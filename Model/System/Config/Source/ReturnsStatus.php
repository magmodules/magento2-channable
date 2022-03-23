<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * ReturnsStatus Option Source model
 */
class ReturnsStatus implements OptionSourceInterface
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
    public function toOptionArray(): array
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
