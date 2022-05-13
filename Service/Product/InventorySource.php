<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager as ModuleManager;

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
     * @param string $websiteCode
     *
     * @return null|int
     */
    public function execute(string $websiteCode): ?int
    {
        if (!$this->isMsiEnabled()) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_sales_channel');

        if (!$connection->isTableExists($tableName)) {
            return null;
        }

        $select = $connection->select()
            ->from($tableName, ['stock_id'])
            ->where('code = ?', $websiteCode)
            ->limit(1);

        return (int)$connection->fetchOne($select);
    }

    /**
     * Check if MSI is enabled
     *
     * @return bool
     */
    public function isMsiEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
