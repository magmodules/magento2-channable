<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\Data\OrderInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;

/**
 * Import test order with random product
 */
class ImportSimulator
{

    /**
     * Available options
     */
    const PARAMS = ['country', 'lvb', 'product_id'];

    /**
     * @var Import
     */
    private $import;

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
     * @var ChannableOrderRepository
     */
    private $channableOrderRepository;

    /**
     * ImportSimulator constructor.
     * @param Import $import
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollection
     * @param ConfigProvider $configProvider
     * @param Random $random
     * @param ChannableOrderRepository $channableOrderRepository
     */
    public function __construct(
        Import $import,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollection,
        ConfigProvider $configProvider,
        Random $random,
        ChannableOrderRepository $channableOrderRepository
    ) {
        $this->import = $import;
        $this->productRepository = $productRepository;
        $this->productCollection = $productCollection;
        $this->configProvider = $configProvider;
        $this->random = $random;
        $this->channableOrderRepository = $channableOrderRepository;
    }

    /**
     * Import test order with random product
     *
     * @param int $storeId
     * @param array $params
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotImportOrder
     */
    public function execute(int $storeId, array $params = []): OrderInterface
    {
        $this->storeId = (int)$storeId;

        if (!$this->configProvider->isOrderEnabled((int)$storeId)) {
            throw new CouldNotImportOrder(
                __('Order import not enabled for this store (Store ID: %1)', $this->storeId)
            );
        }
        $channableOrder = $this->channableOrderRepository->createByDataArray(
            $this->getTestData($params, $storeId),
            $storeId
        );
        return $this->import->execute($channableOrder);
    }

    /**
     * Get test data in Channable Order format
     *
     * @param array $params
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTestData($params = [], $storeId = null): array
    {
        $country = !empty($params['country']) ? $params['country'] : 'NL';
        $this->productId = !empty($params['product_id']) ? $params['product_id'] : null;

        $product = $this->getProductData();
        $random = $this->random->getRandomString(5, '0123456789');

        return [
            "channable_id" => $random,
            "channable_channel_label" => "Channable Test",
            "channel_id" => "TEST-" . $random,
            "channel_name" => "Channable",
            "is_test" => true,
            "order_status" => !empty($params['lvb']) ? "shipped" : "not_shipped",
            "shipment_method" => "Prime",
            "channel_customer_number" => "123456789",
            "shipment_promise" => "2021-09-06 23:00:00000000",
            "customer" => [
                "gender" => "male",
                "phone" => "01234567890",
                "mobile" => "01234567890",
                "email" => "dontemail@me.net",
                "first_name" => "Test",
                "middle_name" => "From",
                "last_name" => "Channable",
                "company" => "TestCompany",
            ],
            "billing" => [
                "first_name" => "Test",
                "middle_name" => "From",
                "last_name" => "Channable",
                "company" => "Do Not Ship",
                "vat_id" => 'NL0001',
                "email" => "dontemail@me.net",
                "address_line_1" => "Billing Line 1",
                "address_line_2" => "Billing Line 2",
                "street" => "Street",
                "house_number" => 1,
                "house_number_ext" => "",
                "zip_code" => "1000 AA",
                "city" => "UTRECHT",
                "country_code" => $country,
                "state" => $country == "US" ? "Texas" : "",
                "state_code" => $country == "US" ? "TX" : "",
            ],
            "shipping" => [
                "first_name" => "Test",
                "middle_name" => "From",
                "last_name" => "Channable",
                "company" => "Do Not Ship",
                "email" => "dontemail@me.net",
                "address_line_1" => "Billing Line 1",
                "address_line_2" => "Billing Line 2",
                "street" => "Street",
                "house_number" => 1,
                "house_number_ext" => "",
                "zip_code" => "1000 AA",
                "city" => "UTRECHT",
                "country_code" => $country,
                "state" => $country == "US" ? "Texas" : "",
                "state_code" => $country == "US" ? "TX" : "",
            ],
            "price" => [
                "payment_method" => "bol",
                "currency" => "EUR",
                "subtotal" => $product['price'],
                "payment" => 0,
                "shipping" => 0,
                "discount" => 10,
                "total" => $product['price'],
                "transaction_fee" => 6.2,
                "commission" => round($product['price'] * 0.10, 2),
            ],
            "products" => [
                [
                    "id" => $product['id'],
                    "quantity" => 2,
                    "price" => $product['price'],
                    "ean" => $product['sku'],
                    "reference_code" => $product['sku'],
                    "title" => $product['name'],
                    "delivery_period" => "2019-04-17+02=>00",
                    "shipping" => 0,
                    "commission" => round($product['price'] * 0.10, 2),
                ],
            ],
            "memo" => "Test Order from Channable",
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
            'id' => $product->getId(),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
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
