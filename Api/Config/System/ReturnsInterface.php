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
    public const XML_PATH_RETURNS_CREDITMEMO = 'magmodules_channable_marketplace/returns/show_on_creditmemo';
    public const XML_PATH_RETURNS_AUTO_MATCH = 'magmodules_channable_marketplace/returns/auto_update';
    public const XML_PATH_GTIN_ATTRIBUTE = 'magmodules_channable/data/ean_attribute';

    /**
     * Check whether returns are enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isReturnsEnabled(int $storeId = null): bool;

    /**
     * Check whether we should show option to accept returns on creditmemo creation
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function showOnCreditmemoCreation(int $storeId = null): bool;

    /**
     * Returns webhook url builder
     *
     * @param int $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getReturnsWebhookUrl(int $storeId): string;

    /**
     * Check whether returns should be automatically accepted on creditmemo creation
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function autoUpdateReturnsOnCreditmemo(int $storeId = null): bool;

    /**
     * Returns attribute set as GTIN
     *
     * @param int|null $storeId
     * @return string
     */
    public function getGtinAttribute(int $storeId = null): string;
}
