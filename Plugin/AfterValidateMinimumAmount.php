<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;

class AfterValidateMinimumAmount
{
    /** @var CheckoutSession $checkoutSession */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Disable Minimum Order Amount for Channable orders.
     *
     * @param Quote $subject
     * @param bool $result
     *
     * @return bool
     */
    public function afterValidateMinimumAmount(Quote $subject, bool $result): bool
    {
        return (bool) $this->checkoutSession->getChannableEnabled() === true ? true : $result;
    }
}
