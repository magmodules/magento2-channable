<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;

class TierPriceData
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var EntityMetadata
     */
    private $metadata;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @throws Exception
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadata = $metadataPool->getMetadata(ProductInterface::class);
    }

    /**
     * @param ProductCollection $products
     * @param ProductCollection $parents
     * @return void
     */
    public function load(ProductCollection $products, ProductCollection $parents, $websiteId)
    {
        /** @var Product $product */
        $product = $products->getFirstItem();

        if ($product->isEmpty()) {
            return;
        }

        $productIds = array_merge(
            $products->getColumnValues('entity_id'),
            $parents->getColumnValues('entity_id')
        );

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_product_entity_tier_price');

        $select = $connection->select()
            ->from($tableName)
            ->where('website_id IN (?)', [0, $websiteId])
            ->where($this->metadata->getLinkField() . ' IN (?)', $productIds);

        $result = $connection->fetchAll($select);

        $tierPriceData = [];
        foreach ($result as $record) {
            $tierPriceData[$record[$this->metadata->getLinkField()]][] = $record;
        }

        $this->addTierPriceDataToCollection($products, $tierPriceData);
        $this->addTierPriceDataToCollection($parents, $tierPriceData);
    }

    /**
     * @param ProductCollection $productCollection
     * @param array $tierPriceData
     * @return void
     */
    private function addTierPriceDataToCollection(ProductCollection $productCollection, array $tierPriceData)
    {
        foreach ($productCollection as $product) {
            $tierPrice = $tierPriceData[$product->getData($this->metadata->getLinkField())] ?? [];
            $product->setData('tier_price', $tierPrice);
        }
    }
}