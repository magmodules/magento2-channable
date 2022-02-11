<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Order;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableReturnsData;
use Magmodules\Channable\Api\Order\Data\DataInterfaceFactory;
use Magmodules\Channable\Model\Order\ResourceModel\Collection;
use Magmodules\Channable\Model\Order\ResourceModel\ResourceModel;

class DataModel extends AbstractModel implements ExtensibleDataInterface, ChannableOrderData
{

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var DataInterfaceFactory
     */
    private $itemDataFactory;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param DataInterfaceFactory $itemDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel $resource
     * @param Collection $collection
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataInterfaceFactory $itemDataFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel $resource,
        Collection $collection,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->itemDataFactory = $itemDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->resourceConnection = $resourceConnection;
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
     *
     */
    public function _construct()
    {
        $this->_init(ResourceModel::class);
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
    public function setEntityId($entityId): ChannableOrderData
    {
        return $this->setData(self::ENTITY_ID, $entityId);
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
    public function setChannableId(int $channableId): ChannableOrderData
    {
        return $this->setData(self::CHANNABLE_ID, $channableId);
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
    public function setChannelId(string $channelId): ChannableOrderData
    {
        return $this->setData(self::CHANNEL_ID, $channelId);
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
    public function setChannelName(string $channelName): ChannableOrderData
    {
        return $this->setData(self::CHANNEL_NAME, $channelName);
    }

    /**
     * @inheritDoc
     */
    public function getChannelLabel(): string
    {
        return (string)$this->getData(self::CHANNEL_LABEL);
    }

    /**
     * @inheritDoc
     */
    public function setChannelLabel(string $channelLabel): ChannableOrderData
    {
        return $this->setData(self::CHANNEL_LABEL, $channelLabel);
    }

    /**
     * @inheritDoc
     */
    public function getChannableOrderStatus(): string
    {
        return (string)$this->getData(self::CHANNABLE_ORDER_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setChannableOrderStatus(string $channableOrderStatus): ChannableOrderData
    {
        return $this->setData(self::CHANNABLE_ORDER_STATUS, $channableOrderStatus);
    }

    /**
     * @inheritDoc
     */
    public function getIsTests(): bool
    {
        return (bool)$this->getData(self::IS_TEST);
    }

    /**
     * @inheritDoc
     */
    public function setIsTests(bool $isTest): ChannableOrderData
    {
        return $this->setData(self::IS_TEST, $isTest);
    }

    /**
     * @inheritDoc
     */
    public function getProducts(): array
    {
        return $this->getData(self::PRODUCT);
    }

    /**
     * @inheritDoc
     */
    public function setProducts(string $products): ChannableOrderData
    {
        return $this->setData(self::PRODUCT, $products);
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(): array
    {
        return $this->getData(self::CUSTOMER);
    }

    /**
     * @inheritDoc
     */
    public function setCustomer(string $customer): ChannableOrderData
    {
        return $this->setData(self::CUSTOMER, $customer);
    }

    /**
     * @inheritDoc
     */
    public function getBilling(): array
    {
        return $this->getData(self::BILLING);
    }

    /**
     * @inheritDoc
     */
    public function setBilling(string $billing): ChannableOrderData
    {
        return $this->setData(self::BILLING, $billing);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): array
    {
        return $this->getData(self::PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setPrice(string $price): ChannableOrderData
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderId(int $magentoOrderId): ChannableOrderData
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
    public function setMagentoIncrementId(string $magentoIncrementId): ChannableOrderData
    {
        return $this->setData(self::MAGENTO_INCREMENT_ID, $magentoIncrementId);
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
    public function setStoreId(int $storeId): ChannableOrderData
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMsg(): string
    {
        return (string)$this->getData(self::ERROR_MSG);
    }

    /**
     * @inheritDoc
     */
    public function setErrorMsg(string $errorMsg): ChannableOrderData
    {
        return $this->setData(self::ERROR_MSG, $errorMsg);
    }

    /**
     * @inheritDoc
     */
    public function getAttempts(): int
    {
        return (int)$this->getData(self::ATTEMPTS);
    }

    /**
     * @inheritDoc
     */
    public function setAttempts(int $attempts): ChannableOrderData
    {
        return $this->setData(self::ATTEMPTS, $attempts);
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
    public function setCreatedAt(string $createdAt): ChannableOrderData
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
    public function setUpdatedAt(string $updatedAt): ChannableOrderData
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function beforeSave()
    {
        if (!$this->getStatus()) {
            $this->setStatus('new');
        }
        return parent::beforeSave();
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
    public function setStatus(string $status): ChannableOrderData
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return DataModel
     */
    public function afterSave(): DataModel
    {
        if (!$this->getMagentoOrderId()) {
            return parent::afterSave();
        }

        $connection = $this->resourceConnection->getConnection();
        $selectData = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('channable_orders'),
                [
                    'channel_id',
                    'channable_id',
                    'channel_label',
                    'channel_name',
                ]
            )->where('magento_order_id = ?', $this->getMagentoOrderId());
        $data = $connection->fetchRow($selectData);
        if ($data) {
            $connection->update(
                $this->resourceConnection->getTableName('sales_order_grid'),
                $data,
                [
                    'entity_id = ?' => $this->getMagentoOrderId()
                ]
            );
        }

        return parent::afterSave();
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderId(): int
    {
        return (int)$this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function getShipping(): array
    {
        return $this->getData(self::SHIPPING);
    }

    /**
     * @inheritDoc
     */
    public function setShipping(string $shipping): ChannableReturnsData
    {
        return $this->setData(self::SHIPPING, $shipping);
    }
}
