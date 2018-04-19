<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Adminhtml\Selftest;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\Selftest as SelftestHelper;

/**
 * Class Api
 *
 * @package Magmodules\Channable\Controller\Adminhtml\Selftest
 */
class Api extends Action
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var SelftestHelper
     */
    private $selftestHelper;

    /**
     * Api constructor.
     *
     * @param Context        $context
     * @param JsonFactory    $resultJsonFactory
     * @param SelftestHelper $selftestHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SelftestHelper $selftestHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->selftestHelper = $selftestHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $results = $this->selftestHelper->runApiTests();
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'msg' => implode('<br/>', $results)]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_Channable::general');
    }
}
