<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Currency;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Convert order prices between currencies
 */
class Converter
{
    private const PRICE_PRECISION = 2;

    private StoreManagerInterface $storeManager;
    private array $rateCache = [];

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Convert amount from order currency to base currency
     *
     * @param float $amount Amount in order currency
     * @param string $orderCurrency Currency code from JSON (e.g., PLN)
     * @param int $storeId
     * @return float Amount in base currency
     */
    public function convertToBase(float $amount, string $orderCurrency, int $storeId): float
    {
        if ($amount == 0) {
            return 0.0;
        }

        $baseCurrency = $this->getBaseCurrencyCode($storeId);
        if ($baseCurrency === null || $baseCurrency === $orderCurrency) {
            return $amount;
        }

        $rate = $this->getRate($storeId, $orderCurrency);
        if ($rate == 0) {
            return $amount;
        }

        return round($amount / $rate, self::PRICE_PRECISION);
    }

    /**
     * Get exchange rate (base to order currency)
     *
     * @param int $storeId
     * @param string $orderCurrency
     * @return float
     */
    public function getRate(int $storeId, string $orderCurrency): float
    {
        $cacheKey = $storeId . '_' . $orderCurrency;
        if (isset($this->rateCache[$cacheKey])) {
            return $this->rateCache[$cacheKey];
        }

        try {
            $store = $this->storeManager->getStore($storeId);
            $baseCurrency = $store->getBaseCurrency();
            $rate = $baseCurrency->getRate($orderCurrency);
            $this->rateCache[$cacheKey] = $rate ? (float)$rate : 1.0;
        } catch (\Throwable $e) {
            $this->rateCache[$cacheKey] = 1.0;
        }

        return $this->rateCache[$cacheKey];
    }

    /**
     * Check if conversion is needed
     *
     * @param string $orderCurrency
     * @param int $storeId
     * @return bool
     */
    public function needsConversion(string $orderCurrency, int $storeId): bool
    {
        $baseCurrency = $this->getBaseCurrencyCode($storeId);
        return $baseCurrency !== null && $baseCurrency !== $orderCurrency;
    }

    /**
     * Get base currency code for store
     *
     * @param int $storeId
     * @return string|null
     */
    public function getBaseCurrencyCode(int $storeId): ?string
    {
        try {
            return $this->storeManager->getStore($storeId)->getBaseCurrencyCode();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
