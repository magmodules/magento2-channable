<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magmodules\Channable\Logger\ChannableLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_Channable';
    const XML_PATH_EXTENSION_ENABLED = 'magmodules_channable/general/enable';
    const XML_PATH_MARKETPLACE_ENABLE = 'magmodules_channable_marketplace/general/enable';
    const XML_PATH_TOKEN = 'magmodules_channable/general/token';

    private $moduleList;
    private $metadata;
    private $storeManager;
    private $config;
    private $logger;
    private $date;

    /**
     * General constructor.
     *
     * @param Context                  $context
     * @param StoreManagerInterface    $storeManager
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param ChannableLogger          $logger
     * @param DateTime                 $date
     * @param Config                   $config
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        ChannableLogger $logger,
        DateTime $date,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->date = $date;
        $this->config = $config;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Set configuration data function
     *
     * @param      $value
     * @param      $key
     * @param null $storeId
     */
    public function setConfigData($value, $key, $storeId = null)
    {
        if ($storeId) {
            $this->config->saveConfig($key, $value, 'stores', $storeId);
        } else {
            $this->config->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->getStoreValue(self::XML_PATH_TOKEN);
    }

    /**
     * Get Configuration data
     *
     * @param      $path
     * @param      $scope
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStoreValue($path, $storeId = null, $scope = null)
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * Returns current version of the extension
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Returns current version of Magento
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /**
     * @param $path
     *
     * @return array
     */
    public function getEnabledArray($path)
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($this->getStoreValue($path, $store->getId())) {
                if ($this->getEnabled($store->getId())) {
                    $storeIds[] = $store->getId();
                }
            }
        }

        return $storeIds;
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
        if (isset($storeId)) {
            return $this->getStoreValue(self::XML_PATH_EXTENSION_ENABLED, $storeId);
        } else {
            return $this->getStoreValue(self::XML_PATH_EXTENSION_ENABLED);
        }
    }

    public function getMarketplaceEnabled()
    {
        return $this->getStoreValue(self::XML_PATH_MARKETPLACE_ENABLE);
    }

    /**
     * @return mixed
     */
    public function getGmtData()
    {
        return $this->date->gmtDate();
    }

    /**
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $this->logger->add($type, $data);
    }
}
