<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Config\System;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Returns config group interface
 */
interface ReturnsInterface extends ItemupdateInterface
{

    public const XML_PATH_RETURNS_ENABLE = 'magmodules_channable_marketplace/returns/enable';

    /**
     * Check whether returns are enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isReturnsEnabled(int $storeId = null): bool;

    /**
     * Returns webhook url builder
     *
     * @param int $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getReturnsWebhookUrl(int $storeId): string;
}
