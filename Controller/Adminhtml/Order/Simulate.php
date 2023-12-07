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
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Service\Order\ImportSimulator;

/**
 * Adminhtml Simulate controller for Order Grid to simulate Test Order
 */
class Simulate extends Action
{

    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Magmodules_Channable::order_simulate';

    /**
     * @var ImportSimulator
     */
    private $importSimulator;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Simulate constructor.
     * @param Context $context
     * @param ImportSimulator $importSimulator
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Context $context,
        ImportSimulator $importSimulator,
        RedirectInterface $redirect
    ) {
        $this->importSimulator = $importSimulator;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $order = $this->importSimulator->execute(
                (int)$this->getRequest()->getParam('store_id'),
                $this->getExtraParams()
            );
            $this->messageManager->addSuccessMessage(__('Test order #%1 created', $order->getIncrementId()));
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
        }

        return $resultRedirect;
    }

    /**
     * Process and validate params
     *
     * @return array
     */
    private function getExtraParams(): array
    {
        $extraParams = [];
        foreach (ImportSimulator::PARAMS as $param) {
            $extraParams[$param] = $this->getRequest()->getParam($param, null);
        }

        return $extraParams;
    }
}
