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

/**
 * Class Item
 *
 * @package Magmodules\Channable\Helper
 */
class Item extends AbstractHelper
{

    const XPATH_ENABLE = 'magmodules_channable_marketplace/item/enable';
    const XPATH_WEBHOOK = 'magmodules_channable_marketplace/item/webhook';
    const XPATH_LIMIT = 'magmodules_channable_marketplace/item/limit';
    const XPATH_LOG = 'magmodules_channable_marketplace/item/log';
    const XPATH_CRON = 'magmodules_channable_marketplace/item/cron';
    const XPATH_INVALIDATE_MODUS = 'magmodules_channable_marketplace/item/invalidation_modus';
    const XPATH_LAST_RUN = 'magmodules_channable_marketplace/item/last_run';
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * Item constructor.
     *
     * @param Context               $context
     * @param General               $generalHelper
     * @param ItemFactory           $itemFactory
     * @param StoreManagerInterface $storeManager
     */
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
     * @return bool
     */
    public function invalidateByObserver()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $modus = $this->generalHelper->getStoreValue(self::XPATH_INVALIDATE_MODUS);
        if ($modus == 'cron') {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        if (!$this->generalHelper->getMarketplaceEnabled()) {
            return false;
        }
        return $this->generalHelper->getStoreValue(self::XPATH_ENABLE);
    }

    /**
     * @return bool
     */
    public function invalidateByCron()
    {
        $modus = $this->generalHelper->getStoreValue(self::XPATH_INVALIDATE_MODUS);
        if ($modus == 'cron') {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getLastRun()
    {
        $date = $this->generalHelper->getUncachedStoreValue(self::XPATH_LAST_RUN, 0);
        if (!empty($date)) {
            return $date;
        }

        $date = $this->generalHelper->getDateTime();
        return date('Y-m-d H:i:s', strtotime('-1 days', $date));
    }

    /**
     *
     */
    public function setLastRun()
    {
        $date = $this->generalHelper->getDateTime();
        $this->generalHelper->setConfigData($date, self::XPATH_LAST_RUN);
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->generalHelper->getEnabledArray(self::XPATH_ENABLE);
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
        $config['limit'] = $this->generalHelper->getStoreValue(self::XPATH_LIMIT, $storeId);
        $config['webhook'] = $this->generalHelper->getStoreValue(self::XPATH_WEBHOOK, $storeId);
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
        return $this->generalHelper->getStoreValue(self::XPATH_LOG);
    }

    /**
     * @return mixed
     */
    public function isCronEnabled()
    {
        return $this->generalHelper->getStoreValue(self::XPATH_CRON);
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
                'enable'    => $this->generalHelper->getStoreValue(self::XPATH_ENABLE, $storeId),
                'webhook'   => $this->generalHelper->getStoreValue(self::XPATH_WEBHOOK, $storeId),
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
