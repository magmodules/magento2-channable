<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionService;

/**
 * Plugin for SourceDeductionService
 */
class AroundSourceDeductionService
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AroundIsAnySourceItemInStockCondition constructor.
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Skip QuantityValidator for adding products to order.
     *
     * Out-of-stock products: depending on configuration setting
     * LVB Orders: these orders are shipped from external warehouse
     *
     * @param SourceDeductionService $subject
     * @param \Closure $proceed
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @return mixed
     */
    public function aroundExecute(
        SourceDeductionService $subject,
        $proceed,
        SourceDeductionRequestInterface $sourceDeductionRequest
    ) {
        if ($this->checkoutSession->getChannableSkipReservation()) {
            return;
        }

        return $proceed($sourceDeductionRequest);
    }
}
