<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for Channable Orders
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{

    const ENTITY_ID = 'entity_id';
    const CHANNABLE_ID = 'channable_id';
    const CHANNEL_ID = 'channel_id';
    const CHANNEL_NAME = 'channel_name';
    const CHANNEL_LABEL = 'channel_label';
    const CHANNABLE_ORDER_STATUS = 'channable_order_status';
    const IS_TEST = 'is_test';
    const PRODUCT = 'products';
    const CUSTOMER = 'customer';
    const BILLING = 'billing';
    const SHIPPING = 'shipping';
    const PRICE = 'price';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const MAGENTO_INCREMENT_ID = 'magento_increment_id';
    const STORE_ID = 'store_id';
    const STATUS = 'status';
    const ERROR_MSG = 'error_msg';
    const ATTEMPTS = 'attempts';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId): self;

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
     * @return string
     */
    public function getChannelId(): string;

    /**
     * @param string $channelId
     * @return $this
     */
    public function setChannelId(string $channelId): self;

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
     * @return string
     */
    public function getChannelLabel(): string;

    /**
     * @param string $channelLabel
     * @return $this
     */
    public function setChannelLabel(string $channelLabel): self;

    /**
     * @return string
     */
    public function getChannableOrderStatus(): string;

    /**
     * @param string $channableOrderStatus
     * @return $this
     */
    public function setChannableOrderStatus(string $channableOrderStatus): self;

    /**
     * @return bool
     */
    public function getIsTests(): bool;

    /**
     * @param bool $isTest
     * @return $this
     */
    public function setIsTests(bool $isTest): self;

    /**
     * @return array
     */
    public function getProducts(): array;

    /**
     * @param string $products
     * @return $this
     */
    public function setProducts(string $products): self;

    /**
     * @return array
     */
    public function getCustomer(): array;

    /**
     * @param string $customer
     * @return $this
     */
    public function setCustomer(string $customer): self;

    /**
     * @return array
     */
    public function getBilling(): array;

    /**
     * @param string $billing
     * @return $this
     */
    public function setBilling(string $billing): self;

    /**
     * @return array
     */
    public function getShipping(): array;

    /**
     * @param string $shipping
     * @return $this
     */
    public function setShipping(string $shipping): self;

    /**
     * @return array
     */
    public function getPrice(): array;

    /**
     * @param string $price
     * @return $this
     */
    public function setPrice(string $price): self;

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
     * @return string
     */
    public function getMagentoIncrementId(): string;

    /**
     * @param string $magentoIncrementId
     * @return $this
     */
    public function setMagentoIncrementId(string $magentoIncrementId): self;

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
    public function getErrorMsg(): string;

    /**
     * @param string $errorMsg
     * @return $this
     */
    public function setErrorMsg(string $errorMsg): self;

    /**
     * @return int
     */
    public function getAttempts(): int;

    /**
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self;

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

    /**
     * @return array
     */
    public function getData($key = '', $index = null);
}
