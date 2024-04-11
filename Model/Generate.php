<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\App\Emulation;
use Magmodules\Channable\Helper\Feed as FeedHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Model\Collection\Products as ProductsModel;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Service\Product\TierPriceData;

class Generate
{

    public const XPATH_FEED_RESULT = 'magmodules_channable/feeds/results';
    public const XPATH_GENERATE = 'magmodules_channable/generate/enable';

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
     * @var TierPriceData
     */
    private $tierPriceData;
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * Generate constructor.
     *
     * @param ProductsModel $productModel
     * @param Item $itemModel
     * @param SourceHelper $sourceHelper
     * @param ProductHelper $productHelper
     * @param GeneralHelper $generalHelper
     * @param FeedHelper $feedHelper
     * @param TierPriceData $tierPriceData
     * @param Emulation $appEmulation
     */
    public function __construct(
        ProductsModel $productModel,
        ItemModel $itemModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        TierPriceData $tierPriceData,
        Emulation $appEmulation
    ) {
        $this->productModel = $productModel;
        $this->productHelper = $productHelper;
        $this->itemModel = $itemModel;
        $this->sourceHelper = $sourceHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->tierPriceData = $tierPriceData;
        $this->appEmulation = $appEmulation;
    }

    /**
     * Generate feed by store
     *
     * @param int $storeId
     * @param int|null $page
     * @param array|null $productIds
     * @param string|null $currency
     * @param string|null $type
     * @return array
     * @throws LocalizedException
     */
    public function generateByStore(
        int $storeId,
        ?int $page,
        ?array $productIds = [],
        ?string $currency = null,
        ?string $type = 'feed'
    ): array {
        $feed = [];
        $pages = 1;

        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, $type, $currency);
        $productCollection = $this->productModel->getCollection($config, $page, $productIds);
        $size = $productCollection->getSize();

        if (($config['filters']['limit'] > 0) && empty($productIds)) {
            $productCollection->setPage($page, $config['filters']['limit']);
            $pages = ceil($size / $config['filters']['limit']);
        }

        if (($page <= $pages) || $pages == 0) {

            $products = $productCollection->load();
            $parentRelations = $this->productHelper->getParentsFromCollection($products, $config);
            $parents = $this->productModel->getParents($parentRelations, $config);

            $this->prefetchData($products, $parents, $config);

            foreach ($products as $product) {
                /** @var Product $product */
                $parent = null;
                if (!empty($parentRelations[$product->getEntityId()])) {
                    foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                        if ($foundParent = $parents->getItemById($parentId)) {
                            $parent = $foundParent;
                        }
                    }
                }
                if (!empty($productIds)) {
                    $product->unsetData('extension_attributes');
                    $feed['product_source'] = $product->getData();
                    if (!empty($parent)) {
                        $feed['parent_source'] = $parent->getData();
                    }
                }
                if ($dataRow = $this->getDataRow($product, $parent, $config)) {
                    $feed[] = $dataRow;
                    if (!empty($config['item_updates'])) {
                        $this->itemModel->add($dataRow, $storeId);
                    }
                }
            }

            $return = [];
            if (empty($productIds)) {
                $limit = $config['filters']['limit'];
                $return['config'] = $this->feedHelper
                    ->getFeedSummary($timeStart, $size, $limit, count($feed), $page, $pages);
                $return['products'] = $feed;
            } else {
                $return['products'] = array_filter([
                    'product' => !empty($feed['product_source']) ? $feed['product_source'] : null,
                    'parent' => !empty($feed['parent_source']) ? $feed['parent_source'] : null,
                    'feed' => !empty($feed[0]) ? $feed[0] : null,
                ]);
                $return['config'] = $this->feedHelper
                    ->getFeedSummary($timeStart, $size, count($productIds), count($feed), $page, $pages);
            }
            $feed = $return;
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return $feed;
    }

    /**
     * Calculate size for product collection
     *
     * @param int $storeId
     *
     * @return int
     */
    public function getSize(int $storeId): int
    {
        try {
            $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $config = $this->sourceHelper->getConfig($storeId, 'size');
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
     * @return null|array
     */
    public function getDataRow($product, $parent, $config): ?array
    {
        if ($dataRow = $this->productHelper->getDataRow($product, $parent, $config)) {
            if ($row = $this->sourceHelper->reformatData($dataRow, $product, $parent, $config)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Prefetches data to reduce amount of queries required.
     * This increases performance by a lot for environments with >1ms latency to database.
     *
     * @param ProductCollection $products
     * @param ProductCollection $parents
     * @param array $config
     * @return void
     */
    private function prefetchData(
        ProductCollection $products,
        ProductCollection $parents,
        array $config
    ) {
        $this->productHelper->getInventoryData()->load($products->getColumnValues('sku'), $config);
        $this->productHelper->getMediaData()->load($products, $parents);
        if (in_array('tier_price', array_column($config['attributes'], 'source'))) {
            $this->tierPriceData->load($products, $parents, $config['website_id']);
        }
    }
}
