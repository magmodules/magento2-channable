<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class Channable extends AbstractMethod
{
    public const CODE = 'channable';
    protected $_code = self::CODE;
    protected $_isOffline = true;
    protected $_canUseCheckout = false;
    protected $_canUseInternal = true;
    protected $_infoBlockType = 'Magmodules\Channable\Block\Info\Channable';
}
