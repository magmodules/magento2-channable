<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Inventory implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'qty', 'label' => __('QTY')],
            ['value' => 'min_sale_qty', 'label' => __('Minimum Sales QTY')],
            ['value' => 'qty_increments', 'label' => __('QTY Increments')],
            ['value' => 'manage_stock', 'label' => __('Manage Stock')],
        ];
    }
}
