<?php

/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Config\System;

interface FeedInterface
{

    public const XML_PATH_BUNDLE_STOCK_CALCULATION = 'magmodules_channable/types/bundle_stock_calculation';

    /**
     * Check if bundle stock calculation is enabled.
     *
     * This setting determines whether the parent bundle product stock
     * should be calculated based on the lowest stock of associated simple products.
     *
     * @param int|null $storeId Store ID for which the configuration should be checked.
     *                          If null, uses the default store configuration.
     * @return bool True if bundle stock calculation is enabled, false otherwise.
     */
    public function isBundleStockCalculationEnabled(?int $storeId = null): bool;
}
