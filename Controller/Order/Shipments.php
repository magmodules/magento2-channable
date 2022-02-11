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
use Magmodules\Channable\Service\Webhook\Shipments as ShipmentsCollector;
use Magmodules\Channable\Service\Order\Validator\Data as DataValidator;

class Shipments extends Action
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var DataValidator
     */
    private $dataValidator;
    /**
     * @var ShipmentsCollector
     */
    private $shipmentsCollector;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param ShipmentsCollector $shipmentsCollector
     * @param DataValidator $dataValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ShipmentsCollector $shipmentsCollector,
        DataValidator $dataValidator,
        JsonFactory $resultJsonFactory
    ) {
        $this->configProvider = $configProvider;
        $this->shipmentsCollector = $shipmentsCollector;
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
            $timespan = intval($this->getRequest()->getParam('timespan'));
            if ($timespan >= 1 && $timespan <= 336) {
                $response = $this->shipmentsCollector->execute($timespan);
            } else {
                $response = $this->dataValidator->jsonResponse('Invalid timespan, supported range: 1-336');
            }
        } else {
            $response = $this->dataValidator->jsonResponse('Unknown Token');
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
