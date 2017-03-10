<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Controller\Feed;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Model\Generate as GenerateModel;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;

class Preview extends Action
{

    protected $generate;
    protected $general;
    protected $feed;

    /**
     * Preview constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param GenerateModel $generateModel
     * @param FeedHelper    $feedHelper
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        FeedHelper $feedHelper
    ) {
        $this->generate = $generateModel;
        $this->general = $generalHelper;
        $this->feed = $feedHelper;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }

    /**
     * Execute function for preview of Channable debug feed
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        $page = (int)$this->getRequest()->getParam('page');
        $token = $this->getRequest()->getParam('token');
        $productId = $this->getRequest()->getParam('pid');

        if (empty($storeId) || empty($token)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Params missing!'));

            return $result;
        }

        $enabled = $this->general->getEnabled($storeId);

        if (empty($enabled)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Please enable extension and flush cache!'));

            return $result;
        }

        if ($token != $this->feed->getToken()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(__('Token invalid!'));

            return $result;
        }

        if ($data = $this->generate->generateByStore($storeId, $productId, $page)) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents(print_r($data, true));

            return $result;
        }

        return false;
    }
}
