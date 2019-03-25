<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Class Order
 *
 * @package Magmodules\Channable\Helper
 */
class Order extends AbstractHelper
{

    const XPATH_ORDER_ENABLE = 'magmodules_channable_marketplace/general/enable';
    const XPATH_IMPORT_CUSTOMER = 'magmodules_channable_marketplace/order/import_customer';
    const XPATH_CUSTOMER_GROUP_ID = 'magmodules_channable_marketplace/order/customers_group';
    const XPATH_INVOICE_ORDER = 'magmodules_channable_marketplace/order/invoice_order';
    const XPATH_USE_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/use_custom_status';
    const XPATH_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/custom_status';
    const XPATH_SEPERATE_HOUSENUMBER = 'magmodules_channable_marketplace/order/seperate_housenumber';
    const XPATH_SHIPPING_METHOD = 'magmodules_channable_marketplace/order/shipping_method';
    const XPATH_SHIPPING_CUSTOM = 'magmodules_channable_marketplace/order/shipping_method_custom';
    const XPATH_SHIPPING_METHOD_FALLBACK = 'magmodules_channable_marketplace/order/shipping_method_fallback';
    const XPATH_USE_CHANNEL_ORDERID = 'magmodules_channable_marketplace/order/channel_orderid';
    const XPATH_ENABLE_BACKORDERS = 'magmodules_channable_marketplace/order/backorders';
    const XPATH_LVB_ENABLED = 'magmodules_channable_marketplace/order/lvb';
    const XPATH_LVB_SKIP_STOCK = 'magmodules_channable_marketplace/order/lvb_stock';
    const XPATH_LVB_AUTO_SHIP = 'magmodules_channable_marketplace/order/lvb_ship';
    const XPATH_ORDERID_PREFIX = 'magmodules_channable_marketplace/order/orderid_prefix';
    const XPATH_MARK_COMPLETED_AS_SHIPPED = 'magmodules_channable_marketplace/order/mark_completed_as_shipped';
    const XPATH_LOG = 'magmodules_channable_marketplace/order/log';
    const XPATH_TAX_PRICE = 'tax/calculation/price_includes_tax';
    const XPATH_TAX_SHIPPING = 'tax/calculation/shipping_includes_tax';
    const XPATH_SHIPPING_TAX_CLASS = 'tax/classes/shipping_tax_class';
    const XPATH_CUSTOMER_STREET_LINES = 'customer/address/street_lines';
    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductFactory
     */
    private $product;
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * Order constructor.
     *
     * @param Context                $context
     * @param StoreManagerInterface  $storeManager
     * @param General                $generalHelper
     * @param ProductFactory         $product
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        ProductFactory $product,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return bool|mixed
     */
    public function validateRequestData($request)
    {
        $storeId = $request->getParam('store');
        if (empty($storeId)) {
            return $this->jsonResponse('Store param missing in request');
        }

        $enabled = $this->generalHelper->getEnabled($storeId);
        if (empty($enabled)) {
            return $this->jsonResponse('Extension not enabled');
        }

        $order = $this->getEnabled($storeId);
        if (empty($order)) {
            return $this->jsonResponse('Order import not enabled');
        }

        $token = $this->generalHelper->getToken();
        if (empty($token)) {
            return $this->jsonResponse('Token not set in admin');
        }

        $code = trim(preg_replace('/\s+/', '', $request->getParam('code')));
        if (empty($code)) {
            return $this->jsonResponse('Token param missing in request');
        }

        if ($code != $token) {
            return $this->jsonResponse('Invalid token');
        }

        return false;
    }

    /**
     * @param string $errors
     * @param string $orderId
     *
     * @return array
     */
    public function jsonResponse($errors = '', $orderId = '')
    {
        $response = [];
        if (!empty($orderId)) {
            $response['validated'] = 'true';
            $response['order_id'] = $orderId;
        } else {
            $response['validated'] = 'false';
            $response['errors'] = $errors;
        }
        return $response;
    }

    /**
     * General check if Extension is enabled
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnabled($storeId = null)
    {
        if (!$this->generalHelper->getEnabled($storeId)) {
            return false;
        }

        return $this->generalHelper->getStoreValue(self::XPATH_ORDER_ENABLE, $storeId);
    }

    /**
     * Validate if $data is json
     *
     * @param                                         $orderData
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return bool|mixed
     */
    public function validateJsonData($orderData, $request)
    {
        $data = null;
        $test = $request->getParam('test');
        $lvb = $request->getParam('lvb');
        $storeId = $request->getParam('store');

        if ($test) {
            $data = $this->getTestJsonData($test, $lvb);
        } else {
            if ($orderData == null) {
                return $this->jsonResponse('Post data empty!');
            }
            $data = json_decode($orderData, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                return $this->jsonResponse('Post not valid JSON-Data: ' . json_last_error_msg());
            }
        }

        if (empty($data)) {
            return $this->jsonResponse('No Order Data in post');
        }

        if (empty($data['channable_id'])) {
            return $this->jsonResponse('Post missing channable_id');
        }

        if (empty($data['channel_id'])) {
            return $this->jsonResponse('Post missing channel_id');
        }

        if (!empty($data['order_status'])) {
            if ($data['order_status'] == 'shipped') {
                if (!$this->getLvbEnabled($storeId)) {
                    return $this->jsonResponse('LVB Orders not enabled');
                }
            }
        }

        return $data;
    }

    /**
     * @param      $productId
     * @param bool $lvb
     *
     * @return bool|string
     */
    public function getTestJsonData($productId, $lvb = false)
    {
        $orderStatus = $lvb ? 'shipped' : 'not_shipped';
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->product->create()->load($productId);
        if ($product) {
            $data = '{"channable_id": 112345, "channel_id": 123456, "channel_name": "Bol", 
              "order_status": "' . $orderStatus . '", "extra": {"memo": "Channable Test", 
              "comment": "Channable order id: 999999999"}, "price": {"total": "' . $product->getFinalPrice() . '", 
              "currency": "EUR", "shipping": 0, "subtotal": "' . $product->getFinalPrice() . '",
              "commission": 2.50, "payment_method": "bol", "transaction_fee": 0},
              "billing": { "city": "Amsterdam", "state": "", "email": "dontemail@me.net",
              "address_line_1": "Billing Line 1", "address_line_2": "Billing Line 2", "street": "Donkere Spaarne", 
              "company": "Test company", "zip_code": "5000 ZZ", "last_name": "Channable", "first_name": "Test",
              "middle_name": "from", "country_code": "NL", "house_number": 100, "house_number_ext": "a",
              "address_supplement": "Address supplement" }, "customer": { "email": "dontemail@me.net", 
              "phone": "054333333", "gender": "man", "mobile": "", "company": "Test company", "last_name":
              "From Channable", "first_name": "Test", "middle_name": "" },
              "products": [{"id": "' . $product->getEntityId() . '", "ean": "000000000", 
              "price": "' . $product->getFinalPrice() . '", "title": "' . htmlentities($product->getName()) . '", 
              "quantity": 1, "shipping": 0, "commission": 2.50, "reference_code": "00000000", 
              "delivery_period": "2016-07-12+02:00"}], "shipping": {  "city": "Amsterdam", "state": "", 
              "email": "dontemail@me.net", "street": "Shipping Street", "company": "Magmodules",
              "zip_code": "1000 AA", "last_name": "from Channable", "first_name": "Test order", "middle_name": "",
              "country_code": "NL", "house_number": 21, "house_number_ext": "B", "address_supplement": 
              "Address Supplement", "address_line_1": "Shipping Line 1", "address_line_2": "Shipping Line 2" }}';
            return json_decode($data, true);
        }
        return false;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getLvbEnabled($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_LVB_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getImportCustomer($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_IMPORT_CUSTOMER, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getInvoiceOrder($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_INVOICE_ORDER, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getProcessingStatus($storeId = null)
    {
        $useCustomStatus = $this->generalHelper->getStoreValue(self::XPATH_USE_CUSTOM_STATUS, $storeId);
        if (!$useCustomStatus) {
            return null;
        }

        return $this->generalHelper->getStoreValue(self::XPATH_CUSTOM_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCustomerGroupId($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_CUSTOMER_GROUP_ID, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUseChannelOrderId($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_USE_CHANNEL_ORDERID, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnableBackorders($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_ENABLE_BACKORDERS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getLvbSkipStock($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_LVB_SKIP_STOCK, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getLvbAutoShip($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_LVB_AUTO_SHIP, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return int
     */
    public function getSeperateHousenumber($storeId = null)
    {
        $seprate = $this->generalHelper->getStoreValue(self::XPATH_SEPERATE_HOUSENUMBER, $storeId);
        if (!$seprate) {
            return 0;
        }

        $streetLines = $this->generalHelper->getStoreValue(self::XPATH_CUSTOMER_STREET_LINES, $storeId);
        if ($streetLines > 2) {
            return 2;
        }

        return 1;
    }

    /**
     * @param $channelId
     * @param $storeId
     *
     * @return mixed|null|string|string[]
     */
    public function getUniqueIncrementId($channelId, $storeId)
    {
        $prefix = $this->getOrderIdPrefix($storeId);
        $newIncrementId = $prefix . preg_replace("/[^a-zA-Z0-9]+/", "", $channelId);
        $orderCheck = $this->orderCollectionFactory->create()
            ->addFieldToFilter('increment_id', ['eq' => $newIncrementId])
            ->getSize();

        if ($orderCheck) {
            /** @var \Magento\Sales\Model\Order $lastOrder */
            $lastOrder = $this->orderCollectionFactory->create()
                ->addFieldToFilter('increment_id', ['like' => $newIncrementId . '-%'])
                ->getLastItem();

            if ($lastOrder->getIncrementId()) {
                $lastIncrement = explode('-', $lastOrder->getIncrementId());
                $newIncrementId = substr($lastOrder->getIncrementId(), 0, -(strlen(end($lastIncrement)) + 1));
                $newIncrementId .= '-' . (end($lastIncrement) + 1);
            } else {
                $newIncrementId .= '-1';
            }
        }

        return $newIncrementId;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getOrderIdPrefix($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_ORDERID_PREFIX, $storeId);
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        $configData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $configData[$storeId] = [
                'store_id'    => $storeId,
                'code'        => $store->getCode(),
                'name'        => $store->getName(),
                'is_active'   => $store->getIsActive(),
                'status'      => $this->generalHelper->getStoreValue(self::XPATH_ORDER_ENABLE, $storeId),
                'webhook_url' => $this->getWebhookUrl($storeId),
                'status_url'  => $this->getOrderStatusUrl($storeId)
            ];
        }
        return $configData;
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    public function getWebhookUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return $url . sprintf('channable/order/hook/store/%s/code/%s/ajax/true', $storeId, $token);
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    public function getOrderStatusUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return $url . sprintf('channable/order/status/code/%s', $token);
    }

    /**
     * @param      $type
     * @param null $storeId
     *
     * @return mixed
     */
    public function getNeedsTaxCalulcation($type, $storeId = null)
    {
        if ($type == 'shipping') {
            return $this->generalHelper->getStoreValue(self::XPATH_TAX_SHIPPING, $storeId);
        } else {
            return $this->generalHelper->getStoreValue(self::XPATH_TAX_PRICE, $storeId);
        }
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getTaxClassShipping($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_SHIPPING_TAX_CLASS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getShippingMethod($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_SHIPPING_METHOD, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return array
     */
    public function getShippingCustomShippingMethods($storeId = null)
    {
        $shippingMethodCustom = $this->generalHelper->getStoreValue(self::XPATH_SHIPPING_CUSTOM, $storeId);
        $shippingMethodCustom = preg_replace('/\s+/', '', $shippingMethodCustom);
        $prioritizedMethods = array_flip(array_reverse(explode(';', $shippingMethodCustom)));
        return $prioritizedMethods;
    }

    /**
     * @param null $storeId
     *
     * @return mixed|string
     */
    public function getShippingMethodFallback($storeId = null)
    {
        if ($method = $this->generalHelper->getStoreValue(self::XPATH_SHIPPING_METHOD_FALLBACK, $storeId)) {
            return $method;
        }
        return 'flatrate_flatrate';
    }

    /**
     * @param $id
     * @param $data
     */
    public function addTolog($id, $data)
    {
        $this->generalHelper->addTolog('Order ' . $id, $data);
    }

    /**
     * @return mixed
     */
    public function isLoggingEnabled()
    {
        return $this->generalHelper->getStoreValue(self::XPATH_LOG);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMarkCompletedAsShipped($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_MARK_COMPLETED_AS_SHIPPED, $storeId);
    }
}
