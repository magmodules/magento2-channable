<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Collection;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Indexer\Product\Flat\StateFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;

/**
 * Class Products
 *
 * @package Magmodules\Channable\Model\Collection
 */
class Products
{

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ProductCollection
     */
    private $productCollection;
    /**
     * @var ProductAttributeCollectionFactory
     */
    private $productAttributeCollectionFactory;
    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var StateFactory
     */
    private $productFlatState;
    /**
     * @var StockHelper
     */
    private $stockHelper;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Products constructor.
     *
     * @param ProductCollectionFactory          $productCollectionFactory
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param EavConfig                         $eavConfig
     * @param StockHelper                       $stockHelper
     * @param GeneralHelper                     $generalHelper
     * @param ProductHelper                     $productHelper
     * @param StateFactory                      $productFlatState
     * @param ResourceConnection                $resource
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductCollection $productCollection,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        EavConfig $eavConfig,
        StockHelper $stockHelper,
        GeneralHelper $generalHelper,
        ProductHelper $productHelper,
        StateFactory $productFlatState,
        ResourceConnection $resource
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productCollection = $productCollection;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->eavConfig = $eavConfig;
        $this->productFlatState = $productFlatState;
        $this->productHelper = $productHelper;
        $this->generalHelper = $generalHelper;
        $this->stockHelper = $stockHelper;
        $this->resource = $resource;
    }

    /**
     * @param $config
     * @param $page
     * @param $productIds
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getCollection($config, $page, $productIds)
    {
        $flat = $config['flat'];
        $filters = $config['filters'];
        $attributes = $this->getAttributes($config['attributes']);

        if (!$flat) {
            $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
        } else {
            $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
        }

        $collection = $this->productCollectionFactory
            ->create(['catalogProductFlatState' => $productFlatState])
            ->addStoreFilter($config['store_id'])
            ->addAttributeToSelect($attributes)
            ->addUrlRewrite()
            ->setOrder('entity_id', 'ASC');

        if (!empty($filters['visibility'])) {
            $collection->addAttributeToFilter('visibility', ['in' => $filters['visibility']]);
        }

        if (!empty($filters['type_id'])) {
            $collection->addAttributeToFilter('type_id', ['in' => $filters['type_id']]);
        }

        if (!empty($productIds)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        }

        if (!empty($filters['category_ids'])) {
            if (!empty($filters['category_type'])) {
                $collection->addCategoriesFilter([$filters['category_type'] => $filters['category_ids']]);
            }
        }

        if (!empty($config['inventory']['attributes'])) {
            $this->joinCatalogInventoryLeft($collection, $config);
        }

        $this->addFilters($filters, $collection);
        $this->joinPriceIndexLeft($collection, $config['website_id']);

        return $collection;
    }

    /**
     * @param $selectedAttrs
     *
     * @return array
     */
    public function getAttributes($selectedAttrs)
    {
        $attributes = $this->getProductAttributes();
        foreach ($selectedAttrs as $selectedAtt) {
            if (!empty($selectedAtt['source'])) {
                if (empty($selectedAtt['inventory'])) {
                    $attributes[] = $selectedAtt['source'];
                }
            }
            if (!empty($selectedAtt['multi']) && is_array($selectedAtt['multi'])) {
                foreach ($selectedAtt['multi'] as $attribute) {
                    $attributes[] = $attribute['source'];
                }
            }
            if (!empty($selectedAtt['main'])) {
                $attributes[] = $selectedAtt['main'];
            }
        }

        return array_unique($attributes);
    }

    /**
     * @return array
     */
    public function getProductAttributes()
    {
        return [
            'entity_id',
            'image',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'status',
            'tax_class_id',
            'weight',
            'product_has_weight',
            'quantity_and_stock_status'
        ];
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array                                                   $config
     */
    private function joinCatalogInventoryLeft($collection, $config)
    {
        $joinCond = join(
            ' AND ',
            ['cataloginventory.product_id = e.entity_id', 'cataloginventory.website_id = 0']
        );
        $tableName = ['cataloginventory' => $collection->getTable('cataloginventory_stock_item')];
        $collection->getSelect()->joinLeft(
            $tableName,
            $joinCond,
            array_combine($config['inventory']['attributes'], $config['inventory']['attributes'])
        );
    }

    /**
     * @param                                                         $filters
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param string                                                  $type
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addFilters($filters, $collection, $type = 'simple')
    {
        $cType = [
            'eq'   => '=',
            'neq'  => '!=',
            'gt'   => '>',
            'gteq' => '>=',
            'lt'   => '<',
            'lteg' => '<='
        ];

        if (!isset($filters['add_disabled'])) {
            $collection->addAttributeToFilter('status', 1);
        }

        foreach ($filters['advanced'] as $filter) {
            $attribute = $filter['attribute'];
            $condition = $filter['condition'];
            $value = $filter['value'];
            $productFilterType = $filter['product_type'];
            $filterExpr = [];

            if ($type == 'simple' && $productFilterType == 'parent') {
                continue;
            }
            if ($type == 'parent' && $productFilterType == 'simple') {
                continue;
            }

            $attributeModel = $this->eavConfig->getAttribute('catalog_product', $attribute);
            if (!$frontendInput = $attributeModel->getFrontendInput()) {
                continue;
            }

            if ($frontendInput == 'select' || $frontendInput == 'multiselect') {
                $options = $attributeModel->getSource()->getAllOptions();
                if (strpos($value, ',') !== false) {
                    $values = [];
                    $value = explode(',', $value);
                    foreach ($value as $v) {
                        $valueId = array_search(trim($v), array_column($options, 'label'));
                        if ($valueId) {
                            $values[] = $options[$valueId]['value'];
                        }
                    }
                    $value = implode(',', $values);
                } else {
                    $valueId = array_search($value, array_column($options, 'label'));
                    if ($valueId) {
                        $value = $options[$valueId]['value'];
                    }
                }
            }

            if ($attribute == 'quantity_and_stock_status') {
                if ((isset($cType[$condition])) && is_numeric($value)) {
                    $collection->getSelect()->where(
                        'cataloginventory_stock_item.qty ' . $cType[$condition] . ' ' . $value
                    );
                }
                continue;
            }

            if ($attribute == 'min_sale_qty') {
                if ((isset($cType[$condition])) && is_numeric($value)) {
                    $collection->getSelect()->where(
                        'cataloginventory_stock_item.min_sale_qty ' . $cType[$condition] . ' ' . $value
                    );
                }
                continue;
            }

            switch ($condition) {
                case 'nin':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }
                    $filterExpr[] = ['attribute' => $attribute, $condition => $value];
                    $filterExpr[] = ['attribute' => $attribute, 'null' => true];
                    break;
                case 'in':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }
                    $filterExpr[] = ['attribute' => $attribute, $condition => $value];
                    break;
                case 'neq':
                    $filterExpr[] = ['attribute' => $attribute, $condition => $value];
                    $filterExpr[] = ['attribute' => $attribute, 'null' => true];
                    break;
                case 'empty':
                    $filterExpr[] = ['attribute' => $attribute, 'null' => true];
                    break;
                case 'not-empty':
                    $filterExpr[] = ['attribute' => $attribute, 'notnull' => true];
                    break;
                case 'gt':
                case 'gteq':
                case 'lt':
                case 'lteq':
                    if (is_numeric($value)) {
                        $filterExpr[] = ['attribute' => $attribute, $condition => $value];
                    }
                    break;
                default:
                    $filterExpr[] = ['attribute' => $attribute, $condition => $value];
                    break;
            }

            if (!empty($filterExpr)) {
                if ($productFilterType == 'parent') {
                    $filterExpr[] = ['attribute' => 'type_id', 'eq' => 'simple'];
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr, '', 'left');
                } elseif ($productFilterType == 'simple') {
                    $filterExpr[] = ['attribute' => 'type_id', 'neq' => 'simple'];
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr, '', 'left');
                } else {
                    /** @noinspection PhpParamsInspection */
                    $collection->addAttributeToFilter($filterExpr);
                }
            }
        }

        if (!empty($filters['stock'])) {
            $this->stockHelper->addInStockFilterToCollection($collection);
            $collection->setFlag('has_stock_status_filter', true);
        } else {
            $collection->setFlag('has_stock_status_filter', false);
        }
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param                                                         $websiteId
     */
    public function joinPriceIndexLeft($collection, $websiteId)
    {
        $joinCond = join(
            ' AND ',
            [
                'price_index.entity_id = e.entity_id',
                'price_index.website_id = ' . $websiteId,
                'price_index.customer_group_id = 0'
            ]
        );
        $colls = ['final_price', 'min_price', 'max_price'];
        $tableName = ['price_index' => $collection->getTable('catalog_product_index_price')];
        $collection->getSelect()->joinLeft($tableName, $joinCond, $colls);
    }

    /**
     * @param $parentRelations
     * @param $config
     *
     * @return ProductCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getParents($parentRelations, $config): ProductCollection
    {
        $parentRelations = $parentRelations ?: [0];
        $filters = $config['filters'];

        if (!$config['flat']) {
            $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
        } else {
            $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
        }

        $entityField = $this->generalHelper->getLinkField();
        $attributes = $this->getAttributes($config['attributes']);

        $collection = $this->productCollectionFactory
            ->create(['catalogProductFlatState' => $productFlatState])
            ->addStoreFilter($config['store_id'])
            ->addAttributeToFilter($entityField, ['in' => array_values($parentRelations)])
            ->addAttributeToSelect($attributes)
            ->addUrlRewrite()
            ->setRowIdFieldName($entityField);

        if (!empty($filters['category_ids'])) {
            if (!empty($filters['category_type'])) {
                $collection->addCategoriesFilter([$filters['category_type'] => $filters['category_ids']]);
            }
        }

        if (!empty($filters['visibility'])) {
            $collection->addAttributeToFilter('visibility', ['in' => $filters['visibility']]);
        }

        if (!empty($config['inventory']['attributes'])) {
            $this->joinCatalogInventoryLeft($collection, $config);
        }

        $this->addFilters($filters, $collection, 'parent');
        $this->joinPriceIndexLeft($collection, $config['website_id']);
        return $collection->load();

    }

    /**
     * @param $date
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLastEditedCollection($date)
    {
        $collection = $this->productCollectionFactory
            ->create()
            ->addAttributeToSelect(['last_updated', 'entity_id'])
            ->addAttributeToFilter('updated_at', ['gteq' => $date]);

        return $collection->load();
    }

    /**
     * Direct Database Query to get total records of collection with filters.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     *
     * @return int
     */
    public function getCollectionCountWithFilters($productCollection)
    {
        $selectCountSql = $productCollection->getSelectCountSql();
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        return $connection->fetchOne($selectCountSql);
    }
}
