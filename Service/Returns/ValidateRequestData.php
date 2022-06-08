<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Framework\App\RequestInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Class ValidateRequestData
 */
class ValidateRequestData
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var JsonResponse
     */
    private $jsonResponse;

    /**
     * ValidateRequestData constructor.
     * @param ConfigProvider $configProvider
     * @param JsonResponse $jsonResponse
     */
    public function __construct(
        ConfigProvider $configProvider,
        JsonResponse $jsonResponse
    ) {
        $this->configProvider = $configProvider;
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|array
     */
    public function execute($request)
    {
        $storeId = $request->getParam('store');
        if (empty($storeId)) {
            return $this->jsonResponse->execute('Store param missing in request');
        }

        if (!$this->configProvider->isEnabled()) {
            return $this->jsonResponse->execute('Extension not enabled');
        }

        if (!$this->configProvider->isReturnsEnabled((int)$storeId)) {
            return $this->jsonResponse->execute('Returns not enabled');
        }

        $token = $this->configProvider->getToken();
        if (empty($token)) {
            return $this->jsonResponse->execute('Token not set in admin');
        }

        $code = trim(preg_replace('/\s+/', '', (string)$request->getParam('code')));
        if (empty($code)) {
            return $this->jsonResponse->execute('Token param missing in request');
        }

        if ($code != $token) {
            return $this->jsonResponse->execute('Invalid token');
        }

        return false;
    }
}
