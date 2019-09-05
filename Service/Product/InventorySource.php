<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Product;

use Magento\Framework\App\ResourceConnection;

/**
 * Class InventorySource
 *
 * @package Magmodules\Channable\Service\Product
 */
class InventorySource
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Inventory constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Returns inventory source (stock_id) by websiteCode
     *
     * @param $websiteCode
     *
     * @return string
     */
    public function execute($websiteCode)
    {
        $source = null;
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_sales_channel');

        if (!$connection->isTableExists($tableName)) {
            return $source;
        }

        $select = $connection->select()
            ->from($tableName, ['stock_id'])
            ->where('code = ?', $websiteCode)
            ->limit(1);

        return $connection->fetchOne($select);
    }

}