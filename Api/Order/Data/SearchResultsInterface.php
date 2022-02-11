<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Order\Data;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;

/**
 * Interface for Channable order search results.
 * @api
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{

    /**
     * Gets Code Items.
     *
     * @return ChannableOrderData[]
     */
    public function getItems(): array;

    /**
     * Sets Code Items.
     *
     * @param ChannableOrderData[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
