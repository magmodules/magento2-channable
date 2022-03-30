<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\InventoryConfiguration\Model\StockItemConfiguration;

/**
 * Plugin for StockItemConfiguration
 */
class AfterStockItemConfiguration
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AfterStockItemConfiguration constructor.
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Bypass Stock Item Configuration
     *
     * Out-of-stock products: depending on configuration setting
     * LVB Orders: these orders are shipped from external warehouse
     *
     * @param StockItemConfiguration $subject
     * @param bool                   $result
     *
     * @return bool
     */
    public function afterIsManageStock(
        StockItemConfiguration $subject,
        bool $result
    ) {
        if ($this->checkoutSession->getChannableSkipReservation()) {
            return false;
        }

        return $result;
    }
}
