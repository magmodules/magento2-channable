<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
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
use Psr\Log\LoggerInterface;

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
     * Generate constructor.
     *
     * @param ProductsModel   $productModel
     * @param Item            $itemModel
     * @param SourceHelper    $sourceHelper
     * @param ProductHelper   $productHelper
     * @param GeneralHelper   $generalHelper
     * @param FeedHelper      $feedHelper
     * @param Emulation       $appEmulation
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductsModel $productModel,
        ItemModel $itemModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        Emulation $appEmulation,
        LoggerInterface $logger
    ) {
        $this->productModel = $productModel;
        $this->productHelper = $productHelper;
        $this->itemModel = $itemModel;
        $this->sourceHelper = $sourceHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * @param       $storeId
     * @param int   $page
     * @param array $productIds
     *
     * @return array
     */
    public function generateByStore($storeId, $page, $productIds = [])
    {
        $feed = [];
        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, 'feed');
        $productCollection = $this->productModel->getCollection($config, $page, $productIds);

        $size = $productCollection->count();
        $pages = $productCollection->getLastPageNumber();

        $products = $productCollection->load();
        $parents = $this->productModel->getParents($products, $config);

        foreach ($products as $product) {
            $parent = '';
            if (!empty($config['filters']['relations'])) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $parent = $parents->getItemById($parentId);
                }
            }
            if (!empty($productId)) {
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

        if ($page <= $pages) {
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
