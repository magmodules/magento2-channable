<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magmodules\Channable\Model\Collection\Products as ProductsModel;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

/**
 * Class Generate
 *
 * @package Magmodules\Channable\Model
 */
class Generate
{

    const XPATH_FEED_RESULT = 'magmodules_channable/feeds/results';
    const XPATH_GENERATE = 'magmodules_channable/generate/enable';
    /**
     * @var ProductsModel
     */
    private $productModel;
    /**
     * @var Item
     */
    private $itemModel;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var SourceHelper
     */
    private $sourceHelper;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var FeedHelper
     */
    private $feedHelper;
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * Generate constructor.
     *
     * @param ProductsModel $productModel
     * @param Item          $itemModel
     * @param SourceHelper  $sourceHelper
     * @param ProductHelper $productHelper
     * @param GeneralHelper $generalHelper
     * @param FeedHelper    $feedHelper
     * @param Emulation     $appEmulation
     */
    public function __construct(
        ProductsModel $productModel,
        ItemModel $itemModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        Emulation $appEmulation
    ) {
        $this->productModel = $productModel;
        $this->productHelper = $productHelper;
        $this->itemModel = $itemModel;
        $this->sourceHelper = $sourceHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->appEmulation = $appEmulation;
    }

    /**
     * @param        $storeId
     * @param        $page
     * @param array  $productIds
     * @param string $type
     *
     * @return array|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateByStore($storeId, $page, $productIds = [], $type = 'feed')
    {
        $feed = [];
        $pages = 1;

        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, $type);
        $productCollection = $this->productModel->getCollection($config, $page, $productIds);
        $size = $this->productModel->getCollectionCountWithFilters($productCollection);


        if (($config['filters']['limit'] > 0) && empty($productId)) {
            $productCollection->setPage($page, $config['filters']['limit'])->getCurPage();
            $pages = ceil($size / $config['filters']['limit']);
        }

        $products = $productCollection->load();
        $parentRelations = $this->productHelper->getParentsFromCollection($products, $config);
        $parents = $this->productModel->getParents($parentRelations, $config);

        foreach ($products as $product) {
            /** @var \Magento\Catalog\Model\Product $product */
            $parent = null;
            if (!empty($parentRelations[$product->getEntityId()])) {
                foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                    if ($parent = $parents->getItemById($parentId)) {
                        continue;
                    }
                }
            }
            if (!empty($productIds)) {
                $feed['product_source'] = $product->getData();
                if (!empty($parent)) {
                    $feed['parent_source'] = $parent->getData();
                }
            }
            if ($dataRow = $this->getDataRow($product, $parent, $config)) {
                $feed[] = $dataRow;
            }

            if (!empty($config['item_updates'])) {
                $this->itemModel->add($dataRow, $storeId);
            }
        }

        if (($page <= $pages) || $pages == 0) {
            $return = [];
            if (empty($productId)) {
                $limit = $config['filters']['limit'];
                $return['config'] = $this->feedHelper->getFeedSummary($timeStart, $size, $limit, count($feed), $page, $pages);
                $return['products'] = $feed;
            } else {
                if (!empty($feed[0])) {
                    $return['feed'] = $feed[0];
                }
                if (!empty($feed['product_source'])) {
                    $return['product'] = $feed['product_source'];
                }
                if (!empty($feed['parent_source'])) {
                    $return['parent'] = $feed['parent_source'];
                }
                $return['config'] = $config;
            }
            $feed = $return;
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return $feed;
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    public function getSize($storeId) {
        try {
            $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $config = $this->sourceHelper->getConfig($storeId, 'szie');
            $productCollection = $this->productModel->getCollection($config, 1, []);
            $size = $this->productModel->getCollectionCountWithFilters($productCollection);
            $this->appEmulation->stopEnvironmentEmulation();
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('getSize', $e->getMessage());
            $size = -1;
        }

        return $size;
    }

    /**
     * @param $product
     * @param $parent
     * @param $config
     *
     * @return string
     */
    public function getDataRow($product, $parent, $config)
    {
        if ($dataRow = $this->productHelper->getDataRow($product, $parent, $config)) {
            if ($row = $this->sourceHelper->reformatData($dataRow, $product, $config)) {
                return $row;
            }
        }

        return false;
    }
}
