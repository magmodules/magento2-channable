<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigData;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Unserialize\Unserialize;

/**
 * Class General
 *
 * @package Magmodules\Channable\Helper
 */
class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_Channable';
    const XPATH_EXTENSION_ENABLED = 'magmodules_channable/general/enable';
    const XPATH_EXTENSION_VERSION = 'magmodules_channable/general/version';
    const XPATH_MARKETPLACE_ENABLE = 'magmodules_channable_marketplace/general/enable';
    const XPATH_TOKEN = 'magmodules_channable/general/token';
    const XPATH_USE_ROW_ID = 'magmodules_channable/advanced/use_row_id';

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ConfigDataCollectionFactory
     */
    private $configDataCollectionFactory;
    /**
     * @var ConfigData
     */
    private $coreDate;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;
    /**
     * @var LogRepository
     */
    private $logger;
    /**
     * @var Unserialize
     */
    private $unserialize;

    /**
     * General constructor.
     *
     * @param Context                     $context
     * @param StoreManagerInterface       $storeManager
     * @param ProductMetadataInterface    $metadata
     * @param ConfigDataCollectionFactory $configDataCollectionFactory
     * @param ConfigData                  $config
     * @param DateTime                    $coreDate
     * @param TimezoneInterface           $localeDate
     * @param LogRepository               $logger
     * @param Unserialize                 $unserialize
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $metadata,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        ConfigData $config,
        DateTime $coreDate,
        TimezoneInterface $localeDate,
        LogRepository $logger,
        Unserialize $unserialize
    ) {
        $this->storeManager = $storeManager;
        $this->metadata = $metadata;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->config = $config;
        $this->coreDate = $coreDate;
        $this->localeDate = $localeDate;
        $this->logger = $logger;
        $this->unserialize = $unserialize;
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
        return $this->getStoreValue(self::XPATH_TOKEN);
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
     * Get Uncached Value from core_config_data
     *
     * @param      $path
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUncachedStoreValue($path, $storeId)
    {
        $collection = $this->configDataCollectionFactory->create()
            ->addFieldToSelect('value')
            ->addFieldToFilter('path', $path);

        if ($storeId > 0) {
            $collection->addFieldToFilter('scope_id', $storeId);
            $collection->addFieldToFilter('scope', 'stores');
        } else {
            $collection->addFieldToFilter('scope_id', 0);
            $collection->addFieldToFilter('scope', 'default');
        }

        $collection->getSelect()->limit(1);

        return $collection->getFirstItem()->getValue();
    }

    /**
     * Get Configuration Array data.
     *
     * @param      $path
     * @param null $storeId
     * @param null $scope
     *
     * @return array
     */
    public function getStoreValueArray($path, $storeId = null, $scope = null)
    {
        $value = $this->getStoreValue($path, $storeId, $scope);
        return $this->getValueArray($value);
    }

    /**
     * Pre Magento 2.2.x => Unserialize
     * Magento 2.2.x and up => Json Decode
     *
     * @param $value
     *
     * @return array
     */
    public function getValueArray($value)
    {
        if (empty($value)) {
            return [];
        }

        if ($this->isSerialized($value)) {
            return $this->unserialize->unserialize($value);
        }

        $result = json_decode($value, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            if (is_array($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Check if value is a serialized string
     *
     * @param string $value
     *
     * @return boolean
     */
    private function isSerialized($value)
    {
        return (boolean)preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }

    /**
     * Returns current version of the extension
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
       return $this->getStoreValue(self::XPATH_EXTENSION_VERSION);
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
     * Returns current version of Magento
     *
     * @return string
     */
    public function getMagentoEdition()
    {
        return $this->metadata->getEdition();
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
            return $this->getStoreValue(self::XPATH_EXTENSION_ENABLED, $storeId);
        } else {
            return $this->getStoreValue(self::XPATH_EXTENSION_ENABLED);
        }
    }

    /**
     * @return array
     */
    public function getAllStoreIds()
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeIds[] = $store->getId();
        }
        return $storeIds;
    }

    /**
     * @return mixed
     */
    public function getMarketplaceEnabled()
    {
        return $this->getStoreValue(self::XPATH_MARKETPLACE_ENABLE);
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getGmtDate()
    {
        return $this->getDateTime();
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        return $this->coreDate->date("Y-m-d H:i:s");
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getLocaleDate($storeId)
    {
        return $this->localeDate->scopeDate($storeId);
    }

    /**
     * @param null $date
     *
     * @return string
     */
    public function getLocalDateTime($date = null)
    {
        if ($date !== null) {
            return $this->localeDate->date($date)->format('Y-m-d H:i:s');
        }

        return $this->localeDate->date()->format('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->coreDate->gmtTimestamp();
    }

    /**
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $this->logger->addDebugLog($type, $data);
    }

    /**
     * @return string
     */
    public function getLinkField()
    {
        if ($this->isCommerce() && $this->getStoreValue(self::XPATH_USE_ROW_ID)) {
            return 'row_id';
        }

        return 'entity_id';
    }

    /**
     * @return bool
     */
    public function isCommerce()
    {
        return $this->metadata->getEdition() !== ProductMetadata::EDITION_NAME;
    }

}
