<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Returns as ReturnsHelper;
use Magmodules\Channable\Model\Returns as ReturnsModel;

/**
 * Class Hook
 *
 * @package Magmodules\Channable\Controller\Returns
 */
class Hook extends Action
{

    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var ReturnsHelper
     */
    private $returnsHelper;
    /**
     * @var ReturnsModel
     */
    private $returnsModel;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Status constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $generalHelper
     * @param ReturnsHelper $returnsHelper
     * @param ReturnsModel  $returnsModel
     * @param JsonFactory   $resultJsonFactory
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        ReturnsHelper $returnsHelper,
        ReturnsModel $returnsModel,
        JsonFactory $resultJsonFactory
    ) {
        $this->generalHelper = $generalHelper;
        $this->returnsHelper = $returnsHelper;
        $this->returnsModel = $returnsModel;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute function for Channable JSON output
     */
    public function execute()
    {
        $returnData = null;
        $request = $this->getRequest();
        $storeId = $request->getParam('store');
        $response = $this->returnsHelper->validateRequestData($request);

        if (empty($response['errors'])) {
            $data = file_get_contents('php://input');
            $returnData = $this->returnsHelper->validateJsonData($data, $request);
            if (!empty($returnData['errors'])) {
                $response = $returnData;
            }
        }

        if (empty($response['errors'])) {
            try {
                $response = $this->returnsModel->importReturn($returnData, $storeId);
            } catch (\Exception $e) {
                $response = $this->returnsHelper->jsonResponse($e->getMessage());
            }
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($response);
    }
}
