<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Order as OrderHelper;
use Magmodules\Channable\Model\Order as OrderModel;

/**
 * Class Hook
 *
 * @package Magmodules\Channable\Controller\Order
 */
class Hook extends Action
{

    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var OrderModel
     */
    private $orderModel;
    /**
     * @var JsonFactory
     */
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
        $orderData = null;
        $request = $this->getRequest();
        $storeId = $request->getParam('store');
        $response = $this->orderHelper->validateRequestData($request, 'order');

        if (empty($response['errors'])) {
            $data = file_get_contents('php://input');
            $orderData = $this->orderHelper->validateJsonOrderData($data, $request);
            if (!empty($orderData['errors'])) {
                $response = $orderData;
            }
        }

        if (empty($response['errors'])) {
            try {
                $response = $this->orderModel->importOrder($orderData, $storeId);
            } catch (\Exception $e) {
                $response = $this->orderHelper->jsonResponse($e->getMessage());
            }
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
