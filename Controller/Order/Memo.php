<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPutActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Service\Order\UpdateMemo as UpdateMemoService;
use Magmodules\Channable\Service\Order\Validator\Data as DataValidator;

class Memo extends Action implements HttpPutActionInterface
{
    private ConfigProvider $configProvider;
    private JsonFactory $resultJsonFactory;
    private DataValidator $dataValidator;
    private UpdateMemoService $updateMemoService;
    private Json $json;
    private LogRepository $logRepository;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        UpdateMemoService $updateMemoService,
        DataValidator $dataValidator,
        JsonFactory $resultJsonFactory,
        Json $json,
        LogRepository $logRepository
    ) {
        $this->configProvider = $configProvider;
        $this->updateMemoService = $updateMemoService;
        $this->dataValidator = $dataValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->logRepository = $logRepository;
        parent::__construct($context);
    }

    /**
     * Execute function for updating order memo
     */
    public function execute()
    {
        $request = $this->getRequest();
        $token = $this->configProvider->getToken();
        $code = $request->getParam('code');

        if (!$token || !$code || $code !== $token) {
            $response = $this->dataValidator->jsonResponse('Unknown Token');
            return $this->resultJsonFactory->create()->setData($response);
        }

        try {
            $data = $this->json->unserialize($request->getContent());

            if (empty($data['channable_id'])) {
                $response = $this->dataValidator->jsonResponse('Missing channable_id in request body');
                return $this->resultJsonFactory->create()->setData($response);
            }

            if (empty($data['memo'])) {
                $response = $this->dataValidator->jsonResponse('Missing memo in request body');
                return $this->resultJsonFactory->create()->setData($response);
            }

            $response = $this->updateMemoService->execute(
                (int)$data['channable_id'],
                (string)$data['memo']
            );
        } catch (\Throwable $e) {
            $response = $this->dataValidator->jsonResponse($e->getMessage());
        }

        $logData = [
            'type' => 'memo_update',
            'data' => $response
        ];
        $this->logRepository->addDebugLog('memo update', $logData);

        return $this->resultJsonFactory->create()->setData($response);
    }
}
