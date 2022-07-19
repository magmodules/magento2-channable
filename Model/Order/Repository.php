<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Order;

use Exception;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Api\Order\Data\DataInterface;
use Magmodules\Channable\Api\Order\Data\SearchResultsInterfaceFactory;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Model\Base\Metadata;
use Magmodules\Channable\Model\Order\ResourceModel\CollectionFactory as ChannableOrderCollectionFactory;
use Magmodules\Channable\Model\Order\ResourceModel\ResourceModel as ChannableOrderResource;

/**
 * Order Repository class
 */
class Repository implements ChannableOrderRepository
{

    /**
     *  Data interface class name
     */
    const DATA_INTERFACE = DataInterface::class;

    /**
     * DataInterface[]
     *
     * @var array
     */
    private $registry = [];
    /**
     * @var Metadata
     */
    private $metadata;
    /**
     * @var SearchResultsInterfaceFactory
     */
    private $channableOrderSearchResultsFactory;
    /**
     * @var ChannableOrderCollectionFactory
     */
    private $channableOrderCollectionFactory;
    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    /**
     * @var ChannableOrderResource
     */
    private $channableOrderResource;

    /***
     * @param Metadata $metadata
     * @param SearchResultsInterfaceFactory $channableOrderSearchResultsFactory
     * @param ChannableOrderCollectionFactory $channableOrderCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ChannableOrderResource $channableOrderResource
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        Metadata $metadata,
        SearchResultsInterfaceFactory $channableOrderSearchResultsFactory,
        ChannableOrderCollectionFactory $channableOrderCollectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ChannableOrderResource $channableOrderResource,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->channableOrderResource = $channableOrderResource;
        $this->metadata = $metadata;
        $this->channableOrderSearchResultsFactory = $channableOrderSearchResultsFactory;
        $this->channableOrderCollectionFactory = $channableOrderCollectionFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor ?: ObjectManager::getInstance()
            ->get(CollectionProcessorInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getList($criteria)
    {
        $collection = $this->channableOrderCollectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection, self::DATA_INTERFACE);
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->channableOrderSearchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $items = [];

        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($id): bool
    {
        $entity = $this->get($id);
        return $this->delete($entity);
    }

    /**
     * @inheritdoc
     */
    public function get(int $id): DataInterface
    {
        if (!$id) {
            $exceptionMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($exceptionMsg));
        }

        if (!isset($this->registry[$id])) {
            $entity = $this->metadata->getNewInstance()->load($id);

            if (!$entity->getId()) {
                $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
                throw new NoSuchEntityException(__($exceptionMsg, $id));
            }
            $this->registry[$id] = $entity;
        }
        return $this->registry[$id];
    }

    /**
     * @inheritdoc
     */
    public function delete(DataModel $entity): bool
    {
        try {
            $this->metadata->getMapper()->delete($entity);
        } catch (Exception $exception) {
            $exceptionMsg = self::COULD_NOT_DELETE_EXCEPTION;
            throw new CouldNotDeleteException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        unset($this->registry[$entity->getId()]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createByDataArray(array $orderData, int $storeId): DataInterface
    {
        $channableOrderId = $this->getByChannableId((int)$orderData['channable_id']);
        if ($channableOrderId) {
            $channableOrder = $this->get((int)$channableOrderId);
            $channableOrder->setAttempts($channableOrder->getAttempts() + 1);
        } else {
            $orderData['attempts'] = 1;
            $orderData['store_id'] = $storeId;
            $orderData['channel_label'] = $orderData['channable_channel_label'];
            $orderData['channable_order_status'] = $orderData['order_status'];
            $channableOrder = $this->create();
            $channableOrder->setData($orderData);
        }
        $this->save($channableOrder);
        return $channableOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function getByChannableId(int $channableId)
    {
        return $this->channableOrderResource->getByChannableId($channableId);
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        return $this->metadata->getNewInstance();
    }

    /**
     * @inheritdoc
     */
    public function save(DataInterface $entity): DataInterface
    {
        try {
            $this->metadata->getMapper()->save($entity);
        } catch (Exception $exception) {
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        $this->registry[$entity->getId()] = $entity;

        return $this->registry[$entity->getId()];
    }
}
