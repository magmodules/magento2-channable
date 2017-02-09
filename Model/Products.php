<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Magmodules\Channable\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Flat\StateFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;

class Products
{
    /**
     * Products constructor.
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param StockHelper $stockHelper
     * @param StateFactory $productFlatState
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        StockHelper $stockHelper,
        StateFactory $productFlatState
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->productFlatState = $productFlatState;
        $this->stockHelper = $stockHelper;
    }

    /**
     * @param $config
     * @param int $page
     * @param string $productId
     * @param string $count
     * @return mixed
     */
    public function getCollection($config, $page = 1, $productId = '', $count = '')
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
            ->addMinimalPrice()
            ->addUrlRewrite()
            ->addFinalPrice();
        
        if (($filters['limit'] > 0) && empty($productId) && empty($count)) {
            $collection->setPage($page, $filters['limit'])->getCurPage();
        }
        
        if (!empty($filters['visibility'])) {
            $collection->addAttributeToFilter('visibility', ['in' => $filters['visibility']]);
        }

        if (!empty($filters['stock'])) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }

        if (!empty($productId)) {
            $collection->addAttributeToFilter('entity_id', $productId);
        }

        if (!empty($filters['category_ids'])) {
            if (!empty($filters['category_type'])) {
                $collection->addCategoriesFilter([$filters['category_type'] => $filters['category_ids']]);
            }
        }

        if (!empty($config['inventory']['attributes'])) {
            $collection->joinTable(
                'cataloginventory_stock_item',
                'product_id=entity_id',
                $config['inventory']['attributes']
            );
        }
        
        if (empty($count)) {
            return $collection->load();
        } else {
            return $collection->count();
        }
    }

    /**
     * @param $selectedAttrs
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
        return ['entity_id', 'image', 'price', 'special_price', 'special_from_date',
        'special_to_date', 'status', 'tax_class_id', 'weight', 'product_has_weight'];
    }

    /**
     * @param $parentId
     * @param $storeId
     * @param $attributes
     * @return mixed
     */
    public function loadParentProduct($parentId, $storeId, $attributes)
    {
        $flat = false;
        
        if (!$flat) {
            $productFlatState = $this->productFlatState->create(['isAvailable' => false]);
        } else {
            $productFlatState = $this->productFlatState->create(['isAvailable' => true]);
        }
        
        $attributes = $this->getAttributes($attributes);
        
        $parent = $this->productCollectionFactory
            ->create(['catalogProductFlatState' => $productFlatState])
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('entity_id', $parentId)
            ->addAttributeToSelect($attributes)
            ->getFirstItem();
            
        return $parent;
    }
}
