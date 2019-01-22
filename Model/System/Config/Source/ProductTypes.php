<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ProductTypes
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class ProductTypes implements ArrayInterface
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
                [
                    'value' => '',
                    'label' => __('Simple & Parent Products')
                ],
                [
                    'value' => 'simple',
                    'label' => __('Only Simple Products')
                ],
                [
                    'value' => 'parent',
                    'label' => __('Only Parent Products')
                ]
            ];
        }
        return $this->options;
    }
}
