<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * @var ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        PreviewHelper $previewHelper,
        LogRepository $logger
    ) {
        parent::__construct($context);
        $this->generalHelper = $generalHelper;
        $this->generateModel = $generateModel;
        $this->previewHelper = $previewHelper;
        $this->logger = $logger;
        $this->resultFactory = $context->getResultFactory();
    }

    public function execute()
    {
        $request = $this->getRequest();
        $storeId = (int)$request->getParam('id');
        $page = (int)$request->getParam('page', 1);
        $currency = $request->getParam('currency');
        $token = $request->getParam('token');
        $productId = $request->getParam('pid') ? [$request->getParam('pid')] : null;

        if (!$storeId || !$token) {
            return $this->rawResponse((string)__('Params missing!'));
        }

        if (!$this->generalHelper->getEnabled($storeId)) {
            return $this->rawResponse((string)__('Please enable extension and flush cache!'));
        }

        if ($token !== $this->generalHelper->getToken()) {
            return $this->rawResponse((string)__('Token invalid!'));
        }

        try {
            $feed = $this->generateModel->generateByStore($storeId, $page, $productId, $currency);
            $content = $this->previewHelper->getPreviewData($feed, $storeId);
        } catch (\Exception $e) {
            $this->logger->addErrorLog('Generate', $e->getMessage());
            return $this->rawResponse((string)__(self::ERROR_MSG));
        }

        return $this->rawResponse($content, 'text/html');
    }

    private function rawResponse(string $content, string $contentType = 'text/plain'): Raw
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', $contentType);
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $result->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);
        $result->setContents($content);
        return $result;
    }
}
