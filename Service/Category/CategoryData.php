<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Category;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class CategoryData
{
    public const EXCLUDE_ATTRIBUTE = 'channable_cat_disable_export';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ResourceConnection $resourceConnection,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param ProductCollection $products
     * @param ProductCollection $parents
     * @param int $storeId
     * @return array
     */
    public function load(ProductCollection $products, ProductCollection $parents, int $storeId): array
    {
        $product = $products->getFirstItem();
        if ($product->isEmpty()) {
            return [];
        }

        $productIds = array_merge(
            $products->getColumnValues('entity_id'),
            $parents->getColumnValues('entity_id')
        );

        $categoryIds = $this->getCategoryIdsForCollection($productIds);
        return $this->getCategoryTree($categoryIds, $storeId);
    }

    /**
     * @param array $productIds
     * @return array
     */
    private function getCategoryIdsForCollection(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_category_product');

        $select = $connection->select()
            ->from($tableName, 'category_id')
            ->where('product_id IN (?)', $productIds)
            ->group('category_id');

        return $connection->fetchCol($select);
    }

    /**
     * @param array $categoryIds
     * @param int $storeId
     * @return array
     */
    private function getCategoryTree(array $categoryIds, int $storeId): array
    {
        $collection = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'level', 'path', 'url_path', self::EXCLUDE_ATTRIBUTE])
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->addFieldToFilter('is_active', ['eq' => 1]);

        try {
            $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        } catch (\Exception $exception) {
            $rootCategoryId = null;
        }

        if ($rootCategoryId) {
            $collection->addFieldToFilter('path', ['like' => '%/' . $rootCategoryId . '/%']);
        }

        $data = [];
        foreach ($collection as $category) {
            $data[$category->getId()] = [
                'name' => $category->getName(),
                'level' => $category->getLevel(),
                'url' => $category->getUrl(),
                'path' => $category->getPath(),
                'exclude' => $category->getData(self::EXCLUDE_ATTRIBUTE)
            ];
        }

        $categories = [];
        foreach ($data as $categoryId => $category) {
            $paths = explode('/', (string)$category['path']);
            $pathText = [];
            $level = 0;
            $exclude = 0;
            foreach ($paths as $path) {
                if (!empty($data[$path]['name']) && ($path != $rootCategoryId)) {
                    $pathText[] = $data[$path]['name'];
                    if (!empty($data[$path]['exclude'])) {
                        $exclude = 1;
                    }
                    $level++;
                }
            }
            if (!$exclude) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'level' => $level,
                    'path' => implode(' > ', $pathText),
                    'url' => $category['url']
                ];
            }
        }

        return $categories;
    }
}
