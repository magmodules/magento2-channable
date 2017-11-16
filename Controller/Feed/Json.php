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

/**
 * Class Json
 *
 * @package Magmodules\Channable\Controller\Feed
 */
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
     * Json constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param GenerateModel $generateModel
     * @param FeedHelper    $feedHelper
     * @param JsonFactory   $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        GenerateModel $generateModel,
        FeedHelper $feedHelper,
        JsonFactory $resultJsonFactory
    ) {
        $this->generateModel = $generateModel;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('id');
        $page = (int)$this->getRequest()->getParam('page', 1);
        $token = $this->getRequest()->getParam('token');

        if (empty($storeId) || empty($token)) {
            return false;
        }

        $enabled = $this->generalHelper->getEnabled($storeId);

        if (!$enabled) {
            return false;
        }

        if ($token != $this->generalHelper->getToken()) {
            return false;
        }

        if ($productId = $this->getRequest()->getParam('pid')) {
            $productId = [$productId];
        } else {
            $productId = null;
        }

        if ($data = $this->generateModel->generateByStore($storeId, $page, $productId)) {
            $result = $this->resultJsonFactory->create();

            return $result->setData($data);
        }
    }
}
