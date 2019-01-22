<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class InvalidationModus
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class InvalidationModus implements ArrayInterface
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
                    'label' => __('Observer')
                ],
                [
                    'value' => 'cron',
                    'label' => __('Cron')
                ]
            ];
        }
        return $this->options;
    }
}
