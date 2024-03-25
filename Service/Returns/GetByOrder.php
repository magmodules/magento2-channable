<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnsDataInterface;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

class GetByOrder
{

    /**
     * @var string
     */
    private $gtinAttribute;
    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Json
     */
    private $json;

    /**
     * @param ReturnsRepository $returnsRepository
     * @param ProductFactory $productFactory
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     * @param Json $json
     */
    public function __construct(
        ReturnsRepository $returnsRepository,
        ProductFactory $productFactory,
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        Json $json
    ) {
        $this->returnsRepository = $returnsRepository;
        $this->productFactory = $productFactory;
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
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
            return $this->getSkuFromGtin($itemData['gtin'] ?? null, $storeId);
        }

        try {
            $itemData = $this->json->unserialize($itemData);
            return $this->getSkuFromGtin($itemData['gtin'] ?? null, $storeId);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param string $gtin
     * @param int $storeId
     * @return string|null
     */
    private function getSkuFromGtin(string $gtin, int $storeId): ?string
    {
        $gtinAttribute = $this->getGtinAttributeCode($storeId);
        if ($gtinAttribute == 'sku' || $gtinAttribute == null) {
            return $gtin;
        }

        try {
            if ($gtinAttribute == 'id') {
                if ($product = $this->productFactory->create()->load($gtin)) {
                    return $product->getSku();
                }
            }
            if ($product = $this->productFactory->create()->loadByAttribute($gtinAttribute, $gtin)) {
                return $product->getSku();
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('getSkuFromGtin', $exception->getMessage());
        }

        return $gtin;
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getGtinAttributeCode(int $storeId): string
    {
        if (!$this->gtinAttribute) {
            $this->gtinAttribute = $this->configProvider->getGtinAttribute($storeId);
        }

        return $this->gtinAttribute;
    }
}
