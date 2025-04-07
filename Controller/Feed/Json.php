<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Feed;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magmodules\Channable\Helper\Feed as FeedHelper;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Model\Generate as GenerateModel;
use Magento\Framework\App\Response\Http as HttpResponse;

class Json extends Action
{
    /**
     * @var GenerateModel
     */
    private $generateModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var FeedHelper
     */
    private $feedHelper;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        FeedHelper $feedHelper,
        JsonFactory $resultJsonFactory,
        RemoteAddress $remoteAddress
    ) {
        parent::__construct($context);
        $this->generateModel = $generateModel;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->remoteAddress = $remoteAddress;
    }

    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        $page = (int)$this->getRequest()->getParam('page', 1);
        $currency = $this->getRequest()->getParam('currency');
        $token = $this->getRequest()->getParam('token');

        $result = $this->resultJsonFactory->create();
        $this->setNoCacheHeaders();

        if (empty($storeId) || empty($token)) {
            return $result->setData([]);
        }

        if (!$this->generalHelper->getEnabled($storeId)) {
            return $result->setData([]);
        }

        if ($token !== $this->generalHelper->getToken()) {
            return $result->setData([]);
        }

        $productId = $this->getRequest()->getParam('pid');
        $productIds = $productId ? [$productId] : null;

        $ip = $this->remoteAddress->getRemoteAddress();
        $this->feedHelper->setLastFetched($storeId, $ip);

        try {
            $data = $this->generateModel->generateByStore($storeId, $page, $productIds, $currency);
            return $result->setData($data ?: []);
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('Generate', $e->getMessage());
            return $result->setData(['error' => $e->getMessage()]);
        }
    }

    /**
     * Add headers to prevent caching by Varnish or browser
     */
    private function setNoCacheHeaders(): void
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $response->setHeader('Pragma', 'no-cache', true);
        $response->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);
    }
}
