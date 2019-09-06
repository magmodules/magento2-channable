<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Product;

use Magento\Framework\App\ResourceConnection;

/**
 * Class InventoryData
 *
 * @package Magmodules\Channable\Service\Product
 */
class InventoryData
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * InventoryData constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $config
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function addDataToProduct($product, $config)
    {
        if (empty($config['inventory']['stock_id'])) {
            return $product;
        }

        /**
         * Return if product is not of simple type
         */
        if ($product->getTypeId() != 'simple') {
            return $product;
        }

        $inventoryData = $this->getInventoryData($product->getSku(), $config['inventory']['stock_id']);
        $reservations = $this->getReservations($product->getSku(), $config['inventory']['stock_id']);

        $qty = isset($inventoryData['quantity']) ? $inventoryData['quantity'] - $reservations : 0;
        $isSalable = isset($inventoryData['is_salable']) ? $inventoryData['is_salable'] : 0;

        return $product->setQty($qty)->setIsSalable($isSalable)->setIsInStock($isSalable);
    }

    /**
     * @param $sku
     * @param $stockId
     *
     * @return array|void
     */
    private function getInventoryData($sku, $stockId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_' . $stockId);

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $select = $connection->select()
            ->from($tableName)
            ->where('sku = ?', $sku)
            ->limit(1);

        if ($stockData = $connection->fetchRow($select)) {
            return $stockData;
        }
    }

    /**
     * Returns number of reservations by SKU & StockId
     *
     * @param $sku
     * @param $stockId
     *
     * @return float
     */
    private function getReservations($sku, $stockId)
    {
        $reservationQty = 0;
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');

        if (!$connection->isTableExists($tableName)) {
            return $reservationQty;
        }

        $select = $connection->select()
            ->from($tableName, ['quantity' => 'SUM(quantity)'])
            ->where('sku = ?', $sku)
            ->where('stock_id' . ' = ?', $stockId)
            ->limit(1);
        if ($reservationQty = $connection->fetchOne($select)) {
            return ($reservationQty * -1);
        }

        return $reservationQty;
    }
}