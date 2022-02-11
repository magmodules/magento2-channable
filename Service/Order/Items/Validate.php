<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Items;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;
use Magmodules\Channable\Service\Product\InventoryData;
use Magmodules\Channable\Service\Product\InventorySource;

/**
 * Validate items to check on salability
 */
class Validate
{

    /**
     * Exception msg for empty order lines
     */
    const EMPTY_ITEMS_EXCEPTION = 'No products found in order';

    /**
     * Exception msg for missing product id
     */
    const NO_ID_SET_EXCEPTION = 'Could not load product, due to missing id';

    /**
     * Exception msg for product not found
     */
    const PRODUCT_NOT_FOUND_EXCEPTION = 'Product "%1" not found in catalog (ID: %2)';

    /**
     * Exception msg for configurable product
     */
    const CONFIG_PRODUCT_EXCEPTION = 'Product "%1" can not be ordered, as this is the configurable parent (ID: %2)';

    /**
     * Exception msg for product with required options
     */
    const REQUIRED_OPTIONS_EXCEPTION = 'Product "%1" has required options, this is not supported (ID: %2)';

    /**
     * Exception msg stock issues
     */
    const STOCK_EXCEPTION = 'Product "%1" (ID: %2) not available in requested quantity, stock %3, %4 ordered';

    /**
     * Exception msg stock issues for bundle product
     */
    const NOT_IN_STOCK_EXCEPTION = 'Product "%1" (ID: %2) is not in stock (bundle product)';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var InventorySource
     */
    private $inventorySource;

    /**
     * @var InventoryData
     */
    private $inventoryData;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * Validate constructor.
     * @param ConfigProvider $configProvider
     * @param InventorySource $inventorySource
     * @param InventoryData $inventoryData
     * @param ProductRepositoryInterface $productRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ConfigProvider $configProvider,
        InventorySource $inventorySource,
        InventoryData $inventoryData,
        ProductRepositoryInterface $productRepository,
        WebsiteRepositoryInterface $websiteRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->configProvider = $configProvider;
        $this->inventorySource = $inventorySource;
        $this->inventoryData = $inventoryData;
        $this->websiteRepository = $websiteRepository;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Validate all items in Chanable order
     *
     * @param array $items
     * @param StoreInterface $store
     * @param bool $lvb
     *
     * @throws CouldNotImportOrder
     * @throws NoSuchEntityException
     */
    public function execute(array $items, StoreInterface $store, $lvb)
    {
        if (empty($items)) {
            $exceptionMsg = self::EMPTY_ITEMS_EXCEPTION;
            throw new CouldNotImportOrder(__($exceptionMsg));
        }

        foreach ($items as $item) {
            $product = $this->getProduct($item);
            $this->isNotOfConfigurableType($product);
            $this->doesNotHaveRequiredOption($product);

            if (!$this->configProvider->getEnableBackorders() && !$lvb) {
                $websiteCode = $this->getWebsiteCode((int)$store->getWebsiteId());
                $stockId = $this->inventorySource->execute($websiteCode);
                $this->hasSufficientStock($product, $item, (int)$stockId, (int)$store->getId());
            }
        }
    }

    /**
     * Load product by channable order item array
     *
     * @param array $item
     *
     * @return ProductInterface
     * @throws CouldNotImportOrder
     */
    private function getProduct($item): ProductInterface
    {
        if (empty($item['id'])) {
            $exceptionMsg = self::NO_ID_SET_EXCEPTION;
            throw new CouldNotImportOrder(
                __($exceptionMsg)
            );
        }

        try {
            return $this->productRepository->getById($item['id']);
        } catch (\Exception $exception) {
            $exceptionMsg = self::PRODUCT_NOT_FOUND_EXCEPTION;
            throw new CouldNotImportOrder(__(
                $exceptionMsg,
                !empty($item['title']) ? $item['title'] : __('*unknown*'),
                !empty($item['id']) ? $item['id'] : __('*unknown*')
            ));
        }
    }

    /**
     * Validate if product is not of configurable type
     *
     * @param ProductInterface $product
     *
     * @throws CouldNotImportOrder
     */
    private function isNotOfConfigurableType(ProductInterface $product)
    {
        if ($product->getTypeId() == 'configurable') {
            $exceptionMsg = self::CONFIG_PRODUCT_EXCEPTION;
            throw new CouldNotImportOrder(__(
                $exceptionMsg,
                $product->getName(),
                $product->getId()
            ));
        }
    }

    /**
     * Validate for required options
     *
     * @param ProductInterface $product
     *
     * @throws CouldNotImportOrder
     */
    private function doesNotHaveRequiredOption(ProductInterface $product)
    {
        if ($product->getTypeId() == 'bundle') {
            return;
        }

        $options = $product->getRequiredOptions();
        if (!empty($options)) {
            $exceptionMsg = self::REQUIRED_OPTIONS_EXCEPTION;
            throw new CouldNotImportOrder(__(
                $exceptionMsg,
                $product->getName(),
                $product->getId()
            ));
        }
    }

    /**
     * Get website code by website id
     *
     * @param int $websiteId
     * @return string
     * @throws NoSuchEntityException
     */
    private function getWebsiteCode(int $websiteId): string
    {
        $website = $this->websiteRepository->getById($websiteId);
        return $website->getCode();
    }

    /**
     * Check if product has sufficient stock
     *
     * @param ProductInterface $product
     * @param array $item
     * @param int $stockId
     * @param int $storeId
     *
     * @throws CouldNotImportOrder
     */
    private function hasSufficientStock(
        ProductInterface $product,
        array $item,
        int $stockId,
        int $storeId
    ) {
        $qty = $item['quantity'];
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        if ($stockItem->getUseConfigManageStock()) {
            $manageStock = $this->configProvider->getDefaultManageStock((int)$storeId);
        } else {
            $manageStock = $stockItem->getManageStock();
        }

        if ($manageStock && $stockItem) {

            if ($product->getTypeId() == 'bundle' && $stockItem->getIsInStock()) {
                return;
            }

            if ($product->getTypeId() == 'bundle' && !$stockItem->getIsInStock()) {
                $exceptionMsg = self::NOT_IN_STOCK_EXCEPTION;
                throw new CouldNotImportOrder(__(
                    $exceptionMsg,
                    $product->getName(),
                    $product->getId()
                ));
            }
            if ($stockId) {
                $salableQty = $this->inventoryData->getSalableQty($product, (int)$stockId);
            } else {
                $salableQty = $stockItem->getQty();
            }
            if ($salableQty < $qty) {
                $exceptionMsg = self::STOCK_EXCEPTION;
                throw new CouldNotImportOrder(__(
                    $exceptionMsg,
                    $product->getName(),
                    $product->getId(),
                    $salableQty,
                    $qty
                ));
            }
        }
    }

}
