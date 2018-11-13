<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Model\ReturnsFactory as ReturnsFactory;

/**
 * Class Item
 *
 * @package Magmodules\Channable\Helper
 */
class Returns extends AbstractHelper
{

    const XPATH_RETURNS_ENABLE = 'magmodules_channable_marketplace/returns/enable';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var ReturnsFactory
     */
    private $returnsFactory;

    /**
     * Item constructor.
     *
     * @param Context               $context
     * @param General               $generalHelper
     * @param ReturnsFactory        $returnsFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        ReturnsFactory $returnsFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->generalHelper = $generalHelper;
        $this->returnsFactory = $returnsFactory;
        parent::__construct($context);
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

        $returns = $this->isEnabled($storeId);
        if (empty($returns)) {
            return $this->jsonResponse('Returns not enabled');
        }

        $token = $this->generalHelper->getToken();
        if (empty($token)) {
            return $this->jsonResponse('Token not set in admin');
        }

        $code = trim(preg_replace('/\s+/', '', $request->getParam('code')));
        if (empty($code)) {
            return $this->jsonResponse('Token param missing in request');
        }

        if ($code != $token) {
            return $this->jsonResponse('Invalid token');
        }

        return false;
    }

    /**
     * @param string $errors
     * @param string $returnsId
     *
     * @return array
     */
    public function jsonResponse($errors = null, $returnsId = null)
    {
        $response = [];
        if (!empty($returnsId)) {
            $response['validated'] = 'true';
            $response['order_id'] = $returnsId;
        } else {
            $response['validated'] = 'false';
            $response['errors'] = $errors;
        }
        return $response;
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
        return $this->generalHelper->getStoreValue(self::XPATH_RETURNS_ENABLE, $storeId);
    }

    /**
     * @param                                         $returnData
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return array|mixed|null
     */
    public function validateJsonData($returnData, $request)
    {
        $data = null;

        if ($returnData == null) {
            return $this->jsonResponse('Post data empty!');
        }

        $data = json_decode($returnData, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $this->jsonResponse('Post not valid JSON-Data: ' . json_last_error_msg());
        }

        $storeId = $request->getParam('store');
        if (empty($storeId)) {
            return $this->jsonResponse('Missing Store ID in request');
        }

        if (empty($data)) {
            return $this->jsonResponse('No Returns Data in post');
        }

        if (empty($data['channable_id'])) {
            return $this->jsonResponse('Post missing channable_id');
        }

        if (empty($data['channel_id'])) {
            return $this->jsonResponse('Post missing channel_id');
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        $configData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $configData[$storeId] = [
                'store_id'    => $storeId,
                'code'        => $store->getCode(),
                'name'        => $store->getName(),
                'is_active'   => $store->getIsActive(),
                'status'      => $this->generalHelper->getStoreValue(self::XPATH_RETURNS_ENABLE, $storeId),
                'webhook_url' => $this->getWebhookUrl($storeId),
            ];
        }
        return $configData;
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    public function getWebhookUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return $url . sprintf('channable/returns/hook/store/%s/code/%s', $storeId, $token);
    }
}
