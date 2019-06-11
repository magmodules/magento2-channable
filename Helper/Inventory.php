<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Inventory
 *
 * @package Magmodules\Channable\Helper
 */
class Inventory extends AbstractHelper
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Inventory constructor.
     *
     * @param Context            $context
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * Returns inventory source (stock_id) by websiteCode
     *
     * @param $websiteCode
     *
     * @return string
     */
    public function getInventorySource($websiteCode)
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

    /**
     * Returns number of reservations by SKU & StockId
     *
     * @param $productSku
     * @param $stockId
     *
     * @return float
     */
    public function getReservations($productSku, $stockId)
    {
        $reservationQty = 0;
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');

        if (!$connection->isTableExists($tableName)) {
            return $reservationQty;
        }

        $select = $connection->select()
            ->from($tableName, ['quantity' => 'SUM(quantity)'])
            ->where('sku = ?', $productSku)
            ->where('stock_id' . ' = ?', $stockId)
            ->limit(1);

        if ($reservationQty = $connection->fetchOne($select)) {
            return ($reservationQty * -1);
        }

        return $reservationQty;
    }

}