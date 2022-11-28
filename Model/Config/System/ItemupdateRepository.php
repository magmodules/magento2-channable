<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magmodules\Channable\Api\Config\System\ItemupdateInterface;

/**
 * Item Update provider class
 */
class ItemupdateRepository extends BaseRepository implements ItemupdateInterface
{

    /**
     * @inheritDoc
     */
    public function isItemCronEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_ITEM_UPDATE_CRON);
    }

    /**
     * @inheritDoc
     */
    public function getItemUpdateStoreIds(): array
    {
        $storeIds = [];
        if (!$this->isEnabled()) {
            return $storeIds;
        }

        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if (!$this->isItemUpdateEnabled((int)$store->getId())) {
                continue;
            }
            if (!$this->getItemUpdateWebhookUrl((int)$store->getId())) {
                continue;
            }
            $storeIds[] = $store->getId();
        }

        return $storeIds;
    }

    /**
     * @inheritDoc
     */
    public function isItemUpdateEnabled(int $storeId = null): bool
    {
        return (bool)$this->getStoreValue(self::XML_PATH_ITEM_UPDATE_ENABLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getItemUpdateWebhookUrl(int $storeId): ?string
    {
        return $this->getStoreValue(self::XML_PATH_ITEM_UPDATE_WEBHOOK, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getRunLimit(): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_ITEM_UPDATE_LIMIT);
    }
}
