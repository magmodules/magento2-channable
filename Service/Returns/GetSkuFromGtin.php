<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

class GetSkuFromGtin
{
    private $gtinAttribute;
    private ProductFactory $productFactory;
    private ConfigProvider $configProvider;
    private LogRepository $logRepository;
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @param ProductFactory $productFactory
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductFactory $productFactory,
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productFactory = $productFactory;
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param string|null $gtin
     * @param int $storeId
     * @return string|null
     */
    public function execute(?string $gtin, int $storeId): ?string
    {
        $gtinAttribute = $this->getGtinAttributeCode($storeId);
        if ($gtinAttribute == 'sku' || $gtinAttribute == null || $gtin == null) {
            return $gtin;
        }

        try {
            if ($gtinAttribute == 'id') {
                if ($product = $this->productFactory->create()->load($gtin)) {
                    return $product->getSku();
                }
            }
            $product = $this->productCollectionFactory->create()
                ->setStoreId($storeId)
                ->addAttributeToSelect(['sku', $gtinAttribute])
                ->addAttributeToFilter($gtinAttribute, $gtin)
                ->setPageSize(1)
                ->getFirstItem();

            if ($product && $product->getId()) {
                return $product->getSku();
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('getSkuFromGtin', $exception->getMessage());
        }

        return null;
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
