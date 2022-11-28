<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Item;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\Item as ItemHelper;

class Webhook extends Action
{

    /**
     * @var ItemHelper
     */
    private $itemHelper;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Webhook constructor.
     *
     * @param Context $context
     * @param ItemHelper $itemHelper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ItemHelper $itemHelper,
        JsonFactory $resultJsonFactory
    ) {
        $this->itemHelper = $itemHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $webhookData = null;
        $request = $this->getRequest();
        $storeId = $request->getParam('store');
        $response = $this->itemHelper->validateRequestData($request);

        if (empty($response['errors'])) {
            $data = file_get_contents('php://input');
            $webhookData = $this->itemHelper->validateJsonData($data);
            if (!empty($webhookData['errors'])) {
                $response = $webhookData;
            }
        }

        if (empty($response['errors'])) {
            try {
                $response = $this->itemHelper->setWebhook($webhookData, $storeId);
            } catch (\Exception $e) {
                $response = $this->itemHelper->jsonResponse($e->getMessage());
            }
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
