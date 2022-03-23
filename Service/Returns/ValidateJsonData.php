<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Framework\App\RequestInterface;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class ValidateJsonData
 */
class ValidateJsonData
{

    /**
     * @var Json
     */
    private $json;

    /**
     * @var JsonResponse
     */
    private $jsonResponse;

    /**
     * ValidateJsonData constructor.
     * @param Json $json
     * @param JsonResponse $jsonResponse
     */
    public function __construct(
        Json $json,
        JsonResponse $jsonResponse
    ) {
        $this->json = $json;
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @param string $returnData
     * @param RequestInterface $request
     * @return array
     */
    public function execute(string $returnData, RequestInterface $request): array
    {
        if ($returnData == null) {
            return $this->jsonResponse->execute('Post data empty!');
        }

        try {
            $data = $this->json->unserialize($returnData);
        } catch (InvalidArgumentException $e) {
            return $this->jsonResponse->execute($e->getMessage());
        }

        $storeId = $request->getParam('store');
        if (empty($storeId)) {
            return $this->jsonResponse->execute('Missing Store ID in request');
        }

        if (empty($data)) {
            return $this->jsonResponse->execute('No Returns Data in post');
        }

        if (empty($data['channable_id'])) {
            return $this->jsonResponse->execute('Post missing channable_id');
        }

        if (empty($data['channel_id'])) {
            return $this->jsonResponse->execute('Post missing channel_id');
        }

        return $data;
    }
}
