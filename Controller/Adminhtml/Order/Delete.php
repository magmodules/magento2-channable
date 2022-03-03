<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;

/**
 * Delete controller for Orders
 */
class Delete extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::order_delete';

    /**
     * @var ChannableOrderRepository
     */
    private $orderRepository;

    /**
     * Simulate constructor.
     * @param Action\Context $context
     * @param ChannableOrderRepository $orderRepository
     */
    public function __construct(
        Action\Context $context,
        ChannableOrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $this->orderRepository->deleteById(
                (int)$this->getRequest()->getParam('id')
            );
            $this->messageManager->addSuccessMessage(__('Order was removed'));
        } catch (\Exception $e) {
            $this->messageManager->addSuccessMessage($e->getMessage());
        }

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
