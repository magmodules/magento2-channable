<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Order\Items;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magmodules\Channable\Service\Product\InventorySource;
use Magmodules\Channable\Service\Product\InventoryData;
use Magmodules\Channable\Model\Config;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Class Validate
 *
 * @package Magmodules\Channable\Service\Order\Items
 */
class Validate
{

    /**
     * Exception msg for empty order lines
     */
    const EMPTY_ITEMS_EXCEPTION = 'No products found in order';

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
     * @var Config
     */
    private $config;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * Validate constructor.
     * @param Config $config
     * @param InventorySource $inventorySource
     * @param InventoryData $inventoryData
     * @param ProductRepositoryInterface $productRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Config $config,
        InventorySource $inventorySource,
        InventoryData $inventoryData,
        ProductRepositoryInterface $productRepository,
        WebsiteRepositoryInterface $websiteRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->config = $config;
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
     * @param int $websiteId
     * @param bool $lvb
     *
     * @throws CouldNotImportOrder
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(array $items, $websiteId, $lvb)
    {
        if (empty($items)) {
            $exceptionMsg = self::EMPTY_ITEMS_EXCEPTION;
            throw new CouldNotImportOrder(__($exceptionMsg));
        }

        $websiteCode = $this->getWebsiteCode($websiteId);
        $stockId = $this->inventorySource->execute($websiteCode);
        $stockCheck = $this->config->getEnableBackorders();

        foreach ($items as $item) {
            $product = $this->getProduct($item);
            $this->isNotOfConfigurableType($product);
            $this->doesNotHaveRequiredOption($product);

            if (!$stockCheck && !$lvb) {
                $this->hasSufficientStock($product, $item['quantity'], $stockId);
            }
        }
    }

    /**
     * Get website code by ID
     *
     * @param int $websiteId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsiteCode($websiteId)
    {
        $website = $this->websiteRepository->getById($websiteId);

        return $website->getCode();
    }

    /**
     * Load product by item
     *
     * @param $item
     *
     * @return ProductInterface
     * @throws CouldNotImportOrder
     */
    private function getProduct($item)
    {
        $productId = $item['id'];

        try {
            return $this->productRepository->getById($productId);
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
     * Validate product for enough stock
     *
     * @param ProductInterface $product
     * @param                  $qty
     * @param                  $stockId
     *
     * @throws CouldNotImportOrder
     */
    private function hasSufficientStock(ProductInterface $product, $qty, $stockId)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        if ($stockItem->getUseConfigManageStock()) {
            $manageStock = $this->config->getDefaultManageStock();
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
                $salableQty = $this->inventoryData->getSalableQty($product, $stockId);
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