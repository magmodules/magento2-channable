<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File as FilesystemDriver;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;
use Magmodules\Channable\Service\Returns\JsonResponse;
use Magmodules\Channable\Service\Returns\ValidateRequestData;
use Magmodules\Channable\Service\Returns\ValidateJsonData;
use Magmodules\Channable\Service\Returns\ImportReturn;

/**
 * Returns Hook Controller
 */
class Hook extends Action
{

    /**
     * @var FilesystemDriver
     */
    private $filesystemDriver;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var ValidateRequestData
     */
    private $validateRequestData;
    /**
     * @var JsonResponse
     */
    private $jsonResponse;
    /**
     * @var ValidateJsonData
     */
    private $validateJsonData;
    /**
     * @var ImportReturn
     */
    private $importReturn;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * Hook constructor.
     *
     * @param Context $context
     * @param ReturnsRepository $returnsRepository
     * @param JsonFactory $resultJsonFactory
     * @param FilesystemDriver $filesystemDriver
     * @param ValidateRequestData $validateRequestData
     * @param JsonResponse $jsonResponse
     * @param ValidateJsonData $validateJsonData
     * @param ImportReturn $importReturn
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FilesystemDriver $filesystemDriver,
        ValidateRequestData $validateRequestData,
        JsonResponse $jsonResponse,
        ValidateJsonData $validateJsonData,
        ImportReturn $importReturn,
        LogRepository $logRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystemDriver = $filesystemDriver;
        $this->validateRequestData = $validateRequestData;
        $this->jsonResponse = $jsonResponse;
        $this->validateJsonData = $validateJsonData;
        $this->importReturn = $importReturn;
        $this->logRepository = $logRepository;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     *
     * @return Json
     */
    public function execute(): Json
    {
        $returnData = null;
        $request = $this->getRequest();
        $storeId = (int)$request->getParam('store');
        $response = $this->validateRequestData->execute($request);
        try {
            if (empty($response['errors'])) {
                $returnData = $this->validateJsonData->execute(
                    $this->filesystemDriver->fileGetContents('php://input'),
                    $request
                );
                if (!empty($returnData['errors'])) {
                    $response = $returnData;
                }
            }
            if (empty($response['errors'])) {
                $this->logRepository->addDebugLog('Returns', $returnData);
                $response = $this->importReturn->execute($returnData, $storeId);
            }
        } catch (\Exception $e) {
            $response = $this->jsonResponse->execute($e->getMessage());
            $this->logRepository->addErrorLog('Returns hook', $response);
        }
        $this->logRepository->addDebugLog('Returns hook', $response);
        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
