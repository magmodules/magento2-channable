<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Feed;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Preview as PreviewHelper;
use Magmodules\Channable\Model\Generate as GenerateModel;

class Preview extends Action
{

    private const ERROR_MSG = 'We can\'t generate the feed right now, please check error log (var/log/channable.log)';

    /**
     * @var GenerateModel
     */
    private $generateModel;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var PreviewHelper
     */
    private $previewHelper;
    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param GenerateModel $generateModel
     * @param PreviewHelper $previewHelper
     * @param LogRepository $logger
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        PreviewHelper $previewHelper,
        LogRepository $logger
    ) {
        $this->generateModel = $generateModel;
        $this->generalHelper = $generalHelper;
        $this->previewHelper = $previewHelper;
        $this->logger = $logger;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }

    /**
     * Execute function for preview of Channable debug feed
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        $page = (int)$this->getRequest()->getParam('page', 1);
        $currency = $this->getRequest()->getParam('currency');
        $token = $this->getRequest()->getParam('token');

        if (empty($storeId) || empty($token)) {
            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Params missing!'));

            return $result;
        }

        $enabled = $this->generalHelper->getEnabled($storeId);

        if (empty($enabled)) {
            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Please enable extension and flush cache!'));

            return $result;
        }

        if ($token != $this->generalHelper->getToken()) {
            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Token invalid!'));

            return $result;
        }

        if ($productId = $this->getRequest()->getParam('pid')) {
            $productId = [$productId];
        } else {
            $productId = null;
        }

        try {
            if ($feed = $this->generateModel->generateByStore($storeId, $page, $productId, $currency)) {
                $contents = $this->previewHelper->getPreviewData($feed, $storeId);
                /** @var Raw $result */
                $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $result->setHeader('content-type', 'text/html');
                $result->setContents($contents);
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->addErrorLog('Generate', $e->getMessage());
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/html');
            $result->setContents(self::ERROR_MSG);
            return $result;
        }
    }
}
