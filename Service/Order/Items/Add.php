<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Order\Items;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Registry;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magmodules\Channable\Model\Config;

/**
 * Class Add
 *
 * @package Magmodules\Channable\Service\Order\Items
 */
class Add
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var TaxCalculationn
     */
    private $taxCalculation;

    /**
     * Add constructor.
     *
     * @param Config                     $config
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface     $stockRegistry
     * @param Registry                   $registry
     * @param TaxCalculationn            $taxCalculation
     */
    public function __construct(
        Config $config,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        Registry $registry,
        TaxCalculationn $taxCalculation
    ) {
        $this->config = $config;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->registry = $registry;
        $this->taxCalculation = $taxCalculation;
    }

    /**
     * @param CartRepositoryInterface $cart
     * @param array                   $data
     * @param StoreInterface          $store
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($cart, $data, $store)
    {
        $qty = 0;

        foreach ($data['products'] as $item) {
            $product = $this->getProductById($item['id']);
            $price = $this->getProductPrice($item, $product, $store, $cart);
            $product = $this->setProductData($product, $price, $store);
            $item = $cart->addProduct($product, intval($item['quantity']));
            $item->setOriginalCustomPrice($price);
            $qty += intval($item['quantity']);
        }

        if ($this->config->getEnableBackorders($store->getId())) {
            $this->registry->register('channable_skip_qty_check', true);
        }

        return $qty;
    }

    /**
     * @param $productId
     *
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * @param array                   $item
     * @param ProductInterface        $product
     * @param StoreInterface          $store
     * @param CartRepositoryInterface $cart
     *
     * @return float|int
     */
    private function getProductPrice($item, $product, $store, $cart)
    {
        $price = $item['price'];

        $taxCalculation = $this->config->getNeedsTaxCalulcation('price', $store->getId());
        if (empty($taxCalculation)) {
            $taxClassId = $product->getData('tax_class_id');
            $request = $this->taxCalculation->getRateRequest(
                $cart->getShippingAddress(), $cart->getBillingAddress(), null, $store
            );
            $percent = $this->taxCalculation->getRate($request->setProductClassId($taxClassId));
            $price = ($item['price'] / (100 + $percent) * 100);
        }

        return $price;
    }

    /**
     * @param ProductInterface $product
     * @param double           $price
     * @param StoreInterface   $store
     *
     * @return ProductInterface
     */
    private function setProductData($product, $price, $store)
    {
        if ($this->config->getEnableBackorders($store->getId())) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $stockItem->setUseConfigBackorders(false)->setBackorders(true)->setIsInStock(true);
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

}
