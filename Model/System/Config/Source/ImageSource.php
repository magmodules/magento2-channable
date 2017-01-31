<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ImageSource implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'image', 'label' => __('Only Base Image')],
            ['value' => 'small_image', 'label' => __('Only Small Image')],
            ['value' => 'thumbnail', 'label' => __('Only Thumbnail')],
            ['value' => '', 'label' => __('All Images')],
        ];
    }
}
