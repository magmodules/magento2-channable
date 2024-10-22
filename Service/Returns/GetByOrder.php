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
     * @var GetSkuFromGtin
     */
    private $getSkuFromGtin;

    public function __construct(
        ReturnsRepository $returnsRepository,
        GetSkuFromGtin $getSkuFromGtin,
        Json $json
    ) {
        $this->returnsRepository = $returnsRepository;
        $this->getSkuFromGtin = $getSkuFromGtin;
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
        /** @var ReturnsDataInterface $return */
        foreach ($returns as $return) {
            if (!$sku = $this->getSkuFromReturnData($return->getItem(), (int)$return->getStoreId())) {
                continue;
            }
            $returnsArray[$sku] = $return;
        }

        return $returnsArray;
    }

    /**
     * @param $itemData
     * @param int $storeId
     * @return string|null
     */
    private function getSkuFromReturnData($itemData, int $storeId): ?string
    {
        if (is_array($itemData)) {
            return $this->getSkuFromGtin->execute($itemData['gtin'] ?? null, $storeId);
        }

        try {
            $itemData = $this->json->unserialize($itemData);
            return $this->getSkuFromGtin->execute($itemData['gtin'] ?? null, $storeId);
        } catch (\Exception $exception) {
            return null;
        }
    }
}
