<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ProductVisibility implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Not Visible Individually')],
            ['value' => '2', 'label' => __('Catalog')],
            ['value' => '3', 'label' => __('Search')],
            ['value' => '4', 'label' => __('Catalog, Search')],
        ];
    }
}
