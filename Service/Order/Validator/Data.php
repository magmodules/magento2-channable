<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Validator;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Service\Order\ImportSimulator;

/**
 * Validate Order Data
 */
class Data
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ImportSimulator
     */
    private $importSimulator;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * OrderData constructor.
     *
     * @param ConfigProvider $configProvider
     * @param ImportSimulator $importSimulator
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        ImportSimulator $importSimulator,
        Json $json,
        StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->importSimulator = $importSimulator;
        $this->json = $json;
        $this->storeManager = $storeManager;
    }

    /**
     * Validate Request data
     *
     * @param array $request
     *
     * @return bool|mixed
     */
    public function validateRequest($request)
    {
        $storeId = $request['store'] ?? null;
        if (empty($storeId)) {
            return $this->jsonResponse('Store param missing in request');
        }

        if (!$this->configProvider->isOrderEnabled((int)$storeId)) {
            return $this->jsonResponse('Order import not enabled');
        }

        $token = $this->configProvider->getToken();
        if (empty($token)) {
            return $this->jsonResponse('Token not set in admin');
        }

        $code = trim(preg_replace('/\s+/', '', (string)$request['code']));
        if (empty($code)) {
            return $this->jsonResponse('Token param missing in request');
        }

        if ($code != $token) {
            return $this->jsonResponse('Invalid token');
        }

        return false;
    }

    /**
     * @param array $errors
     * @param string $orderId
     *
     * @return array
     */
    public function jsonResponse($errors = [], $orderId = null): array
    {
        $response = [];
        if ($orderId !== null) {
            $response['validated'] = 'true';
            $response['order_id'] = $orderId;
        } else {
            $response['validated'] = 'false';
            $response['errors'] = $errors;
        }
        return $response;
    }

    /**
     * Validate if $data is json
     *
     * @param string $orderData
     * @param array $request
     *
     * @return bool|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validateOrderData(string $orderData, array $request)
    {
        $test = $request['test'] ?? 0;
        $storeId = $request['store'] ?? null;

        if ($test) {
            if (!isset($request['product_id'])) {
                $request['product_id'] = $test;
            }
            if (!isset($request['country'])) {
                $request['country'] = 'NL';
            }
            if (!isset($request['lvb'])) {
                $request['lvb'] = '';
            }
            $params = [
                'product_id' => $request['product_id'] == '0' ? null : $request['product_id'],
                'country' => $request['country'],
                'lvb' => $request['lvb'],
                'price' => $request['price'] ?? null
            ];
            $data = $this->importSimulator->getTestData($params);
        } else {
            if ($orderData == null) {
                return $this->jsonResponse('Post data empty!');
            }
            try {
                $data = $this->json->unserialize($orderData);
            } catch (InvalidArgumentException $e) {
                return $this->jsonResponse($e->getMessage());
            }
        }

        if (empty($data)) {
            return $this->jsonResponse('No Order Data in post');
        }

        if (empty($data['channable_id'])) {
            return $this->jsonResponse('Post missing channable_id');
        }

        if (empty($data['channel_id'])) {
            return $this->jsonResponse('Post missing channel_id');
        }

        if (!empty($data['order_status'])
            && $data['order_status'] == 'shipped'
            && !$this->configProvider->acceptLvbOrder((int)$storeId)
        ) {
            return $this->jsonResponse('LVB Orders not enabled');
        }

        if (!empty($data['price']['currency'])) {
            $currencyCodes = $this->storeManager->getStore($storeId)->getAvailableCurrencyCodes();
            if (!in_array($data['price']['currency'], $currencyCodes)) {
                $msg = __('"%1" not in available currencies for this store', $data['price']['currency']);
                return $this->jsonResponse($msg);
            }
        }

        $data['store_id'] = $storeId;

        return $data;
    }
}
