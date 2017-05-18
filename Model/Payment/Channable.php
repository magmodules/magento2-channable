<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class Channable extends AbstractMethod
{

    const CODE = 'channable';
    protected $_code = self::CODE;
    protected $_isOffline = true;
    protected $_canUseCheckout = false;
    protected $_canUseInternal = true;

    public function isAvailable(CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }
}
