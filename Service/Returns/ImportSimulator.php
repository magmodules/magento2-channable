<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Import test returns with random product
 */
class ImportSimulator
{

    /**
     * Available options
     */
    public const PARAMS = ['product_id'];

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollection;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Random
     */
    private $random;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var ImportReturn
     */
    private $importReturn;

    /**
     * @param ImportReturn               $importReturn
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory   $productCollection
     * @param ConfigProvider             $configProvider
     * @param Random                     $random
     */
    public function __construct(
        ImportReturn $importReturn,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollection,
        ConfigProvider $configProvider,
        Random $random
    ) {
        $this->importReturn = $importReturn;
        $this->productRepository = $productRepository;
        $this->productCollection = $productCollection;
        $this->configProvider = $configProvider;
        $this->random = $random;
    }

    /**
     * Import test return with random product
     *
     * @param int   $storeId
     * @param array $params
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(int $storeId, array $params = []): array
    {
        $this->storeId = (int)$storeId;

        if (!$this->configProvider->isReturnsEnabled((int)$storeId)) {
            throw new LocalizedException(
                __('Returns import not enabled for this store (Store ID: %1)', $this->storeId)
            );
        }

        return $this->importReturn->execute($this->getTestData($params), $storeId);
    }

    /**
     * Get test data in Channable Returns format
     *
     * @param array $params
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTestData($params = []): array
    {
        $this->productId = !empty($params['product_id']) ? $params['product_id'] : null;
        $product = $this->getProductData();
        $random = $this->random->getRandomString(5, '0123456789');

        return [
            "status"       => "new",
            "channel_name" => "Channable",
            "channel_id"   => "TEST-" . $random,
            "channable_id" => $random,
            "item"         => [
                "id"       => $product['id'],
                "order_id" => 99999,
                "gtin"     => $product['sku'],
                "title"    => $product['name'],
                "quantity" => 1,
                "reason"   => "Test return",
                "comment"  => "Do not process"
            ],
            "customer"     => [
                "gender"     => "male",
                "first_name" => "Test",
                "last_name"  => "Channable",
                "email"      => "dontemail@me.net",
            ],
            "address"      => [
                "first_name"   => "Test",
                "last_name"    => "Channable",
                "email"        => "dontemail@me.net",
                "street"       => "Test street",
                "house_number" => "1",
                "address1"     => "Test street 1 bis",
                "address2"     => null,
                "city"         => "Test",
                "country_code" => "NL",
                "zip_code"     => "1234 AB",
            ]
        ];
    }

    /**
     * Get product data array
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductData(): array
    {
        if ($this->productId) {
            $product = $this->productRepository->getById($this->productId);
        } else {
            $product = $this->getRandomProduct();
        }

        return [
            'id'    => $product->getId(),
            'price' => $product->getFinalPrice(),
            'sku'   => $product->getSku(),
            'name'  => $product->getName(),
        ];
    }

    /**
     * Get random enabled simple product
     *
     * @return DataObject
     */
    private function getRandomProduct(): DataObject
    {
        $collection = $this->productCollection->create();
        $collection->addAttributeToSelect(['entity_id', 'sku', 'name'])
            ->addStoreFilter($this->storeId)
            ->addPriceData()
            ->addAttributeToFilter(
                'type_id',
                Type::TYPE_SIMPLE
            )
            ->addAttributeToFilter(
                'status',
                Status::STATUS_ENABLED
            )
            ->setPageSize(1);

        $collection->getSelect()->orderRand();

        return $collection->getFirstItem();
    }
}
