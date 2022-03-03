<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

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
     * @var TaxCalculationn
     */
    private $taxCalculation;

    /**
     * CalculatePrice constructor.
     * @param ConfigProvider $configProvider
     * @param TaxCalculationn $taxCalculation
     */
    public function __construct(
        ConfigProvider $configProvider,
        TaxCalculationn $taxCalculation
    ) {
        $this->configProvider = $configProvider;
        $this->taxCalculation = $taxCalculation;
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
        $taxCalculation = $this->configProvider->getNeedsTaxCalulcation('shipping', (int)$store->getId());
        $shippingPriceCal = (float) $orderData['price']['shipping'];

        if (empty($taxCalculation)) {
            $shippingAddress = $quote->getShippingAddress();
            $billingAddress = $quote->getBillingAddress();
            $taxRateId = $this->configProvider->getTaxClassShipping((int)$store->getId());
            $request = $this->taxCalculation->getRateRequest($shippingAddress, $billingAddress, null, $store);
            $percent = $this->taxCalculation->getRate($request->setData('product_tax_class_id', $taxRateId));
            $shippingPriceCal = ($orderData['price']['shipping'] / (100 + $percent) * 100);
        }

        return $shippingPriceCal;
    }
}
