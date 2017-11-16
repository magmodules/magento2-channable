<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magento\Catalog\Model\ProductFactory;

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
    const XPATH_SHIPPING_METHOD = 'magmodules_channable_marketplace/order/shipping_method';
    const XPATH_SHIPPING_METHOD_FALLBACK = 'magmodules_channable_marketplace/order/shipping_method_fallback';
    const XPATH_LOG = 'magmodules_channable_marketplace/order/log';
    const XPATH_TAX_PRICE = 'tax/calculation/price_includes_tax';
    const XPATH_TAX_SHIPPING = 'tax/calculation/shipping_includes_tax';
    const XPATH_SHIPPING_TAX_CLASS = 'tax/classes/shipping_tax_class';
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
     * Order constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     * @param ProductFactory        $product
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        ProductFactory $product
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->product = $product;
        parent::__construct($context);
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
     * @param $productId
     *
     * @return bool|string
     */
    public function getTestJsonData($productId)
    {
        $product = $this->product->create()->load($productId);
        if ($product) {
            $string = '{"channable_id": 112345, "channel_id": 12345678, "channel_name": "Bol", "extra": {"memo": "Channable Test", "comment": "Channable order id: 999999999"}, "price": {"total": ' . $product->getFinalPrice() . ', "currency": "EUR", "shipping": 0, "subtotal": ' . $product->getFinalPrice() . ', "commission": 2.50, "payment_method": "bol", "transaction_fee": 0}, "billing": { "city": "Amsterdam", "state": "", "email": "dontemail@me.net", "address_line_1": "Billing Line 1", "address_line_2": "Billing Line 2", "street": "Donkere Spaarne", "company": "Test company", "zip_code": "5000 ZZ", "last_name": "Channable", "first_name": "Test", "middle_name": "from", "country_code": "NL", "house_number": 100, "house_number_ext": "a", "address_supplement": "Address supplement" }, "customer": { "email": "dontemail@me.net", "phone": "054333333", "gender": "man", "mobile": "", "company": "Test company", "last_name": "From Channable", "first_name": "Test", "middle_name": "" }, "products": [{"id": "' . $product->getEntityId() . '", "ean": "000000000", "price": ' . $product->getFinalPrice() . ', "title": "' . $product->getName() . '", "quantity": 1, "shipping": 0, "commission": 2.50, "reference_code": "00000000", "delivery_period": "2016-07-12+02:00"}], "shipping": {  "city": "Amsterdam", "state": "", "email": "dontemail@me.net", "street": "Shipping Street", "company": "Magmodules", "zip_code": "1000 AA", "last_name": "from Channable", "first_name": "Test order", "middle_name": "", "country_code": "NL", "house_number": 21, "house_number_ext": "B", "address_supplement": "Address Supplement", "address_line_1": "Shipping Line 1", "address_line_2": "Shipping Line 2" }}';
            return $string;
        }
        return false;
    }

    /**
     * Validate if $data is json
     *
     * @param $data
     *
     * @return bool|mixed
     */
    public function validateJsonOrderData($data)
    {
        $data = json_decode($data, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }
        if (empty($data['channable_id'])) {
            return false;
        }
        if (empty($data['channel_id'])) {
            return false;
        }
        return $data;
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
    public function getCustomerGroupId($storeId = null)
    {
        return $this->generalHelper->getStoreValue(self::XPATH_CUSTOMER_GROUP_ID, $storeId);
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
        return $url . sprintf('channable/order/hook/store/%s/code/%s', $storeId, $token);
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
            return $this->generalHelper->getStoreValue(self::XPATH_TAX_PRICE, $storeId);
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
}
