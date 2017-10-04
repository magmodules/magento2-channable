<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Collection;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Flat\StateFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;

class Products
{

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductAttributeCollectionFactory
     */
    private $productAttributeCollectionFactory;

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
     * @param StockHelper                       $stockHelper
     * @param GeneralHelper                     $generalHelper
     * @param ProductHelper                     $productHelper
     * @param StateFactory                      $productFlatState
     * @param ResourceConnection                $resource
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        StockHelper $stockHelper,
        GeneralHelper $generalHelper,
        ProductHelper $productHelper,
        StateFactory $productFlatState,
        ResourceConnection $resource
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->productFlatState = $productFlatState;
        $this->productHelper = $productHelper;
        $this->generalHelper = $generalHelper;
        $this->stockHelper = $stockHelper;
        $this->resource = $resource;
    }

    /**
     * @param        $config
     * @param string $productIds
     *
     * @return mixed
     */
    public function getCollection($config, $productIds)
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
            ->addAttributeToSelect($attributes)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addMinimalPrice()
            ->addUrlRewrite()
            ->addFinalPrice();

        if (!empty($filters['visibility'])) {
            $collection->addAttributeToFilter('visibility', ['in' => $filters['visibility']]);
        }

        if (!empty($filters['stock'])) {
            if (version_compare($this->generalHelper->getMagentoVersion(), "2.2.0", "<")) {
                $this->stockHelper->addInStockFilterToCollection($collection);
            } else {
                $collection->setFlag('has_stock_status_filter', true);
            }
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

        $collection->joinTable(
            'cataloginventory_stock_item',
            'product_id=entity_id',
            $config['inventory']['attributes']
        );

        $this->addFilters($filters, $collection);
        $collection->getSelect()->group('e.entity_id');

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
     * @param $filters
     * @param $collection
     */
    public function addFilters($filters, $collection)
    {
        $cType = [
            'eq'   => '=',
            'neq'  => '!=',
            'gt'   => '>',
            'gteq' => '>=',
            'lt'   => '<',
            'lteg' => '<='
        ];

        foreach ($filters['advanced'] as $filter) {
            $attribute = $filter['attribute'];
            $condition = $filter['condition'];
            $value = $filter['value'];

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

                    $collection->addAttributeToFilter(
                        [
                            [
                                'attribute' => $attribute,
                                $condition  => $value
                            ],
                            ['attribute' => $attribute, 'null' => true]
                        ]
                    );
                    break;
                case 'in':
                    if (strpos($value, ',') !== false) {
                        $value = explode(',', $value);
                    }
                    $collection->addAttributeToFilter($attribute, [$condition => $value]);
                    break;
                case 'neq':
                    $collection->addAttributeToFilter(
                        [
                            ['attribute' => $attribute, $condition => $value],
                            ['attribute' => $attribute, 'null' => true]
                        ]
                    );
                    break;
                case 'empty':
                    $collection->addAttributeToFilter($attribute, ['null' => true]);
                    break;
                case 'not-empty':
                    $collection->addAttributeToFilter($attribute, ['notnull' => true]);
                    break;
                case 'gt':
                case 'gteq':
                case 'lt':
                case 'lteq':
                    if (is_numeric($value)) {
                        $collection->addAttributeToFilter($attribute, [$condition => $value]);
                    }
                    break;
                default:
                    $collection->addAttributeToFilter($attribute, [$condition => $value]);
                    break;
            }
        }
    }

    /**
     * @param $products
     * @param $config
     *
     * @return $this|array
     */
    public function getParents($products, $config)
    {
        if (!empty($config['filters']['relations'])) {
            $ids = [];
            foreach ($products as $product) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $ids[] = $parentId;
                }
            }

            if (empty($ids)) {
                return [];
            }

            $flat = false;

            if (!$flat) {
                $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
            } else {
                $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
            }

            $attributes = $this->getAttributes($config['attributes']);

            $collection = $this->productCollectionFactory
                ->create(['catalogProductFlatState' => $productFlatState])
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('entity_id', ['in' => $ids])
                ->addAttributeToSelect($attributes)
                ->addMinimalPrice()
                ->addUrlRewrite();

            if (!empty($config['filters']['type_id'])) {
                $collection->addAttributeToFilter('type_id', ['in' => $config['filters']['type_id']]);
            }

            return $collection->load();
        }

        return [];
    }

    /**
     * Direct Database Query to get total records of collection with filters.
     *
     * @param $productCollection
     *
     * @return int
     */
    public function getCollectionCountWithFilters($productCollection)
    {
        $selectCountSql = $productCollection->getSelectCountSql();
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $count = $connection->fetchAll($selectCountSql);
        return count($count);
    }
}
