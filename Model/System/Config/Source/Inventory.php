<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Inventory
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class Inventory implements ArrayInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => 'qty', 'label' => __('QTY')],
                ['value' => 'min_sale_qty', 'label' => __('Minimum Sales QTY')],
                ['value' => 'qty_increments', 'label' => __('QTY Increments')],
                ['value' => 'manage_stock', 'label' => __('Manage Stock')],
                ['value' => 'backorders', 'label' => __('Backorder Enabled')],
            ];
        }
        return $this->options;
    }
}
