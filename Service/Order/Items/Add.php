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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Add items to quote
 */
class Add
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var TaxCalculationn
     */
    private $taxCalculation;

    /**
     * Add constructor.
     * @param ConfigProvider $configProvider
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param CheckoutSession $checkoutSession
     * @param TaxCalculationn $taxCalculation
     */
    public function __construct(
        ConfigProvider $configProvider,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        CheckoutSession $checkoutSession,
        TaxCalculationn $taxCalculation
    ) {
        $this->configProvider = $configProvider;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->taxCalculation = $taxCalculation;
    }

    /**
     * Add items to Quote by OrderData array and returns qty
     *
     * @param Quote $quote
     * @param array $data
     * @param StoreInterface $store
     *
     * @param bool $lvbOrder
     * @return int
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Quote $quote, array $data, StoreInterface $store, bool $lvbOrder = false): int
    {
        $this->setCheckoutSessionData($lvbOrder, $quote->getStoreId());

        $qty = 0;
        foreach ($data['products'] as $item) {
            $product = $this->getProductById((int)$item['id']);
            $price = $this->getProductPrice($item, $product, $store, $quote);
            $product = $this->setProductData($product, $price, $store, $lvbOrder);
            $item = $quote->addProduct($product, (int)$item['quantity']);
            $item->setOriginalCustomPrice($price);
            $qty += (int)$item['quantity'];
        }

        return $qty;
    }

    /**
     * Get Product by ID
     *
     * @param int $productId
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProductById(int $productId): ProductInterface
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Calculate Product Price, depends on Tax Rate and Tax Settings
     *
     * @param array $item
     * @param ProductInterface $product
     * @param StoreInterface $store
     * @param Quote $quote
     *
     * @return float
     */
    private function getProductPrice(array $item, ProductInterface $product, StoreInterface $store, Quote $quote): float
    {
        $price = (float)$item['price'];
        $taxCalculation = $this->configProvider->getNeedsTaxCalulcation('price', (int)$store->getId());
        if (empty($taxCalculation)) {
            $taxClassId = $product->getData('tax_class_id');
            $request = $this->taxCalculation->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                null,
                $store
            );
            $percent = $this->taxCalculation->getRate($request->setData('product_class_id', $taxClassId));
            $price = ($item['price'] / (100 + $percent) * 100);
        }

        return $price;
    }

    /**
     * Set product data
     *
     * @param ProductInterface $product
     * @param float $price
     * @param StoreInterface $store
     * @param bool $lvbOrder
     *
     * @return ProductInterface
     */
    private function setProductData(
        ProductInterface $product,
        float $price,
        StoreInterface $store,
        bool $lvbOrder
    ): ProductInterface {
        if ($this->configProvider->disableStockCheckOnImport((int)$store->getId()) || $lvbOrder) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $stockItem->setUseConfigBackorders(false)
                ->setBackorders(true)
                ->setIsInStock(true);
            $productData = $product->getData();
            $productData['quantity_and_stock_status']['is_in_stock'] = true;
            $productData['is_in_stock'] = true;
            $productData['is_salable'] = true;
            $productData['stock_data'] = $stockItem;
            $product->setData($productData);
        }

        $product->setPrice($price)
            ->setFinalPrice($price)
            ->setSpecialPrice($price)
            ->setTierPrice([])
            ->setOriginalCustomPrice($price)
            ->setSpecialFromDate(null)
            ->setSpecialToDate(null);

        return $product;
    }

    /**
     * @param bool $lvbOrder
     * @param int  $storeId
     *
     * @return void
     */
    private function setCheckoutSessionData(bool $lvbOrder = false, int $storeId = 0): void
    {
        $this->checkoutSession->setChannableSkipQtyCheck(
            $this->configProvider->getEnableBackorders($storeId) ||
            $lvbOrder && $this->configProvider->disableStockMovementForLvbOrders($storeId)
        );

        $this->checkoutSession->setChannableSkipReservation(
            $lvbOrder && $this->configProvider->disableStockMovementForLvbOrders($storeId)
        );
    }
}
