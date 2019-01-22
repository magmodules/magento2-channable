<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ProductVisibility
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class ProductVisibility implements ArrayInterface
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
                ['value' => '1', 'label' => __('Not Visible Individually')],
                ['value' => '2', 'label' => __('Catalog')],
                ['value' => '3', 'label' => __('Search')],
                ['value' => '4', 'label' => __('Catalog, Search')],
            ];
        }
        return $this->options;
    }
}
