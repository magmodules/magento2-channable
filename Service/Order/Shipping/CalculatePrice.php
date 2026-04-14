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
use Magmodules\Channable\Service\Order\Currency\Converter as CurrencyConverter;

/**
 * Get shipping price for quote
 */
class CalculatePrice
{
    private ConfigProvider $configProvider;
    private TaxCalculation $taxCalculation;
    private CurrencyConverter $currencyConverter;

    public function __construct(
        ConfigProvider $configProvider,
        TaxCalculation $taxCalculation,
        CurrencyConverter $currencyConverter
    ) {
        $this->configProvider = $configProvider;
        $this->taxCalculation = $taxCalculation;
        $this->currencyConverter = $currencyConverter;
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

        $storeId = (int)$quote->getStoreId();
        $orderCurrency = $orderData['price']['currency'] ?? '';

        // Convert shipping from order currency to base currency
        $amount = $this->currencyConverter->convertToBase($amount, $orderCurrency, $storeId);

        if (!$this->configProvider->shippingIncludesTax((int)$store->getId())) {
            $taxRateId = $this->configProvider->getTaxClassShipping((int)$store->getId());
            $request = $this->taxCalculation->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                $quote->getCustomerTaxClassId(),
                $store,
                $quote->getCustomerId()
            );
            $percent = $this->taxCalculation->getRate(
                $request->setData('product_class_id', $taxRateId)
            );
            $amount = ($amount / (100 + $percent) * 100);
        }

        return $amount;
    }
}
