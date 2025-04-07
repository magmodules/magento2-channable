<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product;

class InventoryData
{

    private ResourceConnection $resourceConnection;
    private ConfigProvider $configProvider;

    private array $inventory;
    private array $inventorySourceItems;
    private array $reservation;
    private array $bundleParentSimpleRelation;

    public function __construct(
        ResourceConnection $resourceConnection,
        ConfigProvider $configProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configProvider = $configProvider;
    }

    /**
     * Get Inventory Data by SKU and StockID
     *
     * @param array $skus
     * @param int $stockId
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
     * Get Inventory Data by SKU and StockID
     *
     * @param array $skus
     * @return void
     */
    private function getInventorySourceItems(array $skus): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('inventory_source_item');

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $select = $connection->select()
            ->from($tableName)
            ->where('sku IN (?)', $skus);

        $inventoryData = $connection->fetchAll($select);
        foreach ($inventoryData as $data) {
            $this->inventorySourceItems[$data['sku']][$data['source_code']] = $data['quantity'];
        }
    }

    /**
     * Returns number of reservations by SKU & StockId
     *
     * @param array $skus
     * @param int $stockId
     *
     * @return void
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
            $this->reservation[$stockId][$reservation['sku']] = max(0, $reservation['quantity'] * -1);
        }
    }

    /**
     * Get all linked simple products from a list of bundle product SKUs.
     *
     * @param array $skus Array of product SKUs
     */
    public function getLinkedSimpleProductsFromBundle(array $skus): void
    {
        $connection = $this->resourceConnection->getConnection();

        // Retrieve product IDs for the given SKUs
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $bundleProductIds = $connection->fetchPairs(
            $connection->select()
                ->from($productTable, ['sku', 'entity_id'])
                ->where('sku IN (?)', $skus)
                ->where('type_id = ?', 'bundle')
        );

        if (empty($bundleProductIds)) {
            return;
        }

        // Retrieve linked simple products for the bundle products
        $selectionTable = $this->resourceConnection->getTableName('catalog_product_bundle_selection');
        $linkedProducts = $connection->fetchAll(
            $connection->select()
                ->from(['s' => $selectionTable], ['parent_product_id', 'product_id', 'selection_qty'])
                ->join(
                    ['p' => $productTable],
                    's.product_id = p.entity_id',
                    ['sku', 'type_id']
                )
                ->where('s.parent_product_id IN (?)', $bundleProductIds)
        );

        foreach ($linkedProducts as $linkedProduct) {
            $bundleSku = array_search($linkedProduct['parent_product_id'], $bundleProductIds, true);
            $this->bundleParentSimpleRelation[$bundleSku][] = [
                'sku' => $linkedProduct['sku'],
                'product_id' => $linkedProduct['product_id'],
                'quantity' => $linkedProduct['selection_qty'],
            ];
        }
    }

    /**
     * Loads all stock information into memory
     *
     * @param array $skus
     * @param array $config
     * @return void
     */
    public function load(array $skus, array $config): void
    {
        if (isset($config['inventory']['stock_id'])) {
            $this->getInventoryData($skus, (int)$config['inventory']['stock_id']);
            $this->getReservations($skus, (int)$config['inventory']['stock_id']);

            if ($this->configProvider->isBundleStockCalculationEnabled((int)$config['store_id'])) {
                $this->getLinkedSimpleProductsFromBundle($skus);
            }

            if (!empty($config['inventory']['inventory_source_items'])) {
                $this->getInventorySourceItems($skus);
            }
        }
    }

    /**
     * Add stock data to a product object.
     *
     * @param Product $product The product object to which stock data will be added.
     * @param array $config Configuration data, including inventory information.
     * @return Product The product object with added stock data.
     */
    public function addDataToProduct(Product $product, array $config): Product
    {
        if (empty($config['inventory']['stock_id'])) {
            return $product;
        }

        if (!$this->isSupportedProductType($product)) {
            return $product;
        }

        $stockData = $this->getStockData($product, $config);

        return $product
            ->setQty($stockData['qty'] ?? 0)
            ->setIsSalable($stockData['is_salable'] ?? 0)
            ->setIsInStock($stockData['is_salable'] ?? 0)
            ->setInventorySourceItems($stockData['source_item'] ?? null);
    }

    /**
     * Determine if the product type is supported for stock data processing.
     *
     * @param Product $product The product object.
     * @return bool True if the product type is supported, false otherwise.
     */
    private function isSupportedProductType(Product $product): bool
    {
        return in_array($product->getTypeId(), ['simple', 'bundle'], true);
    }

    /**
     * Retrieve stock data for a product, including bundle-specific logic.
     *
     * @param Product $product The product object.
     * @param array $config Configuration data, including inventory information.
     * @return array Stock data for the product.
     */
    private function getStockData(Product $product, array $config): array
    {
        if ($product->getTypeId() == 'bundle' && isset($this->bundleParentSimpleRelation[$product->getSku()])) {
            return $this->getBundleStockData($product, $config);
        }

        return $this->getStockDataBySku($product->getSku(), $config);
    }

    /**
     * Retrieve stock data for a bundle product based on its associated simple products.
     *
     * @param Product $product The bundle product object.
     * @param array $config Configuration data, including inventory information.
     * @return array Stock data for the bundle product.
     */
    private function getBundleStockData(Product $product, array $config): array
    {
        $simples = $this->bundleParentSimpleRelation[$product->getSku()] ?? [];
        $minStockData = ['qty' => 0, 'is_salable' => 0, 'source_item' => null];

        foreach ($simples as $simple) {
            $simpleStockData = $this->getStockDataBySku($simple['sku'], $config);
            $realStock = $simple['quantity'] ? $simpleStockData['qty'] / $simple['quantity'] : $simpleStockData['qty'];

            if ($realStock > $minStockData['qty']) {
                $minStockData = $simpleStockData;
                $minStockData['qty'] = $realStock;
            }
        }

        return $minStockData;
    }

    /**
     * Retrieve stock data for a product SKU.
     *
     * @param string $sku The SKU of the product.
     * @param array $config Configuration data, including inventory information.
     * @return array Stock data for the product.
     */
    private function getStockDataBySku(string $sku, array $config): array
    {
        $stockId = $config['inventory']['stock_id'] ?? null;

        $inventoryData = $this->inventory[$stockId][$sku] ?? [];
        $reservations = $this->reservation[$stockId][$sku] ?? 0;
        $sourceItems = $this->inventorySourceItems[$sku] ?? [];

        return [
            'qty' => isset($inventoryData['quantity']) ? $inventoryData['quantity'] - $reservations : 0,
            'is_salable' => $inventoryData['is_salable'] ?? 0,
            'source_item' => $sourceItems,
        ];
    }
}