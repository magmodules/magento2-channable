<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magmodules\Channable\Api\Config\System\ReturnsInterface;

/**
 * Returns provider class
 */
class ReturnsRepository extends ItemupdateRepository implements ReturnsInterface
{

    /**
     * {@inheritDoc}
     */
    public function isReturnsEnabled(int $storeId = null): bool
    {
        return (bool)$this->getStoreValue(self::XML_PATH_RETURNS_ENABLE, (int)$storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnsWebhookUrl(int $storeId): string
    {
        $url = $this->storeManager->getStore((int)$storeId)->getBaseUrl();
        return $url . sprintf('channable/returns/hook/store/%s/code/%s', $storeId, $this->getToken());
    }

    /**
     * @inheritDoc
     */
    public function autoUpdateReturnsOnCreditmemo(int $storeId = null): bool
    {
        return (bool)$this->getStoreValue(self::XML_PATH_RETURNS_AUTO_MATCH, (int)$storeId);
    }

    /**
     * @inheritDoc
     */
    public function showOnCreditmemoCreation(int $storeId = null): bool
    {
        return $this->getStoreValue(self::XML_PATH_RETURNS_CREDITMEMO, $storeId)
            && $this->isReturnsEnabled($storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getGtinAttribute(int $storeId = null): string
    {
        return $this->getStoreValue(self::XML_PATH_GTIN_ATTRIBUTE, (int)$storeId) ?: 'sku';
    }
}
