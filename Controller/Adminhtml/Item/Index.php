<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

class Index extends Action implements HttpGetActionInterface
{

    /**
     * Error message for disabled cron
     */
    public const CRON_DISABLED = 'Cron is not enabled for automatic item updates to Channable!';

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magmodules_Channable::general_item';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        if (!$this->configProvider->isItemCronEnabled()) {
            $this->messageManager->addNoticeMessage(__(self::CRON_DISABLED));
        }
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('Magmodules_Channable::general_item');
        $resultPage->getConfig()->getTitle()->prepend(__('Channable - Items'));
        $resultPage->addBreadcrumb(__('Channable'), __('Channable'));
        $resultPage->addBreadcrumb(__('Items'), __('Items'));

        return $resultPage;
    }
}
