<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Service\Order\Import as OrderImportService;

/**
 * Import controller for Orders
 */
class Import extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::order_import';

    /**
     * @var OrderImportService
     */
    private $orderImportService;

    /**
     * @var ChannableOrderRepository
     */
    private $channableOrderRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * Simulate constructor.
     * @param Context $context
     * @param OrderImportService $orderImportService
     * @param ChannableOrderRepository $channableOrderRepository
     * @param Json $json
     */
    public function __construct(
        Context $context,
        OrderImportService $orderImportService,
        ChannableOrderRepository $channableOrderRepository,
        Json $json
    ) {
        parent::__construct($context);
        $this->orderImportService = $orderImportService;
        $this->channableOrderRepository = $channableOrderRepository;
        $this->json = $json;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            $channableOrder = $this->channableOrderRepository->get(
                (int)$this->getRequest()->getParam('id')
            );
            $channableOrder->setData('order_status', $channableOrder->getChannableOrderStatus());
            $channableOrder->setData('channable_channel_label', $channableOrder->getChannelLabel());
            $order = $this->orderImportService->execute($channableOrder);
            $this->messageManager->addSuccessMessage(__('Order #%1 created', $order->getIncrementId()));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
