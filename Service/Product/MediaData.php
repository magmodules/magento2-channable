<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;

class MediaData
{
    /**
     * @var ProductAttributeInterface
     * @since 101.0.0
     */
    protected $attribute;

    /**
     * @var ProductAttributeRepositoryInterface
     * @since 101.0.0
     */
    protected $attributeRepository;

    /**
     * @var Gallery
     * @since 101.0.0
     */
    protected $resourceModel;

    /**
     * @var EntityMetadata
     * @since 101.0.0
     */
    protected $metadata;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Gallery $resourceModel
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        Gallery                             $resourceModel,
        MetadataPool                        $metadataPool
    )
    {
        $this->attributeRepository = $attributeRepository;
        $this->resourceModel = $resourceModel;
        $this->metadata = $metadataPool->getMetadata(
            ProductInterface::class
        );
    }

    /**
     * Execute read handler for catalog product gallery
     *
     * @param ProductCollection $products
     * @param ProductCollection $parents
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @since 101.0.0
     */
    public function load(ProductCollection $products, ProductCollection $parents)
    {
        /** @var Product $product */
        $product = $products->getFirstItem();

        if ($product->isEmpty()) {
            return;
        }

        $select = $this->resourceModel->createBatchBaseSelect(
            (int)$product->getStoreId(),
            $this->getAttribute()->getAttributeId()
        );

        $productIds = array_merge(
            $products->getColumnValues('entity_id'),
            $parents->getColumnValues('entity_id')
        );

        $select->where(
            'entity.' . $this->metadata->getLinkField() . ' IN (?)',
            $productIds
        );

        $result = $this->resourceModel->getConnection()->fetchAll($select);

        $mediaEntriesList = [];
        foreach ($result as $record) {
            $mediaEntriesList[$record[$this->metadata->getLinkField()]][] = $record;
        }

        $this->addMediaDataToCollection($products, $mediaEntriesList);
        $this->addMediaDataToCollection($parents, $mediaEntriesList);
    }

    public function addMediaDataToCollection(ProductCollection $productCollection, array $mediaEntriesList)
    {
        foreach ($productCollection as $product) {
            $this->addMediaDataToProduct($product, $mediaEntriesList[$product->getData($this->metadata->getLinkField())] ?? []);
        }
    }

    /**
     * Add media data to product
     *
     * @param Product $product
     * @param array $mediaEntries
     * @return void
     * @since 101.0.1
     */
    public function addMediaDataToProduct(Product $product, array $mediaEntries)
    {
        $product->setData(
            $this->getAttribute()->getAttributeCode(),
            [
                'images' => array_column($mediaEntries, null, 'value_id'),
                'values' => []
            ]
        );
    }

    /**
     * Get attribute
     *
     * @return ProductAttributeInterface
     * @since 101.0.0
     */
    public function getAttribute()
    {
        if (!$this->attribute) {
            $this->attribute = $this->attributeRepository->get('media_gallery');
        }

        return $this->attribute;
    }
}
