<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order;

/**
 * Flat DTO for a single Channable order record (API-safe).
 *
 * @api
 */
interface ItemInterface
{
    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @return int
     */
    public function getChannableId(): int;

    /**
     * @return string|null
     */
    public function getChannelId(): ?string;

    /**
     * @return string|null
     */
    public function getChannelName(): ?string;

    /**
     * @return string|null
     */
    public function getChannelLabel(): ?string;

    /**
     * @return int|null
     */
    public function getMagentoOrderId(): ?int;

    /**
     * @return string|null
     */
    public function getMagentoIncrementId(): ?string;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @return string|null
     */
    public function getChannableOrderStatus(): ?string;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
