<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Model\Returns\Collection;
use Magmodules\Channable\Model\Returns\DataModel;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ChannableReturnsData;
use Magmodules\Channable\Api\Returns\Data\SearchResultsInterface;

/**
 * Interface Repository
 */
interface RepositoryInterface
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
     * Loads a specified Return
     *
     * @param int $entityId
     *
     * @return ChannableReturnsData
     * @throws LocalizedException
     */
    public function get(int $entityId): ChannableReturnsData;

    /**
     * Return Channable Returns object
     *
     * @return ChannableReturnsData
     */
    public function create(): ChannableReturnsData;

    /**
     * Retrieves a Channable Returns matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Register entity to delete
     *
     * @param ChannableReturnsData $entity
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(ChannableReturnsData $entity): bool;

    /**
     * Deletes a Channable Returns entity by ID
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Perform persist operations for one entity
     *
     * @param ChannableReturnsData $entity
     *
     * @return ChannableReturnsData
     * @throws LocalizedException
     */
    public function save(
        ChannableReturnsData $entity
    ): ChannableReturnsData;

    /**
     * Get data collection by set of attribute values
     *
     * @param array $dataSet
     * @param bool $getFirst
     *
     * @return Collection|DataModel
     */
    public function getByDataSet(array $dataSet, bool $getFirst = false);
}
