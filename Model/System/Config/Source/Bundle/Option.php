<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source\Bundle;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Option
 *
 * @package Magmodules\Channable\Model\System\Config\Source\Bundle
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
                ['value' => 'parent', 'label' => __('Only Bundle Product (Recommended)')],
                ['value' => 'simple', 'label' => __('Only Linked Simple Products')],
                ['value' => 'both', 'label' => __('Bundle and Linked Simple Products')]
            ];
        }
        return $this->options;
    }
}
