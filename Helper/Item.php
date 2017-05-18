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
use Magmodules\Channable\Model\ItemFactory as ItemFactory;

class Item extends AbstractHelper
{

    const XML_PATH_ENABLE = 'magmodules_channable_marketplace/item/enable';
    const XML_PATH_WEBHOOK = 'magmodules_channable_marketplace/item/webhook';
    const XML_PATH_LIMIT = 'magmodules_channable_marketplace/item/limit';
    const XML_PATH_LOG = 'magmodules_channable_marketplace/item/log';
    const XML_PATH_CRON = 'magmodules_channable_marketplace/item/cron';

    private $storeManager;
    private $generalHelper;
    private $itemFactory;

    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        ItemFactory $itemFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->generalHelper = $generalHelper;
        $this->itemFactory = $itemFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        if (!$this->generalHelper->getMarketplaceEnabled()) {
            return false;
        }
        return $this->generalHelper->getStoreValue(self::XML_PATH_ENABLE);
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->generalHelper->getEnabledArray(self::XML_PATH_ENABLE);
    }

    /**
     * @param $data
     * @param $type
     */
    public function addTolog($type, $data)
    {
        $this->generalHelper->addTolog('Item ' . $type, $data);
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getApiConfigDetails($storeId)
    {
        $config = [];
        $config['token'] = $this->generalHelper->getToken();
        $config['limit'] = $this->generalHelper->getStoreValue(self::XML_PATH_LIMIT, $storeId);
        $config['webhook'] = $this->generalHelper->getStoreValue(self::XML_PATH_WEBHOOK, $storeId);
        $config['log'] = $this->isLoggingEnabled();

        if ($config['limit'] > 500 || empty($config['limit'])) {
            $config['limit'] = 500;
        }

        return $config;
    }

    /**
     * @return mixed
     */
    public function isLoggingEnabled()
    {
        return $this->generalHelper->getStoreValue(self::XML_PATH_LOG);
    }

    /**
     * @return mixed
     */
    public function isCronEnabled()
    {
        return $this->generalHelper->getStoreValue(self::XML_PATH_CRON);
    }

    /**
     * @param $items
     *
     * @return array
     */
    public function getProductIdsFromCollection($items)
    {
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->getData('id');
        }
        return $productIds;
    }

    /**
     * @return mixed
     */
    public function getConfigData()
    {
        $configData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $configData[$storeId] = [
                'store_id'  => $storeId,
                'code'      => $store->getCode(),
                'name'      => $store->getName(),
                'is_active' => $store->getIsActive(),
                'enable'    => $this->generalHelper->getStoreValue(self::XML_PATH_ENABLE, $storeId),
                'webhook'   => $this->generalHelper->getStoreValue(self::XML_PATH_WEBHOOK, $storeId),
                'qty'       => $this->getQtyByStoreId($storeId),
            ];
        }
        return $configData;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getQtyByStoreId($storeId)
    {
        $items = $this->itemFactory->create()->getCollection()->addFieldToFilter('store_id', $storeId);
        return $items->getSize();
    }
}
