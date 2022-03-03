<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Adminhtml Index controller for Order Grid
 */
class Index extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::order_view';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute function for Order Grid
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        /** @var Page $resultPage */
        $resultPage->setActiveMenu('Magmodules_Channable::general_orders');
        $resultPage->getConfig()->getTitle()->prepend(__('Channable - Orders'));
        $resultPage->addBreadcrumb(__('Channable'), __('Orders'));
        $resultPage->addBreadcrumb(__('Orders'), __('Orders'));
        return $resultPage;
    }
}
