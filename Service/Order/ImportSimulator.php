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
use Magento\Framework\App\Area;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\App\Emulation;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;

/**
 * Simulates an order import with a randomly selected product.
 */
class ImportSimulator
{

    public const PARAMS = ['country', 'lvb', 'product_id', 'qty', 'discount'];
    private const ORDER_IMPORT_DISABLED = 'Order import not enabled for this store (Store ID: %1)';

    private Import $import;
    private ProductRepositoryInterface $productRepository;
    private ProductCollectionFactory $productCollection;
    private ConfigProvider $configProvider;
    private Random $random;
    private ChannableOrderRepository $channableOrderRepository;
    private Emulation $appEmulation;
    private ?int $storeId = null;

    public function __construct(
        Import $import,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollection,
        ConfigProvider $configProvider,
        Random $random,
        ChannableOrderRepository $channableOrderRepository,
        Emulation $appEmulation
    ) {
        $this->import = $import;
        $this->productRepository = $productRepository;
        $this->productCollection = $productCollection;
        $this->configProvider = $configProvider;
        $this->random = $random;
        $this->channableOrderRepository = $channableOrderRepository;
        $this->appEmulation = $appEmulation;
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
        $this->storeId = $storeId;
        if (!$this->configProvider->isOrderEnabled($storeId)) {
            $errorMsg = self::ORDER_IMPORT_DISABLED;
            throw new CouldNotImportOrder(__($errorMsg, $this->storeId));
        }

        $channableOrder = $this->channableOrderRepository->createByDataArray(
            $this->getTestData($params),
            $storeId
        );

        try {
            $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            return $this->import->execute($channableOrder);
        } catch (\Exception $exception) {
            $errorMsg = $exception->getMessage();
            throw new CouldNotImportOrder(__($errorMsg));
        } finally {
            $this->appEmulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Get test data in Channable Order format
     *
     * @param array $params
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTestData(array $params): array
    {
        $country = !empty($params['country']) ? $params['country'] : 'NL';
        $product = $this->getProductData($params);
        $discount = !empty($params['discount']) ? (float)$params['discount'] : 0;
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
                "business_order" => false,
            ],
            "billing" => [
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
                "vat_number" => "NL123456790B01"
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
                "pickup_point_name" => "Albert Heijn: UTRECHT"
            ],
            "price" => [
                "payment_method" => "bol",
                "currency" => "EUR",
                "subtotal" => $product['price'],
                "payment" => 0,
                "shipping" => 0,
                "discount" => $discount,
                "total" => $product['price'] - $discount,
                "transaction_fee" => 0,
                "commission" => round($product['price'] * 0.10, 2),
            ],
            "products" => [
                [
                    "id" => $product['id'],
                    "quantity" => !empty($params['qty']) ? (float)$params['qty'] : 1,
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
     * @param array $params
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductData(array $params): array
    {
        $productId = $params['product_id'] ?? null;
        $fixedPrice = !empty($params['price']) ? (float)$params['price'] : null;
        $product = $productId ? $this->productRepository->getById((int) $productId) : $this->getRandomProduct();

        return [
            'id' => (int) $product->getId(),
            'price' => $this->getPrice($product, $fixedPrice),
            'sku' => (string) $product->getSku(),
            'name' => (string) $product->getName(),
        ];
    }

    /**
     * Selects a random enabled product from the catalog.
     *
     * @return DataObject A randomly selected product.
     */
    private function getRandomProduct(): DataObject
    {
        $productTypes = [Type::TYPE_SIMPLE];

        if ($this->configProvider->importGroupedProducts()) {
            $productTypes[] = 'grouped';
        }
        if ($this->configProvider->importBundleProducts()) {
            $productTypes[] = 'bundle';
        }

        $collection = $this->productCollection->create()
            ->addStoreFilter($this->storeId)
            ->addAttributeToFilter('type_id', ['in' => $productTypes])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToSelect(['entity_id', 'sku', 'name', 'type_id'])
            ->addPriceData()
            ->setPageSize(1);

        $collection->getSelect()->orderRand();
        return $collection->getFirstItem();
    }

    /**
     * Determines the correct price for a product.
     *
     * @param $product
     * @param float|null $fixedPrice Optional fixed price override.
     * @return float The product's price.
     */
    private function getPrice($product, ?float $fixedPrice = null): float
    {
        if ($fixedPrice !== null) {
            return $fixedPrice;
        }

        if ($product->getTypeId() === Type::TYPE_BUNDLE) {
            return (float) $product->getPriceInfo()->getPrice('regular_price')->getMaximalPrice()->getValue();
        }

        return (float) $product->getFinalPrice();
    }
}
