<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Controller\Feed;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use Magmodules\Channable\Model\Generate as GenerateModel;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;

class Json extends Action
{

    protected $generate;
    protected $general;
    protected $resultJsonFactory;

    /**
     * Json constructor.
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param GenerateModel $generateModel
     * @param FeedHelper $feedHelper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        FeedHelper $feedHelper,
        JsonFactory $resultJsonFactory
    ) {
        $this->generate = $generateModel;
        $this->general = $generalHelper;
        $this->feed = $feedHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        $page = (int)$this->getRequest()->getParam('page');
        $token = $this->getRequest()->getParam('token');
        $productId = $this->getRequest()->getParam('pid');

        if (empty($storeId) || empty($token)) {
            return false;
        }

        $enabled = $this->general->getEnabled($storeId);

        if (!$enabled) {
            return false;
        }

        if ($token != $this->feed->getToken()) {
            return false;
        }

        if ($data = $this->generate->generateByStore($storeId, $productId, $page)) {
            $result = $this->resultJsonFactory->create();

            return $result->setData($data);
        }
    }
}
