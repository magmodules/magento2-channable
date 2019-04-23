<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Adminhtml\Item;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;

/**
 * Class MassQue
 *
 * @package Magmodules\Channable\Controller\Adminhtml\Item
 */
class RunQue extends Action
{

    /**
     * @var ItemModel
     */
    public $itemModel;
    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * Manual constructor.
     *
     * @param Context    $context
     * @param ItemModel  $itemModel
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        Context $context,
        ItemModel $itemModel,
        ItemHelper $itemHelper
    ) {
        $this->itemHelper = $itemHelper;
        $this->itemModel = $itemModel;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $results = [];

        try {
            $results = $this->itemModel->updateAll();
        } catch (\Exception $e) {
            $this->itemHelper->addTolog('Manual ItemUpdate', $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        foreach ($results as $storeId => $result) {
            if (isset($result['status']) && $result['status'] == 'success') {
                if (empty($result['qty'])) {
                    $this->messageManager->addNoticeMessage(
                        __('Store ID %1: No items available for update.',
                            $storeId
                        )
                    );
                } else {
                    $this->messageManager->addSuccessMessage(
                        __('Store ID %1: Pushed %2 itemupdate(s), see grid for update result(s).',
                            $storeId,
                            $result['qty']
                        )
                    );
                }
            } else {
                if (isset($result['message'])) {
                    $this->messageManager->addErrorMessage(
                        __('Store ID %1: %2',
                            $storeId,
                            $result['message']
                        )
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Store ID %1: Unknown error, please check error log',
                            $storeId
                        )
                    );
                }
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_Channable::general_item');
    }
}
