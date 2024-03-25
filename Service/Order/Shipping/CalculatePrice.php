<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Get shipping price for quote
 */
class CalculatePrice
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceManager;

    /**
     * CalculatePrice constructor.
     * @param ConfigProvider $configProvider
     * @param TaxCalculation $taxCalculation
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        TaxCalculation $taxCalculation,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceManager
    ) {
        $this->configProvider = $configProvider;
        $this->taxCalculation = $taxCalculation;
        $this->storeManager = $storeManager;
        $this->priceManager = $priceManager;
    }

    /**
     * Calculate shipping price based on tax settings
     *
     * @param Quote $quote
     * @param array $orderData
     * @param StoreInterface $store
     *
     * @return float
     */
    public function execute(Quote $quote, array $orderData, StoreInterface $store): float
    {
        $amount = (float)$orderData['price']['shipping'];
        if ($amount == 0) {
            return $amount;
        }

        $baseCurrency = $this->getBaseCurrency($quote);
        if ($baseCurrency && $baseCurrency != $orderData['price']['currency']) {
            $rate = $this->priceManager->convert($amount, $quote->getStoreId()) / $amount;
            $amount = $amount / $rate;
        }

        $taxCalculation = $this->configProvider->getNeedsTaxCalulcation('shipping', (int)$store->getId());
        if (empty($taxCalculation)) {
            $shippingAddress = $quote->getShippingAddress();
            $billingAddress = $quote->getBillingAddress();
            $taxRateId = $this->configProvider->getTaxClassShipping((int)$store->getId());
            $request = $this->taxCalculation->getRateRequest($shippingAddress, $billingAddress, null, $store);
            $percent = $this->taxCalculation->getRate($request->setData('product_tax_class_id', $taxRateId));
            $amount = ($amount / (100 + $percent) * 100);
        }

        return $amount;
    }

    /**
     * @param Quote $quote
     * @return string|null
     */
    private function getBaseCurrency(Quote $quote): ?string
    {
        try {
            return $this->storeManager->getStore($quote->getStoreId())->getBaseCurrencyCode();
        } catch (\Exception $exception) {
            return null;
        }
    }
}