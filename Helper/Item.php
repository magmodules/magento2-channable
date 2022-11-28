<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magmodules\Channable\Helper\General as GeneralHelper;

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
    const XPATH_WEBHOOK_ITEM = 'magmodules_channable_marketplace/item/webhook';
    const XPATH_ITEM_ENABLED = 'magmodules_channable_marketplace/item/enable';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var CacheTypeListInterface
     */
    private $cacheTypeList;

    /**
     * Item constructor.
     *
     * @param Context                $context
     * @param General                $generalHelper
     * @param StoreManagerInterface  $storeManager
     * @param CacheTypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        StoreManagerInterface $storeManager,
        CacheTypeListInterface $cacheTypeList
    ) {
        $this->storeManager = $storeManager;
        $this->generalHelper = $generalHelper;
        $this->cacheTypeList= $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function invalidateByObserver()
    {
        if (!$this->generalHelper->getMarketplaceEnabled()) {
            return false;
        }

        $modus = $this->generalHelper->getStoreValue(self::XPATH_INVALIDATE_MODUS);
        if ($modus == 'cron') {
            return false;
        }

        return true;
    }

    /**
     * @param null $storeId
     *
     * @return bool|mixed
     */
    public function isEnabled($storeId = null)
    {
        if (!$this->generalHelper->getMarketplaceEnabled()) {
            return false;
        }
        return $this->generalHelper->getStoreValue(self::XPATH_ENABLE, $storeId);
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

        $minusOneDay = ($this->generalHelper->getTimestamp() - 864000);
        return date('Y-m-d H:i:s', $minusOneDay);
    }

    /**
     *
     */
    public function setLastRun()
    {
        $date = $this->generalHelper->getLocalDateTime();
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
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return bool|mixed
     */
    public function validateRequestData($request)
    {
        $storeId = $request->getParam('store');
        if (empty($storeId)) {
            return $this->jsonResponse('Store param missing in request');
        }

        $enabled = $this->generalHelper->getEnabled($storeId);
        if (empty($enabled)) {
            return $this->jsonResponse('Extension not enabled');
        }

        $token = $this->generalHelper->getToken();
        if (empty($token)) {
            return $this->jsonResponse('Token not set in admin');
        }

        $code = trim(preg_replace('/\s+/', '', (string)$request->getParam('code')));
        if (empty($code)) {
            return $this->jsonResponse('Token param missing in request');
        }

        if ($code != $token) {
            return $this->jsonResponse('Invalid token');
        }

        return false;
    }

    /**
     * @param $data
     *
     * @return mixed|string
     */
    public function validateJsonData($data)
    {
        $data = json_decode($data, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $this->jsonResponse('Post not valid JSON-Data: ' . json_last_error_msg());
        }

        if (empty($data)) {
            return $this->jsonResponse('No data in post');
        }

        if (!isset($data['webhook'])) {
            return $this->jsonResponse('Post missing webhook');
        }

        return $data['webhook'];
    }

    /**
     * @param string $errors
     *
     * @return array
     */
    public function jsonResponse($errors = null)
    {
        $response = [];
        if ($errors) {
            $response['validated'] = 'false';
            $response['errors'] = $errors;
        } else {
            $response['validated'] = 'true';
        }
        return $response;
    }

    /**
     * @param $url
     * @param $storeId
     *
     * @return array
     */
    public function setWebhook($url, $storeId)
    {
        $response = [];
        $response['validated'] = 'true';

        if (empty($url)) {
            $this->generalHelper->setConfigData('', self::XPATH_WEBHOOK_ITEM, $storeId);
            $this->generalHelper->setConfigData(0, self::XPATH_ITEM_ENABLED, $storeId);
            $response['msg'] = sprintf('Removed webhook and disabled update', $url);
        } else {
            $this->generalHelper->setConfigData($url, self::XPATH_WEBHOOK_ITEM, $storeId);
            $this->generalHelper->setConfigData(1, self::XPATH_ITEM_ENABLED, $storeId);
            $response['msg'] = sprintf('Webhook set to %s', $url);
        }

        $this->cacheTypeList->cleanType('config');
        return $response;
    }
}
