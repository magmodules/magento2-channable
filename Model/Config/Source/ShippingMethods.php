<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\Source;

use Magento\Shipping\Model\Config\Source\Allmethods;

/**
 * ShippingMethods Option Source model
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
    public function toOptionArray($isActiveOnlyFlag = false): array
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
