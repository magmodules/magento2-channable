<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Service\Returns\JsonResponse;
use Magmodules\Channable\Service\Returns\GetReturnStatus;

/**
 * Returns Status Controller
 */
class Status extends Action
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var JsonResponse
     */
    private $jsonResponse;
    /**
     * @var GetReturnStatus
     */
    private $getReturnStatus;

    /**
     * Status constructor.
     *
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param JsonResponse $jsonResponse
     * @param JsonFactory $resultJsonFactory
     * @param GetReturnStatus $getReturnStatus
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        JsonResponse $jsonResponse,
        JsonFactory $resultJsonFactory,
        GetReturnStatus $getReturnStatus
    ) {
        $this->configProvider = $configProvider;
        $this->jsonResponse = $jsonResponse;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getReturnStatus = $getReturnStatus;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $enabled = $this->configProvider->isReturnsEnabled();
        $token = $this->configProvider->getToken();
        $code = $this->getRequest()->getParam('code');
        if ($enabled && $token && $code) {
            if ($code == $token) {
                if ($id = $this->getRequest()->getParam('id')) {
                    $response = $this->getReturnStatus->execute((int)$id);
                } else {
                    $response = $this->jsonResponse->execute('Missing ID');
                }
            } else {
                $response = $this->jsonResponse->execute('Unknown Token');
            }
        } else {
            $response = $this->jsonResponse->execute('Extension not enabled!');
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
