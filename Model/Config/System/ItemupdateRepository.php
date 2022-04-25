<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magmodules\Channable\Api\Config\System\ItemupdateInterface;

/**
 * Itemupdate provider class
 */
class ItemupdateRepository extends BaseRepository implements ItemupdateInterface
{

    /**
     * {@inheritDoc}
     */
    public function isItemupdateEnabled(int $storeId = null): bool
    {
        return (bool)$this->getStoreValue(self::XML_PATH_ITEMUPDATE_ENABLE, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getItemupdateWebhookUrl(int $storeId): ?string
    {
        return $this->getStoreValue(self::XML_PATH_ITEMUPDATE_WEBHOOK, $storeId);
    }
}
