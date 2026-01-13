<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

class GetSkuFromGtin
{
    private $gtinAttribute;
    private ConfigProvider $configProvider;
    private LogRepository $logRepository;
    private ProductCollectionFactory $productCollectionFactory;
    private ProductRepositoryInterface $productRepository;

    /**
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
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
                try {
                    $product = $this->productRepository->getById((int)$gtin, false, $storeId);
                    return $product->getSku();
                } catch (NoSuchEntityException $e) {
                    // Product not found, continue to other matching methods
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

            // Fallback: If no match found and GTIN is numeric, try loading by entity ID
            // This handles cases where channels (like Amazon) use Product ID instead of actual GTIN
            if (is_numeric($gtin)) {
                try {
                    $productById = $this->productRepository->getById((int)$gtin, false, $storeId);
                    return $productById->getSku();
                } catch (NoSuchEntityException $e) {
                    // Product not found by ID, return null
                }
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
