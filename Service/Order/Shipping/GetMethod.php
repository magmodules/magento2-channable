<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\ShippingFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Get shipping method for quote
 */
class GetMethod
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var RateRequestFactory
     */
    private $rateRequestFactory;

    /**
     * @var ShippingFactory
     */
    private $shippingFactory;

    /**
     * GetMethod constructor.
     * @param ConfigProvider $configProvider
     * @param RateRequestFactory $rateRequestFactory
     * @param ShippingFactory $shippingFactory
     */
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
     * Get the preffered shipping method
     *
     * @param Quote $quote
     * @param StoreInterface $store
     * @param int $itemCount
     * @param float $shippingPriceCal
     *
     * @return string
     * @throws LocalizedException
     */
    public function execute(Quote $quote, StoreInterface $store, int $itemCount, float $shippingPriceCal): string
    {
        $shippingMethod = $this->configProvider->getDefaultShippingMethod((int)$store->getId());
        $shippingMethodFallback = $this->configProvider->getFallbackShippingMethod((int)$store->getId());

        $destCountryId = $quote->getShippingAddress()->getCountryId();
        $destPostcode = $quote->getShippingAddress()->getPostcode();
        $total = $quote->getGrandTotal();

        $request = $this->rateRequestFactory->create();
        $request->setAllItems($quote->getAllItems());
        $request->setDestCountryId($destCountryId);
        $request->setDestPostcode($destPostcode);
        $request->setPackageValue($total);
        $request->setPackageValueWithDiscount($total);
        $request->setPackageQty($itemCount);
        $request->setStoreId((int)$store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());
        $request->setLimitCarrier('');
        $request->setBaseSubtotalInclTax($total);
        $request->setFreeShipping($shippingPriceCal <= 0);
        if (!empty($quote->getShippingAddress()->getWeight())) {
            $request->setPackageWeight($quote->getShippingAddress()->getWeight());
        }

        $shipping = $this->shippingFactory->create();
        $result = $shipping->collectRates($request)->getResult();

        if ($result) {
            $shippingRates = $result->getAllRates();
            if ($shippingMethod != 'channable_custom') {
                foreach ($shippingRates as $shippingRate) {
                    $method = $shippingRate->getCarrier() . '_' . $shippingRate->getMethod();
                    if ($method == $shippingMethod) {
                        return $shippingMethod;
                    }
                }
            } else {
                $priority = -1;
                $customCarrier = null;
                $prioritizedMethods = $this->configProvider->getCustomShippingMethodLogic((int)$store->getId());
                foreach ($shippingRates as $shippingRate) {
                    $method = $shippingRate->getCarrier() . '_' . $shippingRate->getMethod();
                    if (isset($prioritizedMethods[$method]) && $priority < $prioritizedMethods[$method]) {
                        $customCarrier = $method;
                        $priority = $prioritizedMethods[$method];
                    }
                }
                if ($customCarrier !== null) {
                    return $customCarrier;
                }
            }
        }

        return $shippingMethodFallback;
    }
}
