<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ProductCondition
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class ProductCondition implements ArrayInterface
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
                ['value' => 'new', 'label' => __('New')],
                ['value' => 'refurbished', 'label' => __('Refurbished')],
                ['value' => 'used', 'label' => __('Uses')],
            ];
        }
        return $this->options;
    }
}
