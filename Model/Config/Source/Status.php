<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Status source class
 */
class Status implements OptionSourceInterface
{

    const NEW = 'new';
    const IMPORTED = 'imported';
    const ERROR = 'error';
    const FAILED = 'failed';

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
                ['value' => self::NEW, 'label' => ucfirst(self::NEW)],
                ['value' => self::IMPORTED, 'label' => ucfirst(self::IMPORTED)],
                ['value' => self::ERROR, 'label' => ucfirst(self::ERROR)],
                ['value' => self::FAILED, 'label' => ucfirst(self::FAILED)],
            ];
        }
        return $this->options;
    }
}
