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
     * @var array
     */
    private $inventory;

    /**
     * @var array
     */
    private $reservation;

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
        $inventoryData = $this->inventory[$stockId][$product->getSku()] ?? [];
        $reservations = $this->reservation[$stockId][$product->getSku()] ?? 0;

        $qty = isset($inventoryData['quantity'])
            ? $inventoryData['quantity'] - $reservations
            : 0;

        return !empty($inventoryData['is_salable']) ? $qty : 0;
    }

    /**
     * Get Inventory Data by SKU and StockID
     *
     * @param array $skus
     * @param int $stockId
     *
     * @return void
     */
    private function getInventoryData(array $skus, int $stockId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_stock_' . $stockId);

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $select = $connection->select()
            ->from($tableName)
            ->where('sku IN (?)', $skus);

        $inventoryData = $connection->fetchAll($select);
        foreach ($inventoryData as $data) {
            $this->inventory[$stockId][$data['sku']] = $data;
        }
    }

    /**
     * Returns number of reservations by SKU & StockId
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return float
     */
    private function getReservations(array $skus, int $stockId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_reservation');

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $select = $connection->select()
            ->from($tableName, ['sku', 'quantity' => 'SUM(quantity)'])
            ->where('sku IN (?)', $skus)
            ->where('stock_id' . ' = ?', $stockId)
            ->group('sku');

        $reservations = $connection->fetchAll($select);
        foreach ($reservations as $reservation) {
            $this->reservation[$stockId][$reservation['sku']] = $reservation['quantity'];
        }
    }

    /**
     * Loads all stock information into memory, only requiring 2 queries to the database
     * instead of the page_size * 2
     *
     * @param array $skus
     * @param array $config
     * @return void
     */
    public function load(array $skus, array $config) {
        $this->getInventoryData($skus, (int)$config['inventory']['stock_id']);
        $this->getReservations($skus, (int)$config['inventory']['stock_id']);
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

        $inventoryData = $this->inventory[$config['inventory']['stock_id']][$product->getSku()] ?? [];
        $reservations = $this->reservation[$config['inventory']['stock_id']][$product->getSku()] ?? 0;

        $qty = isset($inventoryData['quantity']) ? $inventoryData['quantity'] - $reservations : 0;
        $isSalable = $inventoryData['is_salable'] ?? 0;

        return $product->setQty($qty)
            ->setIsSalable($isSalable)
            ->setIsInStock($isSalable);
    }
}
