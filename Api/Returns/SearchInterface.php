<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * REST API interface for searching Channable returns.
 *
 * @api
 */
interface SearchInterface
{
    /**
     * Search Channable returns by criteria.
     *
     * Returns a flat list of return records with channel identifiers
     * and linked Magento order/creditmemo references.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magmodules\Channable\Api\Returns\SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface;
}
