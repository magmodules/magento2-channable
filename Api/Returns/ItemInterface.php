<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns;

/**
 * Flat DTO for a single Channable return record (API-safe).
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
    public function getChannelReturnId(): ?string;

    /**
     * @return string|null
     */
    public function getChannelOrderId(): ?string;

    /**
     * @return string|null
     */
    public function getChannelOrderIdInternal(): ?string;

    /**
     * @return string|null
     */
    public function getPlatformOrderId(): ?string;

    /**
     * @return int|null
     */
    public function getMagentoOrderId(): ?int;

    /**
     * @return string|null
     */
    public function getMagentoIncrementId(): ?string;

    /**
     * @return int|null
     */
    public function getMagentoCreditmemoId(): ?int;

    /**
     * @return string|null
     */
    public function getMagentoCreditmemoIncrementId(): ?string;

    /**
     * @return string|null
     */
    public function getCustomerName(): ?string;

    /**
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
