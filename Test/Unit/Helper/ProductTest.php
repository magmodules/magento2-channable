<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Test\Unit\Helper;

use Magento\Bundle\Model\ResourceModel\Selection as BundleResource;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\Product\Media\Config as CatalogProductMediaConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filter\FilterManager;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedResource;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Service\Product\InventoryData;
use Magmodules\Channable\Service\Product\MediaData;
use Magmodules\Channable\Service\Product\PriceData;
use PHPUnit\Framework\TestCase;

/**
 * Magento stores a cleared image attribute as the literal string 'no_selection'. Handing that to
 * getMediaUrl() produces a URL that looks real, so marketplaces queue it, fetch it and show a
 * broken-image placeholder on the listing.
 */
class ProductTest extends TestCase
{
    private const MEDIA_BASE = 'https://example.test/media/catalog/product';

    public function testClearedImageAttributeYieldsNoValue(): void
    {
        $helper = $this->createHelper();

        $value = $helper->getValue(
            ['source' => 'helm_open', 'type' => 'media_image'],
            $this->product(['helm_open' => 'no_selection'])
        );

        self::assertSame('', $value);
    }

    public function testImageAttributeWithAnImageYieldsItsMediaUrl(): void
    {
        $helper = $this->createHelper();

        $value = $helper->getValue(
            ['source' => 'helm_open', 'type' => 'media_image'],
            $this->product(['helm_open' => '/v/i/vinz_como.png'])
        );

        self::assertSame(self::MEDIA_BASE . '/v/i/vinz_como.png', $value);
    }

    /**
     * An attribute that was never filled already left the feed empty before this guard existed;
     * that path stays untouched, so a cleared attribute now ends up in the same place.
     */
    public function testUnsetImageAttributeYieldsNoUrl(): void
    {
        $helper = $this->createHelper();

        $value = $helper->getValue(
            ['source' => 'helm_open', 'type' => 'media_image'],
            $this->product([])
        );

        self::assertEmpty($value);
    }

    private function product(array $data): ProductModel
    {
        $product = $this->createMock(ProductModel::class);
        $product->method('getData')->willReturnCallback(
            static fn (string $key) => $data[$key] ?? null
        );

        return $product;
    }

    private function createHelper(): ProductHelper
    {
        $mediaConfig = $this->createMock(CatalogProductMediaConfig::class);
        $mediaConfig->method('getMediaUrl')->willReturnCallback(
            static fn (string $file) => self::MEDIA_BASE . $file
        );

        return new ProductHelper(
            $this->createMock(Context::class),
            $this->createMock(GalleryReadHandler::class),
            $mediaConfig,
            $this->createMock(ProductImageHelper::class),
            $this->createMock(EavConfig::class),
            $this->createMock(FilterManager::class),
            $this->createMock(AttributeSetRepositoryInterface::class),
            $this->createMock(GroupedResource::class),
            $this->createMock(BundleResource::class),
            $this->createMock(ConfigurableResource::class),
            $this->createMock(InventoryData::class),
            $this->createMock(MediaData::class),
            $this->createMock(PriceData::class),
            $this->createMock(ProductResource::class),
            $this->createMock(LogRepository::class)
        );
    }
}
