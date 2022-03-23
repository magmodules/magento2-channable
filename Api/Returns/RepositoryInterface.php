<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ChannableReturnsData;
use Magmodules\Channable\Api\Returns\Data\SearchResultsInterface;

/**
 * Interface Repository
 */
interface RepositoryInterface
{

    /**
     * Loads a specified Return
     *
     * @param int $id
     *
     * @return ChannableReturnsData
     * @throws LocalizedException
     */
    public function get(int $id): ChannableReturnsData;

    /**
     * Return Channable Returns object
     *
     * @return ChannableReturnsData
     */
    public function create();

    /**
     * Retrieves an Channable Returns matching the specified criteria.
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
     * @param ChannableReturnsData $entity
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(ChannableReturnsData $entity): bool;

    /**
     * Deletes an Channable Returns entity by ID
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
     * @param ChannableReturnsData $entity
     *
     * @return ChannableReturnsData
     * @throws LocalizedException
     */
    public function save(
        ChannableReturnsData $entity
    ): ChannableReturnsData;
}
