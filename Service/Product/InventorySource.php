<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager as ModuleManager;

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
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * InventorySource constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ModuleManager      $moduleManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ModuleManager $moduleManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->moduleManager = $moduleManager;
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

        if (!$this->isMsiEnabled()) {
            return $source;
        }

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

    /**
     * @return bool
     */
    public function isMsiEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

}