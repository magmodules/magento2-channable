<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Sales\Model\Order;

/**
 * Plugin to show pickup location instead of shipping description
 */
class AfterGetShippingDescription
{
    /**
     * @param Order $order
     * @param $result
     * @return float|mixed|null
     */
    public function afterGetShippingDescription(
        Order $order,
        $result
    ) {
        if ($channablePickupLocation = $order->getData('channable_pickup_location')) {
            $result =  'Pickup location: ' . $channablePickupLocation;
        }
        return $result;
    }
}
