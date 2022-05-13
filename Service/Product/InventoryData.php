<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

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
     * Get Salable QTY for a product by StockID
     *
     * @param ProductInterface $product
     * @param int $stockId
     *
     * @return float|int|mixed
     */
    public function getSalableQty(ProductInterface $product, int $stockId): float
    {
        $inventoryData = $this->getInventoryData($product->getSku(), $stockId);
        $reservations = $this->getReservations($product->getSku(), $stockId);

        $qty = isset($inventoryData['quantity'])
            ? $inventoryData['quantity'] - $reservations
            : 0;

        return !empty($inventoryData['is_salable']) ? $qty : 0;
    }

    /**
     * Get Inventory Data by SKU and StockID
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return mixed
     */
    private function getInventoryData(string $sku, int $stockId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_' . $stockId);

        if (!$connection->isTableExists($tableName)) {
            return [];
        }

        $select = $connection->select()
            ->from($tableName)
            ->where('sku = ?', $sku)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Returns number of reservations by SKU & StockId
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return float
     */
    private function getReservations(string $sku, int $stockId): float
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');

        if (!$connection->isTableExists($tableName)) {
            return 0;
        }

        $select = $connection->select()
            ->from($tableName, ['quantity' => 'SUM(quantity)'])
            ->where('sku = ?', $sku)
            ->where('stock_id' . ' = ?', $stockId)
            ->limit(1);

        return ($reservationQty = $connection->fetchOne($select))
            ? max(0, ($reservationQty * -1))
            : 0;
    }

    /**
     * Add stock data to product object
     *
     * @param Product $product
     * @param array $config
     *
     * @return Product
     */
    public function addDataToProduct(Product $product, array $config): Product
    {
        if (empty($config['inventory']['stock_id'])
            || $product->getTypeId() != 'simple'
        ) {
            return $product;
        }

        $inventoryData = $this->getInventoryData($product->getSku(), $config['inventory']['stock_id']);
        $reservations = $this->getReservations($product->getSku(), $config['inventory']['stock_id']);

        $qty = isset($inventoryData['quantity']) ? $inventoryData['quantity'] - $reservations : 0;
        $isSalable = $inventoryData['is_salable'] ?? 0;

        return $product->setQty($qty)
            ->setIsSalable($isSalable)
            ->setIsInStock($isSalable);
    }
}
