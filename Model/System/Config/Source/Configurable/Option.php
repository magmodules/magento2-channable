<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source\Configurable;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Option
 *
 * @package Magmodules\Channable\Model\System\Config\Source\Configurable
 */
class Option implements ArrayInterface
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
                ['value' => '', 'label' => __('No')],
                ['value' => 'parent', 'label' => __('Only Configurable Product')],
                ['value' => 'simple', 'label' => __('Only Linked Simple Products (Recommended)')],
                ['value' => 'both', 'label' => __('Configurable and Linked Simple Products')]
            ];
        }
        return $this->options;
    }
}
