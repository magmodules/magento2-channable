<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Test\Unit\Service\Order\Items;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item as ItemResource;
use Magento\Store\Model\Store;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Service\Order\Currency\Converter as CurrencyConverter;
use Magmodules\Channable\Service\Order\Items\Add;
use PHPUnit\Framework\TestCase;

/**
 * A marketplace can split one bundle over several order lines. Magento merges those lines into one
 * quote item and, as long as the children of that item have no ID, raises their quantity with every
 * line too. Saving the children together with the bundle is what keeps their quantity fixed.
 */
class AddTest extends TestCase
{
    private const BUNDLE_ID = 7557;

    public function testChildrenOfABundleAreSavedTogetherWithTheBundle(): void
    {
        $childA = $this->createMock(QuoteItem::class);
        $childB = $this->createMock(QuoteItem::class);
        $bundleItem = $this->createMock(QuoteItem::class);
        $bundleItem->method('getChildren')->willReturn([$childA, $childB]);

        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(1);
        $quote->method('addProduct')->willReturn($bundleItem);

        $saved = [];
        $itemResource = $this->createMock(ItemResource::class);
        $itemResource->method('save')->willReturnCallback(
            function ($item) use (&$saved, $itemResource) {
                $saved[] = $item;
                return $itemResource;
            }
        );

        $qty = $this->createService($itemResource)->execute(
            $quote,
            [
                'price' => ['currency' => 'EUR'],
                'products' => [
                    ['id' => self::BUNDLE_ID, 'quantity' => 1, 'price' => '399.00'],
                    ['id' => self::BUNDLE_ID, 'quantity' => 1, 'price' => '399.00'],
                ],
            ],
            $this->createMock(Store::class)
        );

        self::assertSame(2, $qty);
        self::assertSame(
            [$bundleItem, $childA, $childB, $bundleItem, $childA, $childB],
            $saved,
            'Every order line must save the bundle item and each of its children.'
        );
    }

    private function createService(ItemResource $itemResource): Add
    {
        $config = $this->createMock(ConfigProvider::class);
        $config->method('importBundleProducts')->willReturn(true);
        $config->method('priceIncludesTax')->willReturn(true);
        $config->method('isCrossBorderTradeEnabled')->willReturn(true);

        $bundleType = $this->createMock(BundleType::class);
        $bundleType->method('getOptionsIds')->willReturn([]);
        $bundleType->method('getSelectionsCollection')->willReturn([]);

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeInstance'])
            ->getMock();
        $product->method('getTypeInstance')->willReturn($bundleType);
        $product->setData('type_id', 'bundle');

        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->method('getById')->willReturn($product);

        $objectFactory = $this->createMock(ObjectFactory::class);
        $objectFactory->method('create')->willReturnCallback(
            fn (array $data) => new DataObject($data)
        );

        return new Add(
            $config,
            $productRepository,
            $this->createMock(StockRegistryInterface::class),
            $this->createMock(CheckoutSession::class),
            $this->createMock(ResourceConnection::class),
            $this->createMock(TaxCalculation::class),
            $objectFactory,
            $itemResource,
            $this->createMock(CurrencyConverter::class)
        );
    }
}
