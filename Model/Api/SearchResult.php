<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DataObject;

class SearchResult extends DataObject implements
    \Magmodules\Channable\Api\Order\SearchResultInterface,
    \Magmodules\Channable\Api\Returns\SearchResultInterface
{
    public function getItems(): array
    {
        return $this->getData('items') ?? [];
    }

    public function setItems(array $items): self
    {
        return $this->setData('items', $items);
    }

    public function getTotalCount(): int
    {
        return (int) $this->getData('total_count');
    }

    public function setTotalCount(int $totalCount): self
    {
        return $this->setData('total_count', $totalCount);
    }

    public function getSearchCriteria(): SearchCriteriaInterface
    {
        return $this->getData('search_criteria');
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): self
    {
        return $this->setData('search_criteria', $searchCriteria);
    }
}
