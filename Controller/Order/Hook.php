<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Order as OrderHelper;
use Magmodules\Channable\Model\Order as OrderModel;
use Magmodules\Channable\Service\Order\Items\Validate as ValidateItems;

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
     * @var ValidateItems
     */
    private $validateItems;
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
     * @param ValidateItems $validateItems
     * @param JsonFactory   $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        OrderHelper $orderHelper,
        OrderModel $orderModel,
        ValidateItems $validateItems,
        JsonFactory $resultJsonFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->orderHelper = $orderHelper;
        $this->orderModel = $orderModel;
        $this->validateItems = $validateItems;
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
        $response = $this->orderHelper->validateRequestData($request);

        if (empty($response['errors'])) {
            $data = file_get_contents('php://input');
            $orderData = $this->orderHelper->validateJsonData($data, $request);
            if (!empty($orderData['errors'])) {
                $response = $orderData;
            }
        }

        if (empty($response['errors'])) {
            try {
                $this->validateItems->execute($orderData['products']);
                $response = $this->orderModel->importOrder($orderData, $storeId);
            } catch (\Exception $e) {
                $response = $this->orderHelper->jsonResponse($e->getMessage());
            }
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
