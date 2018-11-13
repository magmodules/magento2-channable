<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Renderer\Returns;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Status
 *
 * @package Magmodules\Channable\Model\Renderer\Returns
 */
class Status implements ArrayInterface
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
                    'value' => 'new',
                    'label' => __('New')
                ],
                [
                    'value' => 'accepted',
                    'label' => __('Accepted')
                ],
                [
                    'value' => 'rejected',
                    'label' => __('Rejected')
                ],
                [
                    'value' => 'repaired',
                    'label' => __('Repaired')
                ],
                [
                    'value' => 'exchanged',
                    'label' => __('Exchanged')
                ],
                [
                    'value' => 'keeps',
                    'label' => __('Keeps')
                ],
                [
                    'value' => 'cancelled',
                    'label' => __('Cancelled')
                ],
            ];
        }
        return $this->options;
    }
}
