<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Shipping\Model\Config\Source\Allmethods;

/**
 * Class ShippingMethods
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class ShippingMethods extends Allmethods
{

    /**
     * Return array of carriers.
     * If $isActiveOnlyFlag is set to true, will return only active carriers
     *
     * @param bool $isActiveOnlyFlag
     *
     * @return array
     */
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $methods = parent::toOptionArray();
        if (isset($methods['channable']['value'])) {
            $methods['channable']['value'][] = [
                'value' => 'channable_custom',
                'label' => '[channable] Use Custom Logic'
            ];
        }
        return $methods;
    }
}
