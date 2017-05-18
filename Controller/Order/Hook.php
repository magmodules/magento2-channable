<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Order as OrderHelper;
use Magmodules\Channable\Model\Order as OrderModel;

class Hook extends Action
{

    private $generalHelper;
    private $orderHelper;
    private $orderModel;
    private $resultJsonFactory;

    /**
     * Hook constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param OrderHelper   $orderHelper
     * @param OrderModel    $orderModel
     * @param JsonFactory   $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        OrderHelper $orderHelper,
        OrderModel $orderModel,
        JsonFactory $resultJsonFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->orderHelper = $orderHelper;
        $this->orderModel = $orderModel;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store');
        $orderEnabled = $this->orderHelper->getEnabled($storeId);
        $token = $this->generalHelper->getToken();
        $code = $this->getRequest()->getParam('code');
        $test = (int)$this->getRequest()->getParam('test');

        if ($orderEnabled && $token && $code) {
            if ($code == $token) {
                if (!empty($test)) {
                    $data = $this->orderHelper->getTestJsonData($test);
                } else {
                    $data = file_get_contents('php://input');
                }
                if (!empty($data)) {
                    if ($data = $this->orderHelper->validateJsonOrderData($data)) {
                        $response = $this->orderModel->importOrder($data, $storeId);
                    } else {
                        $response = $this->orderHelper->jsonResponse('No validated data');
                    }
                } else {
                    $response = $this->orderHelper->jsonResponse('Empty Data');
                }
            } else {
                $response = $this->orderHelper->jsonResponse('Unknown Token');
            }
        } else {
            $response = $this->orderHelper->jsonResponse('Not enabled');
        }

        if (!empty($response)) {
            $result = $this->resultJsonFactory->create();
            return $result->setData($response);
        }
    }
}
