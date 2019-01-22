<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source\Grouped;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Price
 *
 * @package Magmodules\Channable\Model\System\Config\Source\Grouped
 */
class Price implements ArrayInterface
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
                ['value' => '', 'label' => __('Minimum Price (Recommended)')],
                ['value' => 'max', 'label' => __('Maximum Price')],
                ['value' => 'total', 'label' => __('Total Price')]
            ];
        }
        return $this->options;
    }
}
