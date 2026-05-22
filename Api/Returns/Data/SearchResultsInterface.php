<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Returns\Data;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ChannableReturnsData;

/**
 * Interface for Channable Item search results.
 * @api
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{

    /**
     * Gets Code Items.
     *
     * @return \Magmodules\Channable\Api\Returns\Data\DataInterface[]
     */
    public function getItems(): array;

    /**
     * Sets Code Items.
     *
     * @param \Magmodules\Channable\Api\Returns\Data\DataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
