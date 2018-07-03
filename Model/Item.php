<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magmodules\Channable\Model\ItemFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Model\Collection\Products as ProductsModel;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

/**
 * Class Item
 *
 * @package Magmodules\Channable\Model
 */
class Item extends AbstractModel
{

    const OUT_OF_STOCK_MSG = 'out of stock';
    /**
     * @var \Magmodules\Channable\Model\ItemFactory
     */
    private $itemFactory;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var SourceHelper
     */
    private $sourceHelper;
    /**
     * @var ProductsModel
     */
    private $productModel;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var ItemHelper
     */
    private $itemHelper;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Item constructor.
     *
     * @param \Magmodules\Channable\Model\ItemFactory $itemFactory
     * @param GeneralHelper                           $generalHelper
     * @param ProductsModel                           $productModel
     * @param ProductHelper                           $productHelper
     * @param ItemHelper                              $itemHelper
     * @param SourceHelper                            $sourceHelper
     * @param Emulation                               $appEmulation
     * @param Context                                 $context
     * @param Registry                                $registry
     * @param AbstractResource|null                   $resource
     * @param AbstractDb|null                         $resourceCollection
     * @param array                                   $data
     */
    public function __construct(
        ItemFactory $itemFactory,
        GeneralHelper $generalHelper,
        ProductsModel $productModel,
        ProductHelper $productHelper,
        ItemHelper $itemHelper,
        SourceHelper $sourceHelper,
        Emulation $appEmulation,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->itemFactory = $itemFactory;
        $this->productModel = $productModel;
        $this->productHelper = $productHelper;
        $this->generalHelper = $generalHelper;
        $this->sourceHelper = $sourceHelper;
        $this->itemHelper = $itemHelper;
        $this->appEmulation = $appEmulation;
        $this->logger = $context->getLogger();
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $row
     * @param $storeId
     */
    public function add($row, $storeId)
    {
        $data = [];
        $data['item_id'] = $storeId . sprintf('%08d', $row['id']);
        $data['store_id'] = $storeId;
        $data['id'] = $row['id'];
        $data['title'] = $row['title'];

        if (isset($row['price'])) {
            $data['price'] = preg_replace('/([^0-9\.,])/i', '', $row['price']);
            $data['discount_price'] = (isset($row['sale_price']) ? preg_replace(
                '/([^0-9\.,])/i',
                '',
                $row['sale_price']
            ) : '');
            $data['qty'] = (isset($row['qty']) ? $row['qty'] : '');
            $data['gtin'] = (isset($row['ean']) ? $row['ean'] : '');
            $data['parent_id'] = (isset($row['item_group_id']) ? $row['item_group_id'] : 0);

            if (isset($row['availability']) && $row['availability'] == 'in stock') {
                $data['is_in_stock'] = 1;
            }

            $item = $this->itemFactory->create()->setData($data);

            try {
                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->logger->debug('exception');
            }
        }
    }

    /**
     * Update all Stores
     */
    public function updateAll()
    {
        $this->runProductUpdateCheck();
        $storeIds = $this->itemHelper->getStoreIds();
        foreach ($storeIds as $storeId) {
            $this->updateByStore($storeId);
        }
    }

    /**
     * Invalidate last updated products.
     */
    public function runProductUpdateCheck()
    {
        if (!$this->itemHelper->invalidateByCron()) {
            return;
        }

        $type = 'CronUpdate';
        $lastRun = $this->itemHelper->getLastRun();
        $products = $this->productModel->getLastEditedCollection($lastRun);
        foreach ($products as $product) {
            $this->invalidateProduct($product->getId(), $type);
        }

        $this->itemHelper->setLastRun();
    }

    /**
     * @param $productId
     * @param $type
     */
    public function invalidateProduct($productId, $type)
    {
        $log = $this->itemHelper->isLoggingEnabled();
        /** @var \Magmodules\Channable\Model\ResourceModel\Item\Collection $items */
        $items = $this->itemFactory->create()
            ->getCollection()
            ->addFieldToFilter(['id', 'parent_id'], [['eq' => $productId], ['eq' => $productId]]);

        foreach ($items as $item) {
            $item->setNeedsUpdate('1')->save();
            if ($log) {
                $msg = 'Product-id: ' . $productId . ' invalidated by ' . $type;
                $this->addTolog('invalidate', $msg);
            }
        }
    }

    /**
     * @param $data
     * @param $type
     */
    public function addToLog($type, $data)
    {
        $this->itemHelper->addTolog($type, $data);
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function updateByStore($storeId)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, 'api');

        if (!empty($config['api']['webhook'])) {
            $items = $this->itemFactory->create()->getCollection()
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('needs_update', 1)
                ->setPageSize($config['api']['limit']);
            $items->getSelect()->order('last_call', 'ASC');
            if (!$items->getSize()) {
                $result = [
                    'status'   => 'success',
                    'store_id' => $storeId,
                    'qty'      => 0,
                    'date'     => $this->generalHelper->getGmtDate()
                ];
            } else {
                $result = $this->updateCollection($items, $storeId, $config);
            }
        } else {
            $result = [
                'status'   => 'error',
                'msg'      => 'No webhook set for this store',
                'store_id' => $storeId,
                'qty'      => 0,
                'date'     => $this->generalHelper->getGmtDate()
            ];
        }

        if (!empty($config['api']['log'])) {
            $this->addTolog('post', $result);
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return $result;
    }

    /**
     * @param        $items
     * @param int    $storeId
     * @param string $config
     *
     * @return array
     */
    public function updateCollection($items, $storeId, $config = '')
    {
        if (empty($config)) {
            $config = $this->sourceHelper->getConfig($storeId, 'api');
        }

        $productData = $this->getProductData($items, $config);
        $postData = $this->getPostData($items, $productData);
        $postResult = $this->postData($postData, $config);
        $this->updateData($postResult);

        return $postResult;
    }

    /**
     * @param $items
     * @param $config
     *
     * @return array
     */
    public function getProductData($items, $config)
    {
        $productData = [];

        try {
            $productIds = $this->itemHelper->getProductIdsFromCollection($items);
            $products = $this->productModel->getCollection($config, '', $productIds);
            $parentRelations = $this->productHelper->getParentsFromCollection($products, $config);
            $parents = $this->productModel->getParents($parentRelations, $config);

            foreach ($products as $product) {
                /** @var \Magento\Catalog\Model\Product $product */
                $parent = null;
                if (!empty($parentRelations[$product->getEntityId()])) {
                    foreach ($parentRelations[$product->getEntityId()] as $parentId) {
                        /** @var \Magento\Catalog\Model\Product $parent */
                        if ($parent = $parents->getItemById($parentId)) {
                            continue;
                        }
                    }
                }
                if ($dataRow = $this->productHelper->getDataRow($product, $parent, $config)) {
                    if ($row = $this->sourceHelper->reformatData($dataRow, $product, $parent, $config)) {
                        $productData[$product->getId()] = $row;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('getProductData', $e->getMessage());
        }

        return $productData;
    }

    /**
     * @param $items
     * @param $productData
     *
     * @return array
     */
    public function getPostData($items, $productData)
    {
        $postData = [];
        foreach ($items as $item) {
            $id = $item->getData('id');

            if (!$item->getTitle()) {
                continue;
            }

            if (isset($productData[$id])) {
                $product = $productData[$id];
            } else {
                $product = [];
            }

            $update = [];
            $update['item_id'] = $item->getItemId();
            $update['id'] = $id;
            $update['title'] = isset($product['title']) ? $product['title'] : $item->getTitle();
            $update['gtin'] = isset($product['ean']) ? $product['ean'] : $item->getGtin();
            $update['stock'] = isset($product['qty']) ? round($product['qty']) : 0;
            $update['availability'] = isset($product['availability']) ? $product['availability'] : 0;
            $update['price'] = isset($product['price']) ? $product['price'] : number_format($item->getPrice(), 2);
            $update['discount_price'] = isset($product['discount_price']) ? $product['discount_price'] : '';

            $postData[] = $update;
        }

        return $postData;
    }

    /**
     * @param $postData
     * @param $config
     *
     * @return mixed
     */
    public function postData($postData, $config)
    {
        $results = [];
        $request = curl_init();
        $httpHeader = ['X-MAGMODULES-TOKEN: ' . $config['api']['token'], 'Content-Type:application/json'];
        curl_setopt($request, CURLOPT_URL, $config['api']['webhook']);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($request, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($request);
        $header = curl_getinfo($request, CURLINFO_HTTP_CODE);
        curl_close($request);

        if ($header == '200') {
            $results['status'] = 'success';
            $results['store_id'] = $config['store_id'];
            $results['webhook'] = $config['api']['webhook'];
            $results['qty'] = count($postData);
            $results['result'] = json_decode($result, true);
            $results['post_data'] = $postData;
            $results['needs_update'] = 0;
            $results['date'] = $this->generalHelper->getGmtDate();
        } else {
            $results['status'] = 'error';
            $results['store_id'] = $config['store_id'];
            $results['webhook'] = $config['api']['webhook'];
            $results['qty'] = count($postData);
            $results['result'] = json_decode($result, true);
            $results['post_data'] = $postData;
            $results['needs_update'] = 1;
            $results['date'] = $this->generalHelper->getGmtDate();
        }

        return $results;
    }

    /**
     * @param $postResult
     */
    public function updateData($postResult)
    {
        $itemsResult = $postResult['result'];
        $postData = $postResult['post_data'];
        $items = isset($itemsResult['content']) ? $itemsResult['content'] : [];
        $status = isset($postResult['status']) ? $postResult['status'] : '';

        if ($status == 'success') {
            foreach ($items as $item) {
                $key = array_search($item['id'], array_column($postData, 'id'));
                $postData[$key]['call_result'] = $item['message'];
                $postData[$key]['status'] = ucfirst($item['status']);
                $postData[$key]['needs_update'] = ($item['status'] == 'success') ? 0 : 1;
                $postData[$key]['last_call'] = $this->generalHelper->getGmtDate();

                if ($item['status'] == 'error') {
                    $oldStatus = $this->itemFactory->create()->load($postData[$key]['item_id'])->getStatus();
                    if ($oldStatus == 'Error') {
                        $postData[$key]['status'] = 'Not Found';
                        $postData[$key]['needs_update'] = 0;
                    }
                }
            }
        }

        foreach ($postData as $key => $data) {
            $item = $this->itemFactory->create();
            $item->setData($data);
            try {
                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->logger->debug('exception');
            }
        }
    }

    /**
     *
     */
    public function cleanOldEntries()
    {
        $items = $this->itemFactory->create()->getCollection()
            ->addFieldToFilter('updated_at', ['lteq' => date('Y-m-d H:i:s', strtotime('-2 days'))]);

        foreach ($items as $item) {
            $item->delete();
        }
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Magmodules\Channable\Model\ResourceModel\Item');
    }
}
