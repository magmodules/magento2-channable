<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Config\System;

/**
 * Itemupdate group interface
 */
interface ItemupdateInterface
{

    /** General Group */
    const XML_PATH_ITEMUPDATE_ENABLE = 'magmodules_channable_marketplace/item/enable';
    const XML_PATH_ITEMUPDATE_WEBHOOK = 'magmodules_channable_marketplace/item/webhook';

    /**
     * Enabled flag for Itemupdate Import.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isItemupdateEnabled(int $storeId = null): bool;

    /**
     * Returns webhook url
     *
     * @param int $storeId
     *
     * @return null|string
     */
    public function getItemupdateWebhookUrl(int $storeId): ?string;
}
