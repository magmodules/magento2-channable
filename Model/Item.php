<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Model\Collection\Products as ProductsModel;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Item
 *
 * @package Magmodules\Channable\Model
 */
class Item extends AbstractModel
{

    const OUT_OF_STOCK_MSG = 'out of stock';
    const CURL_TIMEOUT = 15;

    /**
     * @var ItemFactory
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
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * Item constructor.
     *
     * @param ItemFactory           $itemFactory
     * @param GeneralHelper         $generalHelper
     * @param ProductsModel         $productModel
     * @param ProductHelper         $productHelper
     * @param ItemHelper            $itemHelper
     * @param SourceHelper          $sourceHelper
     * @param Emulation             $appEmulation
     * @param CurlFactory           $curlFactory
     * @param Context               $context
     * @param Registry              $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null       $resourceCollection
     * @param array                 $data
     */
    public function __construct(
        ItemFactory $itemFactory,
        GeneralHelper $generalHelper,
        ProductsModel $productModel,
        ProductHelper $productHelper,
        ItemHelper $itemHelper,
        SourceHelper $sourceHelper,
        Emulation $appEmulation,
        CurlFactory $curlFactory,
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
        $this->curlFactory = $curlFactory;
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
                $this->generalHelper->addTolog('Item add', $e->getMessage());
            }
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function updateAll()
    {
        $result = [];

        $this->runProductUpdateCheck();
        $storeIds = $this->itemHelper->getStoreIds();
        foreach ($storeIds as $storeId) {
            $result[$storeId] = $this->updateByStore($storeId);
        }

        return $result;
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
     * @param      $storeId
     * @param null $itemIds
     *
     * @return array|mixed
     * @throws LocalizedException
     */
    public function updateByStore($storeId, $itemIds = null)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $config = $this->sourceHelper->getConfig($storeId, 'api');

        if (!empty($config['api']['webhook'])) {
            $items = $this->itemFactory->create()->getCollection()
                ->addFieldToFilter('store_id', $storeId)
                ->setOrder('updated_at', 'ASC')
                ->setPageSize($config['api']['limit']);

            if ($itemIds !== null) {
                $items->addFieldToFilter('item_id', ['in' => $itemIds]);
            } else {
                $items->addFieldToFilter('needs_update', 1);
            }

            $items->getSelect()->order('last_call', 'ASC');
            if (!$items->getSize()) {
                $result = [
                    'status'   => 'success',
                    'store_id' => $storeId,
                    'qty'      => 0,
                    'date'     => $this->generalHelper->getDateTime()
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
                'date'     => $this->generalHelper->getDateTime()
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
     * @param        $storeId
     * @param string $config
     *
     * @return mixed
     * @throws LocalizedException
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
     * @throws LocalizedException
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
            throw new LocalizedException(__($e->getMessage()));
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
        $httpHeader = [
            'X-MAGMODULES-TOKEN: ' . $config['api']['token'],
            'Content-Type:application/json'
        ];

        try {
            /** @var \Magento\Framework\HTTP\Adapter\Curl $curl */
            $curl = $this->curlFactory->create();
            $curl->setConfig(['timeout' => self::CURL_TIMEOUT]);
            $curl->write(
                \Zend_Http_Client::POST,
                $config['api']['webhook'],
                '1.1',
                $httpHeader,
                json_encode($postData)
            );

            $response = $curl->read();
            $responseCode = \Zend_Http_Response::extractCode($response);
            $responseBody = \Zend_Http_Response::extractBody($response);
            $result = json_decode($responseBody, true);
            $results['status'] = isset($result['status']) ? $result['status'] : 'error';
            $results['result'] = $result;
            $results['message'] = isset($result['message']) ? $result['message'] : null;

            if (!isset($result['message']) && $responseCode == '401') {
                $results['message'] = __('401 Unauthorized Webhook')->render();
                $results['status'] = 'unauthorized';
            }
        } catch (\Exception $e) {
            $results['status'] = 'exception';
            $results['message'] = $e->getMessage();
            $results['result'] = [];
        }

        $results['store_id'] = $config['store_id'];
        $results['webhook'] = $config['api']['webhook'];
        $results['qty'] = count($postData);
        $results['post_data'] = $postData;
        $results['date'] = $this->generalHelper->getGmtDate();
        $results['needs_update'] = isset($responseCode) && $responseCode >= 500 ? 1 : 0;

        return $results;
    }

    /**
     * @param $postResult
     */
    public function updateData($postResult)
    {
        $itemsResult = $postResult['result'];
        $postData = $postResult['post_data'];
        $gtmDate = $this->generalHelper->getGmtDate();

        if (!empty($itemsResult['content']) && is_array($itemsResult['content'])) {
            foreach ($itemsResult['content'] as $item) {
                $key = array_search($item['id'], array_column($postData, 'id'));
                $postData[$key]['call_result'] = $item['message'];
                $postData[$key]['status'] = ucfirst($item['status']);
                $postData[$key]['needs_update'] = 0;
                $postData[$key]['last_call'] = $gtmDate;
            }
        } else {
            foreach ($postData as &$item) {
                $item['qty'] = $item['stock'];
                $item['is_in_stock'] = $item['availability'];
                $item['call_result'] = $postResult['message'];
                $item['status'] = $postResult['status'];
                $item['needs_update'] = $postResult['needs_update'];
                $item['last_call'] = $gtmDate;
                unset($item['stock']);
                unset($item['availability']);
            }
        }

        foreach ($postData as $key => $data) {
            if (!empty($data) && is_array($data)) {
                $item = $this->itemFactory->create();
                try {
                    $item->setData($data)->save();
                } catch (\Exception $e) {
                    $this->generalHelper->addTolog('Item updateData', $e->getMessage());
                }
            }
        }
    }

    /**
     * @param $itemIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function updateByItemIds($itemIds)
    {
        $result = [];

        $storeIds = $this->itemHelper->getStoreIds();
        foreach ($storeIds as $storeId) {
            $result[$storeId] = $this->updateByStore($storeId, $itemIds);
        }

        return $result;
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
