<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order;

/**
 * Search result for Channable orders API.
 *
 * @api
 */
interface SearchResultInterface
{
    /**
     * @return \Magmodules\Channable\Api\Order\ItemInterface[]
     */
    public function getItems(): array;

    /**
     * @param \Magmodules\Channable\Api\Order\ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;

    /**
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount(int $totalCount): self;

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    public function getSearchCriteria(): \Magento\Framework\Api\SearchCriteriaInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): self;
}
