<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;

class AfterCheckQty
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AfterCheckQty constructor.
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Skip CheckQty for adding products to Quote for LVB Orders,
     * or with "Enable order for out of stock items" enabled.
     *
     * LVB Orders are shipped from external warehouse and have no impact on stock movement,
     * thus should also be skipped from this check.
     *
     * @param \Magento\CatalogInventory\Model\StockState $subject
     * @param                                            $result
     *
     * @return mixed
     */
    public function afterCheckQty(\Magento\CatalogInventory\Model\StockState $subject, $result)
    {
        if ($this->checkoutSession->getChannableSkipQtyCheck()) {
            return true;
        }

        return $result;
    }
}
