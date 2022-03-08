<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * ReturnLabel source class
 */
class ReturnLabel implements OptionSourceInterface
{

    public const OPTIONS = [
        'no' => 'No',
        'regex' => 'Yes, use regex',
    ];

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
            foreach (self::OPTIONS as $key => $option) {
                $this->options[] = ['value' => $key, 'label' => __($option)];
            }
        }
        return $this->options;
    }
}
