<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class CronFrequency
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class CronFrequency implements ArrayInterface
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
                    'value' => '0 0 * * *',
                    'label' => __('Daily at 0:00')
                ],
                [
                    'value' => '0 */6 * * *',
                    'label' => __('Every 6 hours')
                ],
                [
                    'value' => '0 */4 * * *',
                    'label' => __('Every 4 hours')
                ],
                [
                    'value' => '0 */2 * * *',
                    'label' => __('Every 2 hours')
                ],
                [
                    'value' => '0 * * * *',
                    'label' => __('Every hour')
                ],
                [
                    'value' => '*/30 * * * *',
                    'label' => __('Every 30 minutes')
                ],
                [
                    'value' => '*/15 * * * *',
                    'label' => __('Every 15 minutes')
                ],
                [
                    'value' => '*/5 * * * *',
                    'label' => __('Every 5 minutes')
                ],
                [
                    'value' => 'custom',
                    'label' => __('Custom')
                ],
            ];
        }
        return $this->options;
    }
}
