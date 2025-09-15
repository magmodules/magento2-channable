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
use Magento\Framework\Exception\LocalizedException;
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
     * @throws LocalizedException
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
        $ccpTable = $this->resourceConnection->getTableName('catalog_category_product');

        $select = $connection->select()
            ->from($ccpTable, 'category_id')
            ->where('product_id IN (?)', $productIds)
            ->group('category_id');

        $categoryIds = $connection->fetchCol($select);

        $cceTable = $this->resourceConnection->getTableName('catalog_category_entity');
        $select = $connection->select()
            ->from($cceTable, ['entity_id', 'path'])
            ->where('entity_id IN (?) OR parent_id IN (?)', $categoryIds);

        $categoryData = $connection->fetchPairs($select);

        $allCategoryIds = [];
        // Extract full category ancestry from path to not miss out on any subcategories.
        foreach ($categoryData as $categoryId => $path) {
            $pathIds = explode('/', $path);
            foreach ($pathIds as $pathId) {
                if (!empty($pathId) && is_numeric($pathId)) {
                    $allCategoryIds[$pathId] = $pathId;
                }
            }
        }
        return array_values($allCategoryIds);
    }

    /**
     * @param array $categoryIds
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function getCategoryTree(array $categoryIds, int $storeId): array
    {
        $collection = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'level', 'path', 'url_path', self::EXCLUDE_ATTRIBUTE])
            ->addFieldToFilter([
                ['attribute' => 'entity_id', 'in' => $categoryIds],
                ['attribute' => 'parent_id', 'in' => $categoryIds]
            ])
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
