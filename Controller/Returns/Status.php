<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Returns as ReturnsHelper;
use Magmodules\Channable\Model\Returns as ReturnsModel;

/**
 * Class Status
 *
 * @package Magmodules\Channable\Controller\Returns
 */
class Status extends Action
{

    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var ReturnsHelper
     */
    private $returnsHelper;
    /**
     * @var ReturnsModel
     */
    private $returnsModel;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Status constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param ReturnsHelper $returnsHelper
     * @param ReturnsModel  $returnsModel
     * @param JsonFactory   $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        ReturnsHelper $returnsHelper,
        ReturnsModel $returnsModel,
        JsonFactory $resultJsonFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->returnsHelper = $returnsHelper;
        $this->returnsModel = $returnsModel;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $enabled = $this->generalHelper->getEnabled();
        $token = $this->generalHelper->getToken();
        $code = $this->getRequest()->getParam('code');
        if ($enabled && $token && $code) {
            if ($code == $token) {
                if ($id = $this->getRequest()->getParam('id')) {
                    $response = $this->returnsModel->getReturnStatus($id);
                } else {
                    $response = $this->returnsHelper->jsonResponse('Missing ID');
                }
            } else {
                $response = $this->returnsHelper->jsonResponse('Unknown Token');
            }
        } else {
            $response = $this->returnsHelper->jsonResponse('Extension not enabled!');
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
