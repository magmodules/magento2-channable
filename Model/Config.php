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
}