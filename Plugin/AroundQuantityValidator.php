<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\CatalogInventory\Observer\QuantityValidatorObserver;

/**
 * Plugin for QuantityValidatorObserver
 */
class AroundQuantityValidator
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
     * @param QuantityValidatorObserver $subject
     * @param \Closure $proceed
     * @param Observer $observer
     * @return mixed
     */
    public function aroundExecute(
        QuantityValidatorObserver $subject,
        $proceed,
        Observer $observer
    ) {
        if ($this->checkoutSession->getChannableSkipQtyCheck()) {
            return false;
        }

        return $proceed($observer);
    }
}
