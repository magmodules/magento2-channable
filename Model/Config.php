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

    const XPATH_IMPORT_CUSTOMER = 'magmodules_channable_marketplace/order/import_customer';
    const XPATH_IMPORT_COMPANY_NAME = 'magmodules_channable_marketplace/order/import_company_name';
    const XPATH_SEPERATE_HOUSENUMBER = 'magmodules_channable_marketplace/order/seperate_housenumber';
    const XPATH_CUSTOMER_STREET_LINES = 'customer/address/street_lines';

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
     * @param null|int $storeId
     *
     * @return mixed
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
        return (int) $this->getPath(self::XPATH_CUSTOMER_STREET_LINES, $storeId);
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
     * @param $path
     * @param $storeId
     *
     * @return bool
     */
    private function getFlag($path, $storeId)
    {
        return $this->config->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}