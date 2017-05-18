<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CategoryTypeList implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'in', 'label' => __('Include by Category')],
            ['value' => 'nin', 'label' => __('Exclude by Category')],
        ];
    }
}
