<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Returns;

use Exception;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderFactory;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnsData;
use Magmodules\Channable\Api\Returns\Data\SearchResultsInterfaceFactory as ReturnsSearchResultsactory;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;
use Magmodules\Channable\Model\Base\Metadata;
use Magmodules\Channable\Model\Returns\ResourceModel\CollectionFactory as ReturnsCollectionFactory;

/**
 * Returns Repository class
 */
class Repository implements ReturnsRepository
{

    /**
     * Input exception text
     */
    public const INPUT_EXCEPTION = 'An ID is needed. Set the ID and try again.';
    /**
     * "No such entity" exception text
     */
    public const NO_SUCH_ENTITY_EXCEPTION = 'The return with id "%1" does not exist.';
    /**
     * "Could not delete" exception text
     */
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the return: %1';
    /**
     * "Could not save" exception text
     */
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the return: %1';
    /**
     *  Data interface class name
     */
    public const DATA_INTERFACE = ReturnsData::class;

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
     * @var ReturnsSearchResultsactory
     */
    private $returnsSearchResultsactory;

    /**
     * @var ReturnsCollectionFactory
     */
    private $returnsCollectionFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * Repository constructor.
     * @param Metadata $metadata
     * @param ReturnsSearchResultsactory $returnsSearchResultsactory
     * @param ReturnsCollectionFactory $returnsCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        Metadata $metadata,
        ReturnsSearchResultsactory $returnsSearchResultsactory,
        ReturnsCollectionFactory $returnsCollectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->metadata = $metadata;
        $this->returnsSearchResultsactory = $returnsSearchResultsactory;
        $this->returnsCollectionFactory = $returnsCollectionFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor ?: ObjectManager::getInstance()
            ->get(CollectionProcessorInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getList($criteria)
    {
        $collection = $this->returnsCollectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection, self::DATA_INTERFACE);
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->returnsSearchResultsactory->create();
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
    public function create()
    {
        return $this->metadata->getNewInstance();
    }

    /**
     * @inheritdoc
     */
    public function save(
        ReturnsData $entity
    ): ReturnsData {
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

    /**
     * @inheritdoc
     */
    public function deleteById($id)
    {
        $entity = $this->get($id);
        return $this->delete($entity);
    }

    /**
     * @inheritdoc
     */
    public function get(int $id): ReturnsData
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
    public function delete(ReturnsData $entity): bool
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
}
