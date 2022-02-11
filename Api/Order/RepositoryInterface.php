<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;
use Magmodules\Channable\Api\Order\Data\SearchResultsInterface;
use Magmodules\Channable\Model\Order\DataModel;

/**
 * Channable Order repository interface
 */
interface RepositoryInterface
{

    /**
     * Input exception text
     */
    const INPUT_EXCEPTION = 'An ID is needed. Set the ID and try again.';
    /**
     * "No such entity" exception text
     */
    const NO_SUCH_ENTITY_EXCEPTION = 'The order with id "%1" does not exist.';
    /**
     * "Could not delete" exception text
     */
    const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the order: %1';
    /**
     * "Could not save" exception text
     */
    const COULD_NOT_SAVE_EXCEPTION = 'Could not save the order: %1';

    /**
     * Loads a specified Return
     *
     * @param int $id
     *
     * @return ChannableOrderData
     * @throws LocalizedException
     */
    public function get(int $id): ChannableOrderData;

    /**
     * Return Channable Order object
     *
     * @return ChannableOrderData
     */
    public function create();

    /**
     * Retrieves an Channable Order matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList($searchCriteria);

    /**
     * Register entity to delete
     *
     * @param DataModel $entity
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(DataModel $entity): bool;

    /**
     * Deletes an Channable Order entity by ID
     *
     * @param int $id
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);

    /**
     * Perform persist operations for one entity
     *
     * @param ChannableOrderData $entity
     *
     * @return ChannableOrderData
     * @throws LocalizedException
     */
    public function save(ChannableOrderData $entity): ChannableOrderData;

    /**
     * @param int $channableId
     *
     * @return int|bool
     */
    public function getByChannableId(int $channableId);

    /**
     * @param array $orderData
     * @param int $storeId
     * @throws LocalizedException
     * @return ChannableOrderData
     */
    public function createByDataArray(array $orderData, int $storeId) : ChannableOrderData;
}
