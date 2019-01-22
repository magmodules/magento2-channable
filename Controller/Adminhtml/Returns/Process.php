<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Adminhtml\Returns;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magmodules\Channable\Model\Returns as ReturnsModel;

/**
 * Class Process
 *
 * @package Magmodules\Channable\Controller\Adminhtml\Returns
 */
class Process extends Action
{

    /**
     * @var ReturnsModel
     */
    private $returnsModel;

    /**
     * Process constructor.
     *
     * @param Context      $context
     * @param ReturnsModel $returnsModel
     */
    public function __construct(
        Context $context,
        ReturnsModel $returnsModel
    ) {
        parent::__construct($context);
        $this->returnsModel = $returnsModel;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $result = $this->returnsModel->processReturn($data);

        if (!empty($result['status']) && $result['status'] == 'success') {
            if (!empty($result['msg'])) {
                $this->messageManager->addSuccessMessage($result['msg']);
            } else {
                $this->messageManager->addSuccessMessage(__('Return updated'));
            }
        }

        if (!empty($result['status']) && $result['status'] == 'error') {
            if (!empty($result['msg'])) {
                $this->messageManager->addErrorMessage($result['msg']);
            } else {
                $this->messageManager->addErrorMessage(__('Unkown Error'));
            }
        }

        $this->_redirect('channable/returns/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_Channable::general_returns');
    }
}
