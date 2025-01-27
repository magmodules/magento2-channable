<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ResourceConnection;

/**
 * Handles loading and attaching custom options to product collections.
 */
class CustomOptions
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * CustomOptions constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Load custom options for products and their parent products.
     *
     * @param ProductCollection $products Collection of child products.
     * @param ProductCollection $parents Collection of parent products.
     * @param int $storeId Store ID to load store-specific custom options.
     * @return void
     */
    public function load(ProductCollection $products, ProductCollection $parents, int $storeId): void
    {
        $product = $products->getFirstItem();

        if (!$product || $product->isEmpty()) {
            return;
        }

        $productIds = array_merge(
            $products->getColumnValues('entity_id'),
            $parents->getColumnValues('entity_id')
        );

        // Load custom options and values
        $customOptions = $this->getCustomOptionsData($productIds, $storeId);

        // Add custom options data to the collections
        $this->addCustomOptionsDataToCollection($products, $customOptions);
        $this->addCustomOptionsDataToCollection($parents, $customOptions);
    }

    /**
     * Retrieve custom options and their values for the given product IDs.
     *
     * @param array $productIds Array of product IDs to fetch custom options for.
     * @param int $storeId Store ID to prioritize store-specific data.
     * @return array Formatted custom options with values.
     */
    private function getCustomOptionsData(array $productIds, int $storeId): array
    {
        $connection = $this->resourceConnection->getConnection();

        // Get table names
        $optionTable = $this->resourceConnection->getTableName('catalog_product_option');
        $optionValueTable = $this->resourceConnection->getTableName('catalog_product_option_type_value');
        $optionPriceTable = $this->resourceConnection->getTableName('catalog_product_option_type_price');
        $optionTitleTable = $this->resourceConnection->getTableName('catalog_product_option_type_title');

        // Fetch custom options
        $select = $connection->select()
            ->from($optionTable)
            ->where('product_id IN (?)', $productIds);
        $options = $connection->fetchAll($select);

        // Fetch option values, considering store_id and default (store_id = 0)
        $optionIds = array_column($options, 'option_id');
        $selectValues = $connection->select()
            ->from(['opv' => $optionValueTable])
            ->joinLeft(
                ['opt' => $optionTitleTable],
                'opv.option_type_id = opt.option_type_id AND (opt.store_id = :store_id OR opt.store_id = 0)',
                ['title AS value_title', 'store_id AS title_store_id']
            )
            ->joinLeft(
                ['opp' => $optionPriceTable],
                'opv.option_type_id = opp.option_type_id AND (opp.store_id = :store_id OR opp.store_id = 0)',
                ['price AS value_price', 'price_type', 'store_id AS price_store_id']
            )
            ->where('opv.option_id IN (?)', $optionIds)
            ->order(['opt.store_id DESC', 'opp.store_id DESC']); // Prioritize store-specific data
        $values = $connection->fetchAll($selectValues, ['store_id' => $storeId]);

        $groupedValues = [];
        foreach ($values as $value) {
            $groupedValues[$value['option_id']][] = [
                'option_value_id' => $value['option_type_id'],
                'option_value_title' => $value['value_title'] ?? '',
                'option_value_price' => $value['value_price'] ?? '',
                'option_value_price_type' => $value['price_type'] ?? 'fixed',
                'option_value_sku' => $value['sku'] ?? '',
            ];
        }

        $customOptions = [];
        foreach ($options as $option) {
            $customOptions[$option['product_id']][] = [
                'option_id' => $option['option_id'],
                'option_sku' => $option['sku'] ?? '',
                'option_type' => $option['type'],
                'option_required' => (bool)$option['is_require'],
                'option_values' => $groupedValues[$option['option_id']] ?? [],
            ];
        }

        return $customOptions;
    }

    /**
     * Attach custom options data to each product in the collection.
     *
     * @param ProductCollection $productCollection Collection of products.
     * @param array $customOptionsData Array of custom options grouped by product ID.
     * @return void
     */
    private function addCustomOptionsDataToCollection(ProductCollection $productCollection, array $customOptionsData): void
    {
        foreach ($productCollection as $product) {
            $customOptions = $customOptionsData[$product->getId()] ?? [];
            $product->setData('custom_options', $customOptions);
        }
    }
}
