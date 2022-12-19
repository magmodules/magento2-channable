<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config provider class
 */
class BaseRepository
{

    const XML_PATH_TOKEN = 'magmodules_channable/general/token';

    /**
     * @var Json
     */
    protected $json;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ConfigDataCollectionFactory
     */
    protected $configDataCollectionFactory;
    /**
     * @var ResourceConfig
     */
    protected $resourceConfig;
    /**
     * @var ProductMetadata
     */
    protected $metadata;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var StoreCollection
     */
    protected $storeCollection;

    /**
     * RepositoryNew constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigDataCollectionFactory $configDataCollectionFactory
     * @param Json $json
     * @param ResourceConfig $resourceConfig
     * @param ProductMetadata $metadata
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param StoreCollection $storeCollection
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        Json $json,
        ResourceConfig $resourceConfig,
        ProductMetadata $metadata,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        StoreCollection $storeCollection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->json = $json;
        $this->resourceConfig = $resourceConfig;
        $this->metadata = $metadata;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->storeCollection = $storeCollection;
    }

    /**
     * Retrieve config value by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return string|null
     */
    protected function getStoreValue(string $path, int $storeId = null, string $scope = null): ?string
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        if (empty($storeId)) {
            $storeId = $this->getStore()->getId();
        }
        return (string)$this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * @return StoreInterface
     */
    protected function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (\Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }
        return reset($this->storeManager->getStores());
    }

    /**
     * Retrieve config flag by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return bool
     */
    protected function isSetFlag(string $path, int $storeId = null, string $scope = null): bool
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        if (empty($storeId)) {
            $storeId = $this->getStore()->getId();
        }
        return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
    }

    /**
     * Retrieve config value array by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return array
     */
    protected function getStoreValueArray(string $path, int $storeId = null, string $scope = null): array
    {
        $value = $this->getStoreValue($path, (int)$storeId, $scope);

        if (empty($value)) {
            return [];
        }

        try {
            return $this->json->unserialize($value);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set Config data
     *
     * @param string $value
     * @param string $key
     * @param int|null $storeId
     * @return mixed
     */
    protected function setConfigData(string $value, string $key, int $storeId = null)
    {
        if ($storeId) {
            $this->resourceConfig->saveConfig($key, $value, 'stores', (int)$storeId);
        } else {
            $this->resourceConfig->saveConfig($key, $value, 'default', 0);
        }
        try {
            return $this->json->unserialize($value);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve uncached config value by path and storeId
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    protected function getUncachedStoreValue(string $path, int $storeId = null)
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

        return $collection->getFirstItem()->getData('value');
    }


    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken(?string $token)
    {
        return $this->setConfigData($token, self::XML_PATH_TOKEN);
    }
}
