<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Returns;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ChannableReturnsData;
use Magmodules\Channable\Api\Returns\Data\DataInterfaceFactory;

/**
 * Returns DataModel
 */
class DataModel extends AbstractModel implements ExtensibleDataInterface, ChannableReturnsData
{

    /**
     * @var string
     */
    protected $_eventPrefix = 'channable_returns';
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var DataInterfaceFactory
     */
    private $itemDataFactory;
    /**
     * @var Json
     */
    private $json;

    /**
     * DataModel constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param DataInterfaceFactory $itemDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param Json $json
     * @param ResourceModel $resource
     * @param Collection $collection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataInterfaceFactory $itemDataFactory,
        DataObjectHelper $dataObjectHelper,
        Json $json,
        ResourceModel $resource,
        Collection $collection,
        array $data = []
    ) {
        $this->itemDataFactory = $itemDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->json = $json;
        parent::__construct($context, $registry, $resource, $collection, $data);
    }

    /**
     * Retrieves Channable Returns data model
     *
     * @return ChannableReturnsData
     */
    public function getDataModel(): ChannableReturnsData
    {
        $itemData = $this->getData();
        $itemDataObject = $this->itemDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $itemDataObject,
            $itemData,
            DataInterfaceFactory::class
        );

        return $itemDataObject;
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId): ChannableReturnsData
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): ChannableReturnsData
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getChannelName(): string
    {
        return (string)$this->getData(self::CHANNEL_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setChannelName($channelName): ChannableReturnsData
    {
        return $this->setData(self::CHANNEL_NAME, $channelName);
    }

    /**
     * @inheritDoc
     */
    public function getChannelId(): string
    {
        return (string)$this->getData(self::CHANNEL_ID);
    }

    /**
     * @inheritDoc
     */
    public function setChannelId($channelId): ChannableReturnsData
    {
        return $this->setData(self::CHANNEL_ID, $channelId);
    }

    /**
     * @inheritDoc
     */
    public function getChannableId(): int
    {
        return (int)$this->getData(self::CHANNABLE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setChannableId(int $channableId): ChannableReturnsData
    {
        return $this->setData(self::CHANNABLE_ID, $channableId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderId(): ?int
    {
        return $this->getData(self::MAGENTO_ORDER_ID)
            ? (int)$this->getData(self::MAGENTO_ORDER_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderId(?int $magentoOrderId): ChannableReturnsData
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoIncrementId(): string
    {
        return (string)$this->getData(self::MAGENTO_INCREMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoIncrementId(string $magentoIncrementId): ChannableReturnsData
    {
        return $this->setData(self::MAGENTO_INCREMENT_ID, $magentoIncrementId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoCreditmemoId(): ?int
    {
        return $this->getData(self::MAGENTO_CREDITMEMO_ID)
            ? (int)$this->getData(self::MAGENTO_CREDITMEMO_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setMagentoCreditmemoId(?int $magentoCreditmemoId): ChannableReturnsData
    {
        return $this->setData(self::MAGENTO_CREDITMEMO_ID, $magentoCreditmemoId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoCreditmemoIncrementId(): string
    {
        return (string)$this->getData(self::MAGENTO_CREDITMEMO_INCREMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoCreditmemoIncrementId(string $magentoIncrementId): ChannableReturnsData
    {
        return $this->setData(self::MAGENTO_CREDITMEMO_INCREMENT_ID, $magentoIncrementId);
    }

    /**
     * @inheritDoc
     */
    public function getItem(): array
    {
        $items = $this->getData(self::ITEM);

        try {
            while (is_string($items)) {
                $items = $this->json->unserialize($items);
            }
        } catch (\Exception $exception) {
            $items = [];
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function setItem(array $item): ChannableReturnsData
    {
        return $this->setData(
            self::ITEM,
            $this->json->serialize($item)
        );
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName(): string
    {
        return (string)$this->getData(self::CUSTOMER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName(string $customerName): ChannableReturnsData
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(): array
    {
        $customer = $this->getData(self::CUSTOMER);

        try {
            while (is_string($customer)) {
                $customer = $this->json->unserialize($customer);
            }
        } catch (\Exception $exception) {
            $customer = [];
        }

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function setCustomer(array $customer): ChannableReturnsData
    {
        return $this->setData(
            self::CUSTOMER,
            $this->json->serialize($customer)
        );
    }

    /**
     * @inheritDoc
     */
    public function getAddress(): array
    {
        $address = $this->getData(self::ADDRESS);

        try {
            while (is_string($address)) {
                $address = $this->json->unserialize($address);
            }
        } catch (\Exception $exception) {
            $address = [];
        }

        return $address;
    }

    /**
     * @inheritDoc
     */
    public function setAddress(array $address): ChannableReturnsData
    {
        return $this->setData(
            self::ADDRESS,
            $this->json->serialize($address)
        );
    }

    /**
     * @inheritDoc
     */
    public function getReason(): string
    {
        return (string)$this->getData(self::REASON);
    }

    /**
     * @inheritDoc
     */
    public function setReason(string $reason): ChannableReturnsData
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * @inheritDoc
     */
    public function getComment(): ?string
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * @inheritDoc
     */
    public function setComment(?string $comment): ChannableReturnsData
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): ChannableReturnsData
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getChannelReturnId(): ?string
    {
        return $this->getData(self::CHANNEL_RETURN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setChannelReturnId(?string $channelReturnId): ChannableReturnsData
    {
        return $this->setData(self::CHANNEL_RETURN_ID, $channelReturnId);
    }

    /**
     * @inheritDoc
     */
    public function getChannelOrderId(): ?string
    {
        return $this->getData(self::CHANNEL_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setChannelOrderId(?string $channelOrderId): ChannableReturnsData
    {
        return $this->setData(self::CHANNEL_ORDER_ID, $channelOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getChannelOrderIdInternal(): ?string
    {
        return $this->getData(self::CHANNEL_ORDER_ID_INTERNAL);
    }

    /**
     * @inheritDoc
     */
    public function setChannelOrderIdInternal(?string $channelOrderIdInternal): ChannableReturnsData
    {
        return $this->setData(self::CHANNEL_ORDER_ID_INTERNAL, $channelOrderIdInternal);
    }

    /**
     * @inheritDoc
     */
    public function getPlatformOrderId(): ?string
    {
        return $this->getData(self::PLATFORM_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPlatformOrderId(?string $platformOrderId): ChannableReturnsData
    {
        return $this->setData(self::PLATFORM_ORDER_ID, $platformOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): ChannableReturnsData
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): ChannableReturnsData
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
