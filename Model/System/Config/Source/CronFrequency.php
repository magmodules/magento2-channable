<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CronFrequency implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '*/15 * * * *',
                'label' => __('Every 15 minutes')
            ],
            [
                'value' => '*/10 * * * *',
                'label' => __('Every 10 minutes')
            ],
            [
                'value' => '*/5 * * * *',
                'label' => __('Every 5 minutes')
            ],
            [
                'value' => '* * * * *',
                'label' => __('Every minute')
            ],
            [
                'value' => 'custom',
                'label' => __('Custom')
            ],
        ];
    }
}
