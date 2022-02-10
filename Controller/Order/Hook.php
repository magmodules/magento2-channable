<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Order;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File as FilesystemDriver;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Service\Order\Import as OrderImport;
use Magmodules\Channable\Service\Order\Items\Validate as ValidateItems;
use Magmodules\Channable\Service\Order\Validator\Data as DataValidator;

/**
 * Order hook for order import
 */
class Hook extends Action
{

    /**
     * @var OrderImport
     */
    private $orderImport;
    /**
     * @var DataValidator
     */
    private $dataValidator;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var FilesystemDriver
     */
    private $driver;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ValidateItems
     */
    private $validateItems;
    /**
     * @var ChannableOrderRepository
     */
    private $channableOrderRepository;

    /**
     * Hook constructor.
     * @param Context $context
     * @param OrderImport $orderImport
     * @param DataValidator $dataValidator
     * @param JsonFactory $resultJsonFactory
     * @param FilesystemDriver $driver
     * @param ValidateItems $validateItems
     * @param LogRepository $logRepository
     * @param StoreManagerInterface $storeManager
     * @param ChannableOrderRepository $channableOrderRepository
     */
    public function __construct(
        Context $context,
        OrderImport $orderImport,
        DataValidator $dataValidator,
        JsonFactory $resultJsonFactory,
        FilesystemDriver $driver,
        ValidateItems $validateItems,
        LogRepository $logRepository,
        StoreManagerInterface $storeManager,
        ChannableOrderRepository $channableOrderRepository
    ) {
        $this->orderImport = $orderImport;
        $this->dataValidator = $dataValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->driver = $driver;
        $this->validateItems = $validateItems;
        $this->logRepository = $logRepository;
        $this->storeManager = $storeManager;
        $this->channableOrderRepository = $channableOrderRepository;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $orderData = [];
        $request = $this->getRequest();
        $storeId = (int)$request->getParam('store');
        $response = $this->dataValidator->validateRequest($request->getParams());

        try {
            if (empty($response['errors'])) {
                $orderData = $this->dataValidator->validateOrderData(
                    $this->driver->fileGetContents('php://input'),
                    $request->getParams()
                );
                if (!empty($orderData['errors'])) {
                    $response = $orderData;
                }
            }
            if (empty($response['errors'])) {
                $lvb = $orderData['order_status'] == 'shipped';
                $store = $this->storeManager->getStore($storeId);
                $this->validateItems->execute($orderData['products'], $store, $lvb);
                $channableOrder = $this->channableOrderRepository->createByDataArray($orderData, $storeId);
                $order = $this->orderImport->execute($channableOrder);
                $response = $this->dataValidator->jsonResponse('', $order->getIncrementId());
            }
        } catch (Exception $e) {
            $response = $this->dataValidator->jsonResponse($e->getMessage());
        }
        $data = [
            'type' => 'order',
            'head' => $orderData['channable_id'] ?? null,
            'data' => $response
        ];
        $this->logRepository->addDebugLog('order hook', $data);
        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
