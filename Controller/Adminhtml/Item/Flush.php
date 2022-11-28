<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Service\ItemUpdate\FlushItems;

class Flush extends Action
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magmodules_Channable::general_item';

    /**
     * @var FlushItems
     */
    private $flushItems;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @param Action\Context $context
     * @param RedirectInterface $redirect
     * @param FlushItems $flushItems
     */
    public function __construct(
        Action\Context $context,
        RedirectInterface $redirect,
        FlushItems $flushItems
    ) {
        $this->flushItems = $flushItems;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Flush Item Table
     */
    public function execute(): Redirect
    {
        $this->flushItems->execute();
        $this->messageManager->addSuccessMessage(__('Table flushed!'));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->redirect->getRefererUrl());
        return $resultRedirect;
    }
}
