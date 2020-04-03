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
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Hook constructor.
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param OrderHelper $orderHelper
     * @param OrderModel $orderModel
     * @param ValidateItems $validateItems
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        OrderHelper $orderHelper,
        OrderModel $orderModel,
        ValidateItems $validateItems,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->generalHelper = $generalHelper;
        $this->orderHelper = $orderHelper;
        $this->orderModel = $orderModel;
        $this->validateItems = $validateItems;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
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

        try {
            if (empty($response['errors'])) {
                try {
                    $data = file_get_contents('php://input');
                    $orderData = $this->orderHelper->validateJsonData($data, $request);
                    if (!empty($orderData['errors'])) {
                        $response = $orderData;
                    }
                } catch (\Exception $e) {
                    $response = $this->orderHelper->jsonResponse($e->getMessage());
                }
            }
            if (empty($response['errors'])) {
                $lvb = ($orderData['order_status'] == 'shipped') ? true : false;
                $store = $this->storeManager->getStore($storeId);
                $this->validateItems->execute($orderData['products'], $store->getWebsiteId(), $lvb);
                $response = $this->orderModel->importOrder($orderData, $storeId);
            }
        } catch (\Exception $e) {
            $response = $this->orderHelper->jsonResponse($e->getMessage());
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
