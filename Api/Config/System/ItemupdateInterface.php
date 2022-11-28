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
    const XML_PATH_ITEM_UPDATE_ENABLE = 'magmodules_channable_marketplace/item/enable';
    const XML_PATH_ITEM_UPDATE_WEBHOOK = 'magmodules_channable_marketplace/item/webhook';
    const XML_PATH_ITEM_UPDATE_CRON = 'magmodules_channable_marketplace/item/cron';
    const XML_PATH_ITEM_UPDATE_LIMIT = 'magmodules_channable_marketplace/item/limit';

    /**
     * Enabled flag for Item update Import.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isItemUpdateEnabled(int $storeId = null): bool;

    /**
     * Return array of store ids where item update is enabled
     *
     * @return array
     */
    public function getItemUpdateStoreIds(): array;

    /**
     * Enabled flag for Item update cron.
     *
     * @return bool
     */
    public function isItemCronEnabled(): bool;

    /**
     * Returns the maximum number of items to update per run.
     *
     * @return int
     */
    public function getRunLimit(): int;

    /**
     * Returns webhook url
     *
     * @param int $storeId
     *
     * @return null|string
     */
    public function getItemUpdateWebhookUrl(int $storeId): ?string;
}
