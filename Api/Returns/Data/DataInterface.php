<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for Channable Returns
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{

    public const ENTITY_ID = 'entity_id';
    public const STORE_ID = 'store_id';
    public const ORDER_ID = 'order_id';
    public const CHANNEL_NAME = 'channel_name';
    public const CHANNEL_ID = 'channel_id';
    public const CHANNABLE_ID = 'channable_id';
    public const MAGENTO_ORDER_ID = 'magento_order_id';
    public const MAGENTO_INCREMENT_ID = 'magento_increment_id';
    public const MAGENTO_CREDITMEMO_ID = 'magento_creditmemo_id';
    public const MAGENTO_CREDITMEMO_INCREMENT_ID = 'magento_creditmemo_increment_id';
    public const ITEM = 'item';
    public const CUSTOMER_NAME = 'customer_name';
    public const CUSTOMER = 'customer';
    public const ADDRESS = 'address';
    public const REASON = 'reason';
    public const COMMENT = 'comment';
    public const STATUS = 'status';
    public const CHANNEL_RETURN_ID = 'channel_return_id';
    public const CHANNEL_ORDER_ID = 'channel_order_id';
    public const CHANNEL_ORDER_ID_INTERNAL = 'channel_order_id_internal';
    public const PLATFORM_ORDER_ID = 'platform_order_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return string
     */
    public function getChannelName(): string;

    /**
     * @param $channelName
     * @return $this
     */
    public function setChannelName($channelName): self;

    /**
     * @return string
     */
    public function getChannelId(): string;

    /**
     * @param $channelId
     * @return $this
     */
    public function setChannelId($channelId): self;

    /**
     * @return int
     */
    public function getChannableId(): int;

    /**
     * @param int $channableId
     * @return $this
     */
    public function setChannableId(int $channableId): self;

    /**
     * @return int|null
     */
    public function getMagentoOrderId(): ?int;

    /**
     * @param int|null $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(?int $magentoOrderId): self;

    /**
     * @return string
     */
    public function getMagentoIncrementId(): string;

    /**
     * @param string $magentoIncrementId
     * @return $this
     */
    public function setMagentoIncrementId(string $magentoIncrementId): self;

    /**
     * @return int|null
     */
    public function getMagentoCreditmemoId(): ?int;

    /**
     * @param int|null $magentoCreditmemoId
     * @return $this
     */
    public function setMagentoCreditmemoId(?int $magentoCreditmemoId): self;

    /**
     * @return string
     */
    public function getMagentoCreditmemoIncrementId(): string;

    /**
     * @param string $magentoIncrementId
     * @return $this
     */
    public function setMagentoCreditmemoIncrementId(string $magentoIncrementId): self;

    /**
     * @return array
     */
    public function getItem(): array;

    /**
     * @param array $item
     * @return $this
     */
    public function setItem(array $item): self;

    /**
     * @return string
     */
    public function getCustomerName(): string;

    /**
     * @param string $customerName
     * @return $this
     */
    public function setCustomerName(string $customerName): self;

    /**
     * @return array
     */
    public function getCustomer(): array;

    /**
     * @param array $customer
     * @return $this
     */
    public function setCustomer(array $customer): self;

    /**
     * @return array
     */
    public function getAddress(): array;

    /**
     * @param array $address
     * @return $this
     */
    public function setAddress(array $address): self;

    /**
     * @return string
     */
    public function getReason(): string;

    /**
     * @param string $reason
     * @return $this
     */
    public function setReason(string $reason): self;

    /**
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string|null
     */
    public function getChannelReturnId(): ?string;

    /**
     * @param string|null $channelReturnId
     * @return $this
     */
    public function setChannelReturnId(?string $channelReturnId): self;

    /**
     * @return string|null
     */
    public function getChannelOrderId(): ?string;

    /**
     * @param string|null $channelOrderId
     * @return $this
     */
    public function setChannelOrderId(?string $channelOrderId): self;

    /**
     * @return string|null
     */
    public function getChannelOrderIdInternal(): ?string;

    /**
     * @param string|null $channelOrderIdInternal
     * @return $this
     */
    public function setChannelOrderIdInternal(?string $channelOrderIdInternal): self;

    /**
     * @return string|null
     */
    public function getPlatformOrderId(): ?string;

    /**
     * @param string|null $platformOrderId
     * @return $this
     */
    public function setPlatformOrderId(?string $platformOrderId): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}
