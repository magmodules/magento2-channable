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
    public const STATUS_NEW = 'new';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REPAIRED = 'repaired';
    public const STATUS_EXCHANGED = 'exchanged';
    public const STATUS_KEEPS = 'keeps';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETE = 'complete';

    private const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_REPAIRED,
        self::STATUS_EXCHANGED,
        self::STATUS_KEEPS,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETE,
    ];

    public function toOptionArray(): array
    {
        return array_map(function (string $status) {
            return [
                'value' => $status,
                'label' => __(ucfirst($status)),
            ];
        }, self::STATUSES);
    }
}
