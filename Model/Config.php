<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{

    const XPATH_USE_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/use_custom_status';
    const XPATH_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/custom_status';
    const XPATH_SEND_INVOICE = 'magmodules_channable_marketplace/order/invoice_order_email';
    const XPATH_IMPORT_CUSTOMER = 'magmodules_channable_marketplace/order/import_customer';
    const XPATH_IMPORT_COMPANY_NAME = 'magmodules_channable_marketplace/order/import_company_name';
    const XPATH_SEPERATE_HOUSENUMBER = 'magmodules_channable_marketplace/order/seperate_housenumber';
    const XPATH_CUSTOMER_STREET_LINES = 'customer/address/street_lines';
    const XPATH_TAX_PRICE = 'tax/calculation/price_includes_tax';
    const XPATH_TAX_SHIPPING = 'tax/calculation/shipping_includes_tax';
    const XPATH_ENABLE_BACKORDERS = 'magmodules_channable_marketplace/order/backorders';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function processingStatus($storeId = null)
    {
        if (!$this->getPath(self::XPATH_USE_CUSTOM_STATUS, $storeId)) {
            return null;
        }

        return $this->getPath(self::XPATH_CUSTOM_STATUS, $storeId);
    }

    /**
     * @param $path
     * @param $storeId
     *
     * @return string
     */
    private function getPath($path, $storeId)
    {
        return $this->config->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function sendInvoiceEmail($storeId)
    {
        return $this->getFlag(self::XPATH_SEND_INVOICE, $storeId);
    }

    /**
     * @param $path
     * @param $storeId
     *
     * @return bool
     */
    private function getFlag($path, $storeId)
    {
        return $this->config->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function importCompanyName($storeId = null)
    {
        return $this->getFlag(self::XPATH_IMPORT_COMPANY_NAME, $storeId);
    }

    /**
     * @param null|int $storeId
     *
     * @return mixed
     */
    public function importCustomer($storeId = null)
    {
        return $this->getFlag(self::XPATH_IMPORT_CUSTOMER, $storeId);
    }

    /**
     * @param null|int $storeId
     *
     * @return int
     */
    public function getSeperateHousenumber($storeId = null)
    {
        return $this->getFlag(self::XPATH_SEPERATE_HOUSENUMBER, $storeId);
    }

    /**
     * @param null|int $storeId
     *
     * @return int
     */
    public function getCustomerStreetLines($storeId)
    {
        return (int)$this->getPath(self::XPATH_CUSTOMER_STREET_LINES, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultManageStock($storeId = null)
    {
        return $this->getPath(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK, $storeId);
    }

    /**
     * @param      $type
     * @param null $storeId
     *
     * @return int
     */
    public function getNeedsTaxCalulcation($type, $storeId = null)
    {
        if ($type == 'shipping') {
            return $this->getFlag(self::XPATH_TAX_SHIPPING, $storeId);
        } else {
            return $this->getFlag(self::XPATH_TAX_PRICE, $storeId);
        }
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnableBackorders($storeId = null)
    {
        return $this->getFlag(self::XPATH_ENABLE_BACKORDERS, $storeId);
    }
}