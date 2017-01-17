<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Model;

use Magmodules\Channable\Model\Products as ProductsModel;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;

use Psr\Log\LoggerInterface;

class Generate
{

    const XML_PATH_FEED_RESULT = 'magmodules_channable/feeds/results';
    const XML_PATH_GENERATE = 'magmodules_channable/generate/enable';

    protected $products;
    protected $source;
    protected $product;
    protected $general;
    protected $feed;

    /**
     * Generate constructor.
     * @param Products $products
     * @param SourceHelper $source
     * @param ProductHelper $product
     * @param GeneralHelper $general
     * @param FeedHelper $feed
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductsModel $products,
        SourceHelper $source,
        ProductHelper $product,
        GeneralHelper $general,
        FeedHelper $feed,
        LoggerInterface $logger
    ) {
        $this->products = $products;
        $this->source = $source;
        $this->product = $product;
        $this->general = $general;
        $this->feed = $feed;
        $this->logger = $logger;
    }

    /**
     * @param $storeId
     * @param string $type
     * @return array
     */
    public function generateByStore($storeId, $productId = 0, $page = 0)
    {
        $feed = [];
        $timeStart = microtime(true);
        $config = $this->source->getConfig($storeId);
        $products = $this->products->getCollection($config, $page, $productId);
        $relations = $config['filters']['relations'];
        $limit = $config['filters']['limit'];
        
        foreach ($products as $product) {
            $parent = '';
            if ($relations) {
                if ($parentId = $this->product->getParentId($product->getEntityId())) {
                    $parent = $products->getItemById($parentId);
                    if (!$parent) {
                        $parent = $this->products->loadParentProduct($parentId, $storeId, $config['attributes']);
                    }
                }
            }
            if (!empty($productId)) {
                $feed['product_source'] = $product->getData();
                if (!empty($parent)) {
                    $feed['parent_source'] = $parent->getData();
                }
            }
            if ($dataRow = $this->product->getDataRow($product, $parent, $config)) {
                if ($row = $this->source->reformatData($dataRow, $product, $config)) {
                    $feed[] = $row;
                }
            }
        }
    
        if (!empty($feed)) {
            $return_feed = [];
            if (empty($productId)) {
                $count = $this->products->getCollection($config, '', '', 1);
                $return_feed['config'] = $this->feed->getFeedSummary($timeStart, $count, $limit, count($feed), $page);
                $return_feed['products'] = $feed;
            } else {
                if (!empty($feed[0])) {
                    $return_feed['feed'] = $feed[0];
                }
                if (!empty($feed['product_source'])) {
                    $return_feed['product'] = $feed['product_source'];
                }
                if (!empty($feed['parent_source'])) {
                    $return_feed['parent'] = $feed['parent_source'];
                }
                $return_feed['config'] = $config;
            }
            return $return_feed;
        }
        
        return [];
    }
}
