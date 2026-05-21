<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * REST API interface for searching Channable orders.
 *
 * @api
 */
interface SearchInterface
{
    /**
     * Search Channable orders by criteria.
     *
     * Returns a flat list of order records with channel identifiers
     * and linked Magento order references.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magmodules\Channable\Api\Order\SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface;
}
