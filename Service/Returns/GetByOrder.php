<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnsDataInterface;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

class GetByOrder
{

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var Json
     */
    private $json;

    /**
     * @param ReturnsRepository $returnsRepository
     * @param Json $json
     */
    public function __construct(
        ReturnsRepository $returnsRepository,
        Json $json
    ) {
        $this->returnsRepository = $returnsRepository;
        $this->json = $json;
    }

    /**
     * @param Order $order
     * @return array|null
     */
    public function execute(Order $order): ?array
    {
        $returns = $this->returnsRepository->getByDataSet(
            [
                ReturnsDataInterface::MAGENTO_INCREMENT_ID => $order->getIncrementId(),
                ReturnsDataInterface::STATUS => 'new'
            ]
        );

        if (!$returns) {
            return null;
        }

        $returnsArray = [];
        foreach ($returns as $return) {
            if (!$sku = $this->getSkuFromReturnData($return->getItem())) {
                continue;
            }
            $returnsArray[$sku] = $return;
        }

        return $returnsArray;
    }

    /**
     * @param $itemData
     * @return string|null
     */
    private function getSkuFromReturnData($itemData): ?string
    {
        if (is_array($itemData)) {
            return $itemData['gtin'] ?? null;
        }

        try {
            $itemData = $this->json->unserialize($itemData);
            return $itemData['gtin'] ?? null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
