<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Items;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;
use Magento\Bundle\Model\Product\Price;

/**
 * Add items to quote
 */
class Add
{

    /**
     * Exception messages
     */
    public const EMPTY_ITEMS_EXCEPTION = 'No products found in order';
    public const PRODUCT_NOT_FOUND_EXCEPTION = 'Product "%1" not found in catalog (ID: %2)';
    public const PRODUCT_EXCEPTION = 'Product "%1" => %2';
    /**
     * @var ObjectFactory
     */
    protected $objectFactory;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var Item
     */
    private $itemResourceModel;

    /**
     * @param ConfigProvider $configProvider
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param CheckoutSession $checkoutSession
     * @param ResourceConnection $resourceConnection
     * @param TaxCalculation $taxCalculation
     */
    public function __construct(
        ConfigProvider $configProvider,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        CheckoutSession $checkoutSession,
        ResourceConnection $resourceConnection,
        TaxCalculation $taxCalculation,
        ObjectFactory $objectFactory,
        Item $itemResourceModel
    ) {
        $this->configProvider = $configProvider;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->checkoutSession = $checkoutSession;
        $this->resourceConnection = $resourceConnection;
        $this->taxCalculation = $taxCalculation;
        $this->objectFactory = $objectFactory;
        $this->itemResourceModel = $itemResourceModel;
    }

    /**
     * Add items to Quote by OrderData array and returns qty
     *
     * @param Quote $quote
     * @param array $data
     * @param StoreInterface $store
     * @param bool $lvbOrder
     * @return int
     * @throws CouldNotImportOrder
     */
    public function execute(Quote $quote, array $data, StoreInterface $store, bool $lvbOrder = false): int
    {
        $this->setCheckoutSessionData($lvbOrder, $quote->getStoreId());
        $qty = 0;

        if (empty($data['products'])) {
            $exceptionMsg = self::EMPTY_ITEMS_EXCEPTION;
            throw new CouldNotImportOrder(__($exceptionMsg));
        }
        $isBusinessOrder = $this->configProvider->isBusinessOrderEnabled((int)$store->getId()) &&
            isset($data['customer']['business_order']) && ($data['customer']['business_order'] == true);

        try {
            foreach ($data['products'] as $item) {
                $product = $this->getProductById((int)$item['id'], (int)$store->getStoreId());
                $price = $this->getProductPrice($item, $product, $store, $quote, $isBusinessOrder);
                $product = $this->setProductData($product, $price, $store, $lvbOrder);
                if ($isBusinessOrder) {
                    $product->setTaxClassId(0);
                }

                switch ($product->getTypeId()) {
                    case 'grouped':
                        if (!$this->configProvider->importGroupedProducts()) {
                            throw new CouldNotImportOrder(__('Import grouped products is not enabled'));
                        }
                        $addedItem = $this->addGroupedProduct($quote, $product, (int)$item['quantity']);
                        break;
                    case 'bundle':
                        if (!$this->configProvider->importBundleProducts()) {
                            throw new CouldNotImportOrder(__('Import bundle products is not enabled'));
                        }
                        $addedItem = $this->addBundleProduct($quote, $product, (int)$item['quantity']);
                        break;
                    default:
                        $addedItem = $quote->addProduct($product, (int)$item['quantity']);
                }

                if (is_string($addedItem)) {
                    throw new CouldNotImportOrder(__($addedItem));
                }

                $addedItem->setOriginalCustomPrice($price);
                $this->itemResourceModel->save($addedItem);
                $qty += (int)$item['quantity'];
            }
        } catch (Exception $exception) {
            $exceptionMsg = $this->reformatException($exception, $item, (int)$store->getStoreId());
            throw new CouldNotImportOrder($exceptionMsg);
        }

        return $qty;
    }

    /**
     * Add Channable specific data to the checkout session
     *
     * @param bool $lvbOrder
     * @param int $storeId
     *
     * @return void
     */
    private function setCheckoutSessionData(bool $lvbOrder = false, int $storeId = 0): void
    {
        $this->checkoutSession->setChannableSkipQtyCheck(
            $this->configProvider->getEnableBackorders($storeId) ||
            $lvbOrder && $this->configProvider->disableStockMovementForLvbOrders($storeId)
        );

        $this->checkoutSession->setChannableSkipReservation(
            $lvbOrder && $this->configProvider->disableStockMovementForLvbOrders($storeId)
        );
    }

    /**
     * Get Product by ID
     *
     * @param int $productId
     * @param int $storeId
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProductById(int $productId, int $storeId): ProductInterface
    {
        return $this->productRepository->getById($productId, false, $storeId);
    }

    /**
     * Calculate Product Price, depends on Tax Rate and Tax Settings
     *
     * @param array $item
     * @param ProductInterface $product
     * @param StoreInterface $store
     * @param Quote $quote
     *
     * @return float
     */
    private function getProductPrice(
        array $item,
        ProductInterface $product,
        StoreInterface $store,
        Quote $quote,
        bool $isBusinessOrder
    ): float {
        if ($isBusinessOrder) {
            return (float)$item['price'];
        }
        $price = (float)$item['price'] - $this->getProductWeeTax($product, $quote);
        if (!$this->configProvider->getNeedsTaxCalulcation('price', (int)$store->getId())) {
            $request = $this->taxCalculation->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                null,
                $store
            );
            $percent = $this->taxCalculation->getRate(
                $request->setData('product_class_id', $product->getData('tax_class_id'))
            );
            $price = $price / (100 + $percent) * 100;
        }

        return $price;
    }

    /**
     * Get Product Wee Tax (FPT)
     *
     * @param ProductInterface $product
     * @param Quote $quote
     * @return float
     */
    private function getProductWeeTax(ProductInterface $product, Quote $quote): float
    {
        if (!$this->configProvider->deductFptTax($quote->getStoreId())) {
            return 0;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('weee_tax');

        if (!$connection->isTableExists($tableName)) {
            return 0;
        }

        $select = $connection->select()
            ->from($tableName, 'value')
            ->where('entity_id = ?', $product->getId())
            ->where('country = ?', $quote->getBillingAddress()->getCountryId())
            ->limit(1);

        return (float)$connection->fetchOne($select);
    }

    /**
     * Set product data
     *
     * @param ProductInterface $product
     * @param float $price
     * @param StoreInterface $store
     * @param bool $lvbOrder
     *
     * @return ProductInterface
     */
    private function setProductData(
        ProductInterface $product,
        float $price,
        StoreInterface $store,
        bool $lvbOrder
    ): ProductInterface {
        if ($this->configProvider->disableStockCheckOnImport((int)$store->getId()) || $lvbOrder) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $stockItem->setUseConfigBackorders(false)
                ->setBackorders(true)
                ->setIsInStock(true);
            $productData = $product->getData();
            $productData['quantity_and_stock_status']['is_in_stock'] = true;
            $productData['is_in_stock'] = true;
            $productData['is_salable'] = true;
            $productData['stock_data'] = $stockItem;
            $product->setData($productData);
        }

        $product->setPrice($price)
            ->setFinalPrice($price)
            ->setSpecialPrice($price)
            ->setTierPrice([])
            ->setOriginalCustomPrice($price)
            ->setSpecialFromDate(null)
            ->setSpecialToDate(null);

        return $product;
    }

    /**
     * @param Quote $quote
     * @param ProductInterface $product
     * @param int $qty
     * @return Quote\Item|string
     * @throws LocalizedException
     */
    private function addGroupedProduct(Quote $quote, ProductInterface $product, int $qty)
    {
        $superGroup = ['super_group' => []];
        foreach ($product->getTypeInstance(true)->getAssociatedProducts($product) as $child) {
            $superGroup['super_group'][$child->getId()] = $child->getQty() * $qty;
        }

        if (!array_sum($superGroup['super_group'])) {
            throw new CouldNotImportOrder(
                __(
                    'No default quantities found for grouped product "%1"',
                    $product->getSku()
                )
            );
        }

        $request = $this->objectFactory->create($superGroup);
        return $quote->addProduct($product, $request);
    }

    /**
     * @param Quote $quote
     * @param ProductInterface $product
     * @param int $qty
     * @return Quote\Item|string
     * @throws LocalizedException
     */
    private function addBundleProduct(Quote $quote, ProductInterface $product, int $qty)
    {
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );

        $bundleOptions = [];
        foreach ($selectionCollection as $selection) {
            if ($selection->getIsDefault()) {
                $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
            }
        }

        $request = $this->objectFactory->create([
            'product' => $product->getId(),
            'bundle_option' => $bundleOptions,
            'qty' => $qty
        ]);
        $product->setPriceType(Price::PRICE_TYPE_FIXED);

        return $quote->addProduct($product, $request);
    }

    /**
     * Generate readable exception message
     *
     * @param Exception $exception
     * @param array $item
     * @param int $storeId
     * @return Phrase
     */
    private function reformatException(Exception $exception, array $item, int $storeId = 0): Phrase
    {
        try {
            $this->getProductById((int)$item['id'], $storeId);
        } catch (NoSuchEntityException $exception) {
            $exceptionMsg = self::PRODUCT_NOT_FOUND_EXCEPTION;
            return __(
                $exceptionMsg,
                !empty($item['title']) ? $item['title'] : __('*unknown*'),
                $item['id']
            );
        }

        $productException = self::PRODUCT_EXCEPTION;
        return __(
            $productException,
            !empty($item['title']) ? $item['title'] : __('*unknown*'),
            $exception->getMessage()
        );
    }
}
