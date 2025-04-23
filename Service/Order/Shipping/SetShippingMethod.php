<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\ShippingFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;

class SetShippingMethod
{
    private array $availableMethods;
    private ConfigProvider $configProvider;
    private RateRequestFactory $rateRequestFactory;
    private ShippingFactory $shippingFactory;

    public function __construct(
        ConfigProvider $configProvider,
        RateRequestFactory $rateRequestFactory,
        ShippingFactory $shippingFactory
    ) {
        $this->configProvider = $configProvider;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->shippingFactory = $shippingFactory;
    }

    /**
     * Set the appropriate shipping method for a quote.
     *
     * @param Quote $quote The quote object.
     * @param float $shippingPriceCal The calculated shipping price.
     * @param ChannableOrderData $orderData Additional Channable order data.
     * @throws LocalizedException
     */
    public function execute(Quote $quote, float $shippingPriceCal, ChannableOrderData $orderData): void
    {
        $storeId = (int)$quote->getStoreId();
        $this->availableMethods = $this->getAllAvailableMethodsForQuote($quote, $shippingPriceCal);

        $selectedMethod = $this->findMatchingShippingMethod($orderData, $storeId)
            ?? $this->configProvider->getFallbackShippingMethod($storeId);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($selectedMethod);

        foreach ($shippingAddress->getShippingRatesCollection() as $rate) {
            $rate->setPrice($shippingPriceCal);
            $rate->setCost($shippingPriceCal);
        }
    }

    /**
     * Retrieve all available shipping methods for the given quote.
     *
     * @param Quote $quote The quote object.
     * @param float $shippingPriceCal The calculated shipping price.
     * @return array The available shipping methods.
     * @throws LocalizedException
     */
    private function getAllAvailableMethodsForQuote(Quote $quote, float $shippingPriceCal): array
    {
        $rateRequest = $this->buildRateRequest($quote, $shippingPriceCal);
        $shippingRates = $this->collectShippingRates($rateRequest);

        $availableMethods = [];
        foreach ($shippingRates->getAllRates() as $rate) {
            $availableMethods[] = $rate->getCarrier() . '_' . $rate->getMethod();
        }

        return $availableMethods;
    }

    /**
     * Build a rate request for shipping calculations.
     *
     * @param Quote $quote The quote object.
     * @param float $shippingPriceCal The calculated shipping price.
     * @return RateRequest The constructed rate request.
     * @throws LocalizedException
     */
    private function buildRateRequest(Quote $quote, float $shippingPriceCal): RateRequest
    {
        $store = $quote->getStore();
        $shippingAddress = $quote->getShippingAddress();
        $total = $quote->getGrandTotal();

        $request = $this->rateRequestFactory->create();
        $request->setAllItems($quote->getAllItems())
            ->setDestCountryId($shippingAddress->getCountryId())
            ->setDestRegionId($shippingAddress->getRegionId())
            ->setDestRegionCode($shippingAddress->getRegionCode())
            ->setDestPostcode($shippingAddress->getPostcode())
            ->setPackageValue($total)
            ->setPackageValueWithDiscount($total)
            ->setPackageQty((int)$quote->getItemsQty())
            ->setStoreId((int)$store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setBaseCurrency($store->getBaseCurrency())
            ->setPackageCurrency($store->getCurrentCurrency())
            ->setBaseSubtotalInclTax($total)
            ->setFreeShipping($shippingPriceCal <= 0);

        if (!empty($shippingAddress->getWeight())) {
            $request->setPackageWeight($shippingAddress->getWeight());
        }

        return $request;
    }

    /**
     * Collect shipping rates for the given rate request.
     *
     * @param RateRequest $rateRequest The rate request object.
     * @return Result|null The collected shipping rates or null if none are found.
     */
    private function collectShippingRates(RateRequest $rateRequest): ?Result
    {
        $shipping = $this->shippingFactory->create();
        return $shipping->collectRates($rateRequest)->getResult();
    }

    /**
     * Find a matching shipping method for the given order data and store.
     *
     * @param ChannableOrderData $orderData The Channable order data.
     * @param int $storeId The store ID.
     * @return string|null The matching shipping method or null if none are found.
     */
    public function findMatchingShippingMethod(ChannableOrderData $orderData, int $storeId): ?string
    {
        if ($shippingMethod = $this->findShippingMethodUsingOrderData($orderData, $storeId)) {
            return $shippingMethod;
        }

        $defaultMethod = $this->configProvider->getDefaultShippingMethod($storeId);
        if ($defaultMethod == 'channable_custom') {
            return $this->getCustomShippingMethod($storeId);
        }

        return $this->isMethodAvailable($defaultMethod) ? $defaultMethod : null;
    }

    /**
     * Find a shipping method using Channable order data and mapping.
     *
     * @param ChannableOrderData $orderData The Channable order data.
     * @param int $storeId The store ID.
     * @return string|null The mapped shipping method or null if none are found.
     */
    private function findShippingMethodUsingOrderData(ChannableOrderData $orderData, int $storeId): ?string
    {
        $advancedMapping = $this->configProvider->getAdvancedShippingMapping($storeId);

        if (empty($advancedMapping)) {
            return null;
        }

        foreach ($advancedMapping as $mapping) {
            $channelMatches = strcasecmp($mapping['channel'], $orderData->getChannelName()) === 0;
            $carrierMatches = strcasecmp($mapping['channable_carrier'], $orderData->getShipmentMethod()) === 0;

            if ($channelMatches && $carrierMatches) {
                $method = $mapping['method'];
                return $this->isMethodAvailable($method) ? $method : null;
            }
        }

        return null;
    }

    /**
     * Check if a shipping method is available.
     *
     * @param string|null $method The shipping method to check.
     * @return bool True if the method is available, false otherwise.
     */
    private function isMethodAvailable(?string $method): bool
    {
        return !empty($method) && in_array($method, $this->availableMethods, true);
    }

    /**
     * Retrieve the best custom shipping method based on priorities.
     *
     * @param int $storeId The store ID.
     * @return string|null The prioritized custom shipping method or null if none are found.
     */
    private function getCustomShippingMethod(int $storeId): ?string
    {
        $prioritizedMethods = $this->configProvider->getCustomShippingMethodLogic($storeId);
        $highestPriority = -1;
        $selectedMethod = null;

        foreach ($this->availableMethods as $method) {
            $priority = $prioritizedMethods[$method] ?? null;

            if ($priority !== null && $priority > $highestPriority) {
                $highestPriority = $priority;
                $selectedMethod = $method;
            }
        }

        return $selectedMethod;
    }
}
