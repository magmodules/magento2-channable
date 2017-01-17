<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
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

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_Channable';
    const XML_PATH_EXTENSION_ENABLED = 'magmodules_channable/general/enable';

    protected $moduleList;
    protected $metadata;
    protected $storeManager;
    protected $config;

    /**
     * General constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Config $config
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * General check if Extension is enabled
     * @param null $storeId
     * @return mixed
     */
    public function getEnabled($storeId = null)
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * Get Configuration data
     * @param $path
     * @param $scope
     * @param null $storeId
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
     * Set configuration data function
     * @param $value
     * @param $key
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
     * Returns current version of the extension
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Returns current version of Magento
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /**
     * @param $path
     * @return array
     */
    public function getEnabledArray($path)
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($this->getStoreValue($path)) {
                if ($this->getEnabled($store->getId())) {
                    $storeIds[] = $store->getId();
                }
            }
        }

        return $storeIds;
    }
}
