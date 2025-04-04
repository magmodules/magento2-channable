<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Returns;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magmodules\Channable\Service\Returns\ProcessReturn;

class Process extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::returns_process';

    /**
     * @var ProcessReturn
     */
    private $processReturn;

    /**
     * Process constructor.
     *
     * @param Action\Context $context
     * @param ProcessReturn $processReturn
     */
    public function __construct(
        Action\Context $context,
        ProcessReturn $processReturn
    ) {
        parent::__construct($context);
        $this->processReturn = $processReturn;
    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $result = $this->processReturn->execute($data);

        if (!empty($result['status']) && $result['status'] === 'success') {
            $this->messageManager->addSuccessMessage($result['msg'] ?? __('Return updated'));
        }

        if (!empty($result['status']) && $result['status'] === 'error') {
            $this->messageManager->addErrorMessage($result['msg'] ?? __('Unknown Error'));
        }

        /** @var Redirect $redirect */
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($this->_redirect->getRefererUrl());
        return $redirect;
    }
}
