<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Test\Unit\Model\Collection;

use Magento\Catalog\Model\Indexer\Product\Flat\StateFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\App\ResourceConnection;
use Magmodules\Channable\Helper\General;
use Magmodules\Channable\Helper\Product;
use Magmodules\Channable\Model\Collection\Products;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers addFilters() resolving select/multiselect filter labels to their option values.
 */
class ProductsTest extends TestCase
{
    /**
     * A placeholder-free source (status, visibility, tax_class_id, custom source models): its
     * first option is a real value at index 0.
     */
    private const PLACEHOLDER_FREE_OPTIONS = [
        ['value' => '1', 'label' => 'Enabled'],
        ['value' => '2', 'label' => 'Disabled'],
    ];

    #[DataProvider('labelToOptionValueProvider')]
    public function testResolvesOptionLabelToOptionValue(string $value, string $expected): void
    {
        $captured = [];
        $collection = $this->createStub(ProductCollection::class);
        $collection->method('addAttributeToFilter')
            ->willReturnCallback(static function (...$args) use (&$captured): void {
                $captured[] = $args;
            });

        $products = $this->createProducts($collection);

        $products->addFilters(
            [
                'advanced' => [
                    [
                        'attribute'    => 'status',
                        'condition'    => 'eq',
                        'value'        => $value,
                        'product_type' => '',
                    ],
                ],
            ],
            $collection
        );

        self::assertSame($expected, $this->resolvedFilterValue($captured));
    }

    public static function labelToOptionValueProvider(): array
    {
        return [
            'first option'           => ['Enabled', '1'],
            'later option'           => ['Disabled', '2'],
            'comma-separated labels' => ['Enabled,Disabled', '1,2'],
            'unknown label'          => ['Unknown', 'Unknown'],
        ];
    }

    private function createProducts(ProductCollection $collection): Products
    {
        $attribute = $this->createStub(Attribute::class);
        $attribute->method('getFrontendInput')->willReturn('select');

        $source = $this->createStub(AbstractSource::class);
        $source->method('getAllOptions')->willReturn(self::PLACEHOLDER_FREE_OPTIONS);
        $attribute->method('getSource')->willReturn($source);

        $eavConfig = $this->createStub(EavConfig::class);
        $eavConfig->method('getAttribute')->willReturn($attribute);

        return new Products(
            $this->createStub(ProductCollectionFactory::class),
            $collection,
            $this->createStub(ProductAttributeCollectionFactory::class),
            $eavConfig,
            $this->createStub(Stock::class),
            $this->createStub(General::class),
            $this->createStub(Product::class),
            $this->createStub(StateFactory::class),
            $this->createStub(ResourceConnection::class)
        );
    }

    private function resolvedFilterValue(array $captured): string
    {
        foreach ($captured as $args) {
            $filter = $args[0] ?? null;
            if (is_array($filter) && isset($filter[0]['attribute']) && $filter[0]['attribute'] === 'status') {
                return $filter[0]['eq'];
            }
        }

        return '';
    }
}
