<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
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
use Magmodules\Channable\Model\Products as ProductsModel;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class Item extends AbstractModel
{

    const OUT_OF_STOCK_MSG = 'out of stock';

    private $itemFactory;
    private $generalHelper;
    private $sourceHelper;
    private $productModel;
    private $productHelper;
    private $itemHelper;
    private $appEmulation;
    private $logger;

    /**
     * Item constructor.
     *
     * @param \Magmodules\Channable\Model\ItemFactory $itemFactory
     * @param GeneralHelper                           $generalHelper
     * @param Products                                $productModel
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
            $data['price'] = $row['price'];
            $data['discount_price'] = (isset($row['special_price']) ? $row['special_price'] : '');
            $data['qty'] = (isset($row['qty']) ? $row['qty'] : '');

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
     * @param $productId
     * @param $type
     */
    public function invalidateProduct($productId, $type)
    {
        $items = $this->itemFactory->create()->getCollection()->addFieldToFilter('id', $productId);
        foreach ($items as $item) {
            $item->setNeedsUpdate('1')->save();
        }

        if ($this->itemHelper->isLoggingEnabled()) {
            $msg = 'Product-id: ' . $productId . ' invalidated by ' . $type;
            $this->addTolog('invalidate', $msg);
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

        if (empty($config['api']['webhook'])) {
            $result = [
                'status'   => 'error',
                'store_id' => $storeId,
                'qty'      => 0,
                'date'     => $this->generalHelper->getGmtData()
            ];
        }

        $items = $this->itemFactory->create()->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('needs_update', 1)
            ->setOrder('last_call', 'ASC')
            ->setPageSize($config['api']['limit']);

        if (!$items->getSize()) {
            $result = [
                'status'   => 'success',
                'store_id' => $storeId,
                'qty'      => 0,
                'date'     => $this->generalHelper->getGmtData()
            ];
        } else {
            $result = $this->updateCollection($items, $storeId, $config);
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
    protected function updateCollection($items, $storeId, $config = '')
    {
        if (empty($config)) {
            $config = $this->sourceHelper->getConfig($storeId, 'api');
        }

        $productData = $this->getProductData($items, $config);
        $postData = $this->getPostData($items, $productData);
        $postResult = $this->postData($postData, $config);
        $updateResult = $this->updateData($postResult);

        return $postResult;
    }

    /**
     * @param $items
     * @param $config
     *
     * @return array
     */
    protected function getProductData($items, $config)
    {
        $productData = [];
        $productIds = $this->itemHelper->getProductIdsFromCollection($items);
        $products = $this->productModel->getCollection($config, '', $productIds);

        foreach ($products as $product) {
            $parent = '';
            if (!empty($config['filters']['relations'])) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $parent = $products->getItemById($parentId);
                    if (!$parent) {
                        $parent = $this->productModel->loadParentProduct($parentId, $config['attributes']);
                    }
                }
            }
            if ($dataRow = $this->productHelper->getDataRow($product, $parent, $config)) {
                if ($row = $this->sourceHelper->reformatData($dataRow, $product, $config)) {
                    $productData[$product->getId()] = $row;
                }
            }
        }

        return $productData;
    }

    /**
     * @param $items
     * @param $productData
     *
     * @return array
     */
    protected function getPostData($items, $productData)
    {
        $postData = [];
        foreach ($items as $item) {
            $id = $item->getData('id');

            if (isset($productData[$id])) {
                $product = $productData[$id];
            } else {
                $product = '';
            }

            $update = [];
            $update['item_id'] = $item->getItemId();
            $update['id'] = $id;
            $update['title'] = isset($product['title']) ? $product['title'] : '';
            $update['stock'] = isset($product['qty']) ? round($product['qty']) : '0';
            $update['availability'] = isset($product['is_in_stock']) ? $product['is_in_stock'] : '0';
            $update['price'] = isset($product['price']) ? $product['price'] : '';
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
    protected function postData($postData, $config)
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
            $results['date'] = $this->generalHelper->getGmtData();
        } else {
            $results['status'] = 'error';
            $results['store_id'] = $config['store_id'];
            $results['webhook'] = $config['api']['webhook'];
            $results['qty'] = count($postData);
            $results['result'] = json_decode($result, true);
            $results['post_data'] = $postData;
            $results['needs_update'] = 1;
            $results['date'] = $this->generalHelper->getGmtData();
        }

        return $results;
    }

    /**
     * @param $postResult
     */
    protected function updateData($postResult)
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
                $postData[$key]['last_call'] = $this->generalHelper->getGmtData();

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
