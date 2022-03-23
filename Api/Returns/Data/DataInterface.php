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

    public const ID = 'id';
    public const STORE_ID = 'store_id';
    public const ORDER_ID = 'order_id';
    public const CHANNEL_NAME = 'channel_name';
    public const CHANNEL_ID = 'channel_id';
    public const CHANNABLE_ID = 'channable_id';
    public const MAGENTO_ORDER_ID = 'magento_order_id';
    public const MAGENTO_INCREMENT_ID = 'magento_increment_id';
    public const ITEM = 'item';
    public const CUSTOMER_NAME = 'customer_name';
    public const CUSTOMER = 'customer';
    public const ADDRESS = 'address';
    public const REASON = 'reason';
    public const COMMENT = 'comment';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param mixed $value
     * @return $this
     */
    public function setId($value): self;

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
     * @param string $channelName
     * @return $this
     */
    public function setChannelName(string $channelName): self;

    /**
     * @return int
     */
    public function getChannelId(): int;

    /**
     * @param int $channelId
     * @return $this
     */
    public function setChannelId(int $channelId): self;

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
     * @return int
     */
    public function getMagentoOrderId(): int;

    /**
     * @param int $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(int $magentoOrderId): self;

    /**
     * @return int
     */
    public function getMagentoIncrementId(): int;

    /**
     * @param int $magentoIncrementId
     * @return $this
     */
    public function setMagentoIncrementId(int $magentoIncrementId): self;

    /**
     * @return string
     */
    public function getItem(): string;

    /**
     * @param string $item
     * @return $this
     */
    public function setItem(string $item): self;

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
     * @return string
     */
    public function getCustomer(): string;

    /**
     * @param string $customer
     * @return $this
     */
    public function setCustomer(string $customer): self;

    /**
     * @return string
     */
    public function getAddress(): string;

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): self;

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
     * @return string
     */
    public function getComment(): string;

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment(string $comment): self;

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
