<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Service\Webhook\OrderStatus as OrderStatusCollector;
use Magmodules\Channable\Service\Order\Validator\Data as DataValidator;

class Status extends Action
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var OrderStatusCollector
     */
    private $orderStatusCollector;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var DataValidator
     */
    private $dataValidator;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param OrderStatusCollector $orderStatusCollector
     * @param DataValidator $dataValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        OrderStatusCollector $orderStatusCollector,
        DataValidator $dataValidator,
        JsonFactory $resultJsonFactory
    ) {
        $this->configProvider = $configProvider;
        $this->orderStatusCollector = $orderStatusCollector;
        $this->dataValidator = $dataValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $token = $this->configProvider->getToken();
        $code = $this->getRequest()->getParam('code');
        if ($token && $code && $code == $token) {
            if ($incrementId = (string)$this->getRequest()->getParam('id')) {
                $response = $this->orderStatusCollector->execute($incrementId);
            } else {
                $response = $this->dataValidator->jsonResponse('Missing ID');
            }
        } else {
            $response = $this->dataValidator->jsonResponse('Unknown Token');
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
