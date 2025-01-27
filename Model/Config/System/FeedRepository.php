<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magmodules\Channable\Api\Config\System\FeedInterface;

/**
 * Feed provider class
 */
class FeedRepository extends BaseRepository implements FeedInterface
{

    /**
     * @inheritDoc
     */
    public function isBundleStockCalculationEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_BUNDLE_STOCK_CALCULATION, $storeId);
    }
}
