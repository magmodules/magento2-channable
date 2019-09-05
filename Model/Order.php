<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Order as OrderlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Sales\Model\Service\OrderService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Framework\DB\Transaction;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\ShippingFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Framework\Registry;

/**
 * Class Order
 *
 * @package Magmodules\Channable\Model
 */
class Order
{

    public $weight = 0;
    public $total = 0;
    public $storeId = null;
    public $importCustomer = false;
    public $seperateHousenumber = 1;
    public $numberOfStreetLines = 1;
    public $backorders = false;
    public $lvb = false;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductFactory
     */
    private $product;
    /**
     * @var FormKey
     */
    private $formkey;
    /**
     * @var QuoteFactory
     */
    private $quote;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var AddressFactory
     */
    private $addressFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var OrderConverter
     */
    private $orderConverter;
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;
    /**
     * @var CartManagementInterface
     */
    private $cartManagementInterface;
    /**
     * @var RateRequestFactory
     */
    private $rateRequestFactory;
    /**
     * @var TaxCalculationn
     */
    private $taxCalculation;
    /**
     * @var GeneralHelper
     */
    private $generalHelper;
    /**
     * @var OrderlHelper
     */
    private $orderHelper;
    /**
     * @var ShippingFactory
     */
    private $shippingFactory;
    /**
     * @var ShipmentCollectionFactory
     */
    private $shipmentCollectionFactory;
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ItemsForReindex
     */
    private $itemsForReindex;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * Order constructor.
     *
     * @param StoreManagerInterface       $storeManager
     * @param ProductFactory              $product
     * @param FormKey                     $formkey
     * @param QuoteFactory                $quote
     * @param QuoteManagement             $quoteManagement
     * @param CustomerFactory             $customerFactory
     * @param AddressFactory              $addressFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderService                $orderService
     * @param OrderRepositoryInterface    $orderRepository
     * @param StockRegistryInterface      $stockRegistry
     * @param InvoiceService              $invoiceService
     * @param OrderConverter              $orderConverter
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Transaction                 $transaction
     * @param CartRepositoryInterface     $cartRepositoryInterface
     * @param CartManagementInterface     $cartManagementInterface
     * @param SearchCriteriaBuilder       $searchCriteriaBuilder
     * @param TaxCalculationn             $taxCalculation
     * @param RateRequestFactory          $rateRequestFactory
     * @param ShippingFactory             $shippingFactory
     * @param ShipmentCollectionFactory   $shipmentCollectionFactory
     * @param OrderCollectionFactory      $orderCollectionFactory
     * @param GeneralHelper               $generalHelper
     * @param OrderlHelper                $orderHelper
     * @param CheckoutSession             $checkoutSession
     * @param ItemsForReindex             $itemsForReindex
     * @param Registry                    $registry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductFactory $product,
        FormKey $formkey,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderService $orderService,
        OrderRepositoryInterface $orderRepository,
        StockRegistryInterface $stockRegistry,
        InvoiceService $invoiceService,
        OrderConverter $orderConverter,
        ShipmentRepositoryInterface $shipmentRepository,
        Transaction $transaction,
        CartRepositoryInterface $cartRepositoryInterface,
        CartManagementInterface $cartManagementInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TaxCalculationn $taxCalculation,
        RateRequestFactory $rateRequestFactory,
        ShippingFactory $shippingFactory,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        GeneralHelper $generalHelper,
        OrderlHelper $orderHelper,
        CheckoutSession $checkoutSession,
        ItemsForReindex $itemsForReindex,
        Registry $registry
    ) {
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->stockRegistry = $stockRegistry;
        $this->invoiceService = $invoiceService;
        $this->orderConverter = $orderConverter;
        $this->shipmentRepository = $shipmentRepository;
        $this->transaction = $transaction;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->taxCalculation = $taxCalculation;
        $this->generalHelper = $generalHelper;
        $this->orderHelper = $orderHelper;
        $this->shippingFactory = $shippingFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->itemsForReindex = $itemsForReindex;
        $this->registry = $registry;
    }

    /**
     * @param $data
     * @param $storeId
     *
     * @return array
     */
    public function importOrder($data, $storeId)
    {

        $this->storeId = $storeId;
        $this->importCustomer = $this->orderHelper->getImportCustomer($storeId);
        $this->seperateHousenumber = $this->orderHelper->getSeperateHousenumber($storeId);
        $this->numberOfStreetLines = $this->orderHelper->getCustomerStreetLines($storeId);
        $this->backorders = $this->orderHelper->getEnableBackorders($storeId);
        $this->lvb = ($data['order_status'] == 'shipped') ? true : false;

        if ($errors = $this->checkItems($data['products'])) {
            return $this->jsonRepsonse($errors, '', $data['channable_id']);
        }

        try {
            $store = $this->storeManager->getStore($storeId);
            $cartId = $this->cartManagementInterface->createEmptyCart();
            $cart = $this->cartRepositoryInterface->get($cartId)->setStore($store)->setCurrency()->setIsSuperMode(true);
            $customerId = $this->setCustomerCart($cart, $store, $data);

            $billingAddress = $this->getAddressData('billing', $data, $customerId);
            if (!empty($billingAddress['errors'])) {
                return $this->jsonRepsonse($billingAddress['errors'], '', $data['channable_id']);
            } else {
                $cart->getBillingAddress()->addData($billingAddress);
            }

            $shippingAddress = $this->getAddressData('shipping', $data, $customerId);
            if (!empty($shippingAddress['errors'])) {
                return $this->jsonRepsonse($shippingAddress['errors'], '', $data['channable_id']);
            } else {
                $cart->getShippingAddress()->addData($shippingAddress);
            }

            $itemCount = $this->addProductsToQuote($cart, $data, $store);
            $shippingPriceCal = $this->getShippingPrice($cart, $data, $store);

            $this->checkoutSession->setChannableEnabled(1);
            $this->checkoutSession->setChannableShipping($shippingPriceCal);

            $shippingMethod = $this->getShippingMethod($cart, $store, $itemCount);
            $shippingAddress = $cart->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);

            $cart->setPaymentMethod('channable');
            $cart->setInventoryProcessed(false);
            $cart->getPayment()->importData(['method' => 'channable']);
            $cart->collectTotals();
            $cart->save();

            $cart = $this->cartRepositoryInterface->get($cart->getId());

            if ($this->lvb && $this->orderHelper->getLvbSkipStock($store->getId())) {
                $cart->setInventoryProcessed(true);
                $this->itemsForReindex->clear();
            }

            $orderId = $this->cartManagementInterface->placeOrder($cart->getId());

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);
            if ($this->orderHelper->getUseChannelOrderId($storeId)) {
                $newIncrementId = $this->orderHelper->getUniqueIncrementId($data['channel_id'], $storeId);
                $order->setIncrementId($newIncrementId);
            }

            $this->addPaymentData($order, $data);

            if ($this->orderHelper->getInvoiceOrder($storeId)) {
                $this->invoiceOrder($order);
            }

            if ($this->lvb && $this->orderHelper->getLvbAutoShip($storeId)) {
                $this->shipOrder($order);
            }
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('importOrder: ' . $data['channable_id'], $e->getMessage());
            return $this->jsonRepsonse($e->getMessage(), '', $data['channable_id']);
        }

        $this->checkoutSession->unsChannableEnabled();
        $this->checkoutSession->unsChannableShipping();

        return $this->jsonRepsonse('', $order->getIncrementId());
    }

    /**
     * @param  array $items
     *
     * @return array|bool
     */
    private function checkItems($items)
    {
        $error = [];
        foreach ($items as $item) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->product->create()->load($item['id']);
            if (!$product->getId()) {
                if (!empty($item['title']) && !empty($item['id'])) {
                    $error[] = __(
                        'Product "%1" not found in catalog (ID: %2)',
                        $product['title'],
                        $product['id']
                    );
                } else {
                    $error[] = __('Product not found in catalog');
                }
            } else {
                if ($product->getTypeId() == 'configurable') {
                    $error[] = __(
                        'Product "%1" can not be ordered, as this is the configurable parent (ID: %2)',
                        $product->getName(),
                        $product->getEntityId()
                    );
                }
                if (!$product->isSalable() && !$this->lvb && !$this->backorders) {
                    $error[] = __(
                        'Product "%1" not available in requested quantity (ID: %2)',
                        $product->getName(),
                        $product->getEntityId()
                    );
                }
                $options = $product->getRequiredOptions();
                if (!empty($options)) {
                    $error[] = __(
                        'Product "%1" has required options, this is not supported (ID: %2)',
                        $product->getName(),
                        $product->getEntityId()
                    );
                }
            }
        }
        if (!empty($error)) {
            return $error;
        } else {
            return false;
        }
    }

    /**
     * @param string $errors
     * @param string $orderId
     * @param string $channableId
     *
     * @return array
     */
    private function jsonRepsonse($errors = '', $orderId = '', $channableId = '')
    {
        $response = $this->orderHelper->jsonResponse($errors, $orderId);
        if ($this->orderHelper->isLoggingEnabled()) {
            $this->orderHelper->addTolog($channableId, $response);
        }

        return $response;
    }

    /**
     * @param CartRepositoryInterface $cart
     * @param StoreManagerInterface   $store
     * @param array                   $data
     *
     * @return int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setCustomerCart($cart, $store, $data)
    {
        $storeId = $store->getId();
        $websiteId = $store->getWebsiteId();
        $email = $this->orderHelper->cleanEmail($data['customer']['email']);

        if ($this->importCustomer) {
            $customerGroupId = $this->orderHelper->getCustomerGroupId($storeId);
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($email);
            if (!$customerId = $customer->getEntityId()) {
                $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($data['customer']['first_name'])
                    ->setMiddlename($data['customer']['middle_name'])
                    ->setLastname($data['customer']['last_name'])
                    ->setEmail($email)
                    ->setPassword($email)
                    ->setGroupId($customerGroupId)
                    ->save();
                $customerId = $customer->getId();
            }
            $customer = $this->customerRepository->getById($customerId);
            $cart->assignCustomer($customer);
        } else {
            $customerId = 0;
            $cart->setCustomerId($customerId)
                ->setCustomerEmail($email)
                ->setCustomerFirstname($data['customer']['first_name'])
                ->setCustomerMiddlename($data['customer']['middle_name'])
                ->setCustomerLastname($data['customer']['last_name'])
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        return $customerId;
    }

    /**
     * @param        $type
     * @param array  $order
     * @param string $customerId
     *
     * @return array
     */
    public function getAddressData($type, $order, $customerId = null)
    {
        if ($type == 'billing') {
            $address = $order['billing'];
        } else {
            $address = $order['shipping'];
        }

        $telephone = '000';
        if (!empty($order['customer']['phone'])) {
            $telephone = $order['customer']['phone'];
        }
        if (!empty($order['customer']['mobile'])) {
            $telephone = $order['customer']['mobile'];
        }

        $addressData = [
            'customer_id' => $customerId,
            'company'     => $address['company'],
            'firstname'   => $address['first_name'],
            'middlename'  => $address['middle_name'],
            'lastname'    => $address['last_name'],
            'street'      => $this->getStreet($address),
            'city'        => $address['city'],
            'country_id'  => $address['country_code'],
            'region'      => !empty($address['state_code']) ? $address['state_code'] : null,
            'postcode'    => $address['zip_code'],
            'telephone'   => $telephone,
        ];

        if ($this->importCustomer) {
            $newAddress = $this->addressFactory->create();
            $newAddress->setCustomerId($customerId)
                ->setCompany($addressData['company'])
                ->setFirstname($addressData['firstname'])
                ->setMiddlename($addressData['middlename'])
                ->setLastname($addressData['lastname'])
                ->setStreet($addressData['street'])
                ->setCity($addressData['city'])
                ->setCountryId($addressData['country_id'])
                ->setRegion($addressData['region'])
                ->setPostcode($addressData['postcode'])
                ->setTelephone($addressData['telephone']);

            if ($type == 'billing') {
                $newAddress->setIsDefaultBilling('1')->setSaveInAddressBook('1');
            } else {
                $newAddress->setIsDefaultShipping('1')->setSaveInAddressBook('1');
            }

            try {
                $newAddress->save();
            } catch (\Exception $e) {
                return ['errors' => $e->getMessage()];
            }
        }

        return $addressData;
    }

    /**
     * @param array $address
     *
     * @return string
     */
    private function getStreet($address)
    {
        if ($this->seperateHousenumber || empty($address['address_line_1'])) {
            $street[] = $address['street'];
            $street[] = $address['house_number'];
            $street[] = $address['house_number_ext'];
        } else {
            $street[] = $address['address_line_1'];
            $street[] = $address['address_line_2'];
            $street[] = null;
        }

        if ($this->numberOfStreetLines == 1) {
            return trim(implode(' ', $street));
        }

        if ($this->numberOfStreetLines == 2) {
            $street = [$street[0], trim($street[1] . ' ' . $street[2])];
        }

        return implode("\n", $street);
    }

    /**
     * @param CartRepositoryInterface $cart
     * @param array                   $data
     * @param StoreManagerInterface   $store
     *
     * @return int
     */
    private function addProductsToQuote($cart, $data, $store)
    {
        $qty = 0;
        $taxCalculation = $this->orderHelper->getNeedsTaxCalulcation('price', $store->getId());
        $shippingAddressId = $cart->getShippingAddress();
        $billingAddressId = $cart->getBillingAddress();

        foreach ($data['products'] as $item) {
            $product = $this->product->create()->load($item['id']);
            $stockItem = $this->stockRegistry->getStockItem($item['id']);
            $price = $item['price'];

            if (empty($taxCalculation)) {
                $taxClassId = $product->getData('tax_class_id');
                $request = $this->taxCalculation->getRateRequest($shippingAddressId, $billingAddressId, null, $store);
                $percent = $this->taxCalculation->getRate($request->setProductClassId($taxClassId));
                $price = ($item['price'] / (100 + $percent) * 100);
            }

            $product->setPrice($price)->setFinalPrice($price)->setSpecialPrice($price)->setTierPrice([]);
            if ($this->orderHelper->getEnableBackorders($store->getId())) {
                $stockItem->setUseConfigBackorders(false)->setBackorders(true)->setIsInStock(true);
                $productData = $product->getData();
                $productData['quantity_and_stock_status']['is_in_stock'] = true;
                $productData['is_in_stock'] = true;
                $productData['is_salable'] = true;
                $productData['stock_data'] = $stockItem;
                $product->setData($productData);
            }

            $this->total += $price;
            $this->weight += ($product->getWeight() * intval($item['quantity']));
            $qty += intval($item['quantity']);
            $cart->addProduct($product, intval($item['quantity']));
        }

        if ($this->orderHelper->getEnableBackorders($store->getId())) {
            $this->registry->register('channable_skip_qty_check', true);
        }

        return $qty;
    }

    /**
     * @param CartRepositoryInterface $cart
     * @param                         $data
     * @param StoreManagerInterface   $store
     *
     * @return float|int
     */
    private function getShippingPrice($cart, $data, $store)
    {
        $taxCalculation = $this->orderHelper->getNeedsTaxCalulcation('shipping', $store->getId());
        $shippingPriceCal = $data['price']['shipping'];

        if (empty($taxCalculation)) {
            $shippingAddressId = $cart->getShippingAddress();
            $billingAddressId = $cart->getBillingAddress();
            $taxRateId = $this->orderHelper->getTaxClassShipping($store->getId());
            $request = $this->taxCalculation->getRateRequest($shippingAddressId, $billingAddressId, null, $store);
            $percent = $this->taxCalculation->getRate($request->setProductClassId($taxRateId));
            $shippingPriceCal = ($data['price']['shipping'] / (100 + $percent) * 100);
        }

        return $shippingPriceCal;
    }

    /**
     * @param CartRepositoryInterface $cart
     * @param StoreManagerInterface   $store
     * @param                         $itemCount
     *
     * @return mixed|null|string
     */
    private function getShippingMethod($cart, $store, $itemCount)
    {
        $shippingMethod = $this->orderHelper->getShippingMethod($store->getId());
        $shippingMethodFallback = $this->orderHelper->getShippingMethodFallback($store->getId());

        $destCountryId = $cart->getShippingAddress()->getCountryId();
        $destPostcode = $cart->getShippingAddress()->getPostcode();

        /** @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($cart->getAllItems());
        $request->setDestCountryId($destCountryId);
        $request->setDestPostcode($destPostcode);
        $request->setPackageValue($this->total);
        $request->setPackageValueWithDiscount($this->total);
        $request->setPackageWeight($this->weight);
        $request->setPackageQty($itemCount);
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());
        $request->setLimitCarrier('');
        $request->setBaseSubtotalInclTax($this->total);
        $shipping = $this->shippingFactory->create();
        $result = $shipping->collectRates($request)->getResult();

        if ($result) {
            $shippingRates = $result->getAllRates();
            if ($shippingMethod != 'channable_custom') {
                foreach ($shippingRates as $shippingRate) {
                    $method = $shippingRate->getCarrier() . '_' . $shippingRate->getMethod();
                    if ($method == $shippingMethod) {
                        return $shippingMethod;
                    }
                }
            } else {
                $priority = -1;
                $customCarrier = null;
                $prioritizedMethods = $this->orderHelper->getShippingCustomShippingMethods($store->getId());
                foreach ($shippingRates as $shippingRate) {
                    $method = $shippingRate->getCarrier() . '_' . $shippingRate->getMethod();
                    if (isset($prioritizedMethods[$method]) && $priority < $prioritizedMethods[$method]) {
                        $customCarrier = $method;
                        $priority = $prioritizedMethods[$method];
                    }
                }
                if ($customCarrier !== null) {
                    return $customCarrier;
                }
            }
        }

        return $shippingMethodFallback;
    }

    /**
     * @param OrderModel $order
     * @param array      $data
     */
    private function addPaymentData($order, $data)
    {
        $payment = $order->getPayment();
        if (!empty($data['channable_id'])) {
            $payment->setAdditionalInformation('channable_id', $data['channable_id']);
            $order->setChannableId($data['channable_id']);
        }

        if (!empty($data['channel_id'])) {
            $payment->setAdditionalInformation('channel_id', $data['channel_id']);
            $order->setChannelId($data['channel_id']);
        }

        if (!empty($data['price']['commission'])) {
            $commission = $data['price']['currency'] . ' ' . $data['price']['commission'];
            $payment->setAdditionalInformation('commission', $commission);
        }

        if (!empty($data['channel_name'])) {
            if ($this->lvb) {
                $payment->setAdditionalInformation('channel_name', ucfirst($data['channel_name']) . ' LVB');
            } else {
                $payment->setAdditionalInformation('channel_name', ucfirst($data['channel_name']));
            }
            $order->setChannelName($data['channel_name']);
        }

        if (!empty($data['channable_channel_label'])) {
            $payment->setAdditionalInformation('channel_label', $data['channable_channel_label']);
            $order->setChannelLabel($data['channable_channel_label']);
        }

        $itemRows = [];
        foreach ($data['products'] as $product) {
            $itemRows[] = [
                'title'           => $product['title'],
                'ean'             => $product['ean'],
                'delivery_period' => $product['delivery_period']
            ];
        }
        $payment->setAdditionalInformation('delivery', $itemRows);
        $this->orderRepository->save($order);
    }

    /**
     * @param OrderModel $order
     */
    private function invoiceOrder($order)
    {
        if ($order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();

                $order->setState(OrderModel::STATE_PROCESSING);
                if ($status = $this->orderHelper->getProcessingStatus($order->getStore())) {
                    $order->setStatus($status);
                } else {
                    $order->setStatus(OrderModel::STATE_PROCESSING);
                }

                $this->orderRepository->save($order);
            } catch (\Exception $e) {
                $this->generalHelper->addTolog('invoiceOrder: ' . $order->getIncrementId(), $e->getMessage());
            }
        }
    }

    /**
     * @param OrderModel $order
     */
    private function shipOrder($order)
    {
        if ($order->canShip()) {
            try {
                $shipment = $this->orderConverter->toShipment($order);
                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }

                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                $this->shipmentRepository->save($shipment);
                $this->orderRepository->save($shipment->getOrder());

                $orderComment = __('LVB Order, Automaticly Shipped');
                $order->addStatusHistoryComment($orderComment);
                $this->orderRepository->save($order);
            } catch (\Exception $e) {
                $this->generalHelper->addTolog('shipOrder: ' . $order->getIncrementId(), $e->getMessage());
            }
        }
    }

    /**
     * @param $incrementId
     *
     * @return array
     */
    public function getOrderById($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderList->getFirstItem();

        if (!$order->getId()) {
            return $this->jsonRepsonse('No order found');
        }
        if ($order->getChannableId() < 1) {
            return $this->jsonRepsonse('Not a Channable order');
        }

        $response = [];
        $response['id'] = $order->getIncrementId();
        $response['status'] = $order->getStatus();
        if ($tracking = $this->getTracking($order)) {
            foreach ($tracking as $track) {
                $response['fulfillment']['tracking_code'][] = $track['tracking'];
                $response['fulfillment']['title'][] = $track['title'];
                $response['fulfillment']['carrier_code'][] = $track['carrier_code'];
            }
        }
        return $response;
    }

    /**
     * @param OrderModel $order
     *
     * @return array|bool
     */
    private function getTracking($order)
    {
        $tracking = [];
        $shipmentCollection = $order->getShipmentsCollection();
        foreach ($shipmentCollection as $shipment) {
            foreach ($shipment->getAllTracks() as $tracknum) {
                $tracking[] = [
                    'tracking'     => $tracknum->getNumber(),
                    'title'        => $tracknum->getTitle(),
                    'carrier_code' => $tracknum->getCarrierCode()
                ];
            }
        }
        if (!empty($tracking)) {
            return $tracking;
        } else {
            return false;
        }
    }

    /**
     * @param $timespan
     *
     * @return array
     */
    public function getShipments($timespan)
    {
        $response = [];
        $orderIncrements = [];

        $expression = sprintf('- %s hours', $timespan);
        $gmtDate = $this->generalHelper->getDateTime();
        $date = date('Y-m-d H:i:s', strtotime($expression, strtotime($gmtDate)));

        $shipments = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('main_table.created_at', ['gteq' => $date])
            ->join(
                ['order' => 'sales_order'],
                'main_table.order_id=order.entity_id',
                [
                    'order_increment_id' => 'order.increment_id',
                    'channable_id'       => 'order.channable_id',
                    'status'             => 'order.status'
                ]
            )->addFieldToFilter('channable_id', ['gt' => 0]);

        foreach ($shipments as $shipment) {
            $data['id'] = $shipment->getOrderIncrementId();
            $data['type'] = 'shipment';
            $data['status'] = $shipment->getStatus();
            $data['date'] = $this->generalHelper->getLocalDateTime($shipment->getCreatedAt());
            foreach ($shipment->getAllTracks() as $tracknum) {
                $data['fulfillment']['tracking_code'][] = $tracknum->getNumber();
                $data['fulfillment']['title'][] = $tracknum->getTitle();
                $data['fulfillment']['carrier_code'][] = $tracknum->getCarrierCode();
            }

            $response[] = $data;
            $orderIncrements[] = $shipment->getOrderIncrementId();
            unset($data);
        }

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('updated_at', ['gteq' => $date])
            ->addFieldToFilter('state', [
                    'in' => [
                        OrderModel::STATE_COMPLETE,
                        OrderModel::STATE_CLOSED,
                        OrderModel::STATE_CANCELED
                    ]
                ]
            )
            ->addFieldToFilter('channable_id', ['gt' => 0]);

        if (!empty($orderIncrements)) {
            $orders->addFieldToFilter('increment_id', ['nin' => $orderIncrements]);
        }

        foreach ($orders as $order) {
            $data['id'] = $order->getIncrementId();
            $data['type'] = 'order';
            $data['status'] = $order->getState() == OrderModel::STATE_COMPLETE ? 'complete' : 'canceled';
            $data['date'] = $this->generalHelper->getLocalDateTime($order->getUpdatedAt());
            $response[] = $data;
            unset($data);
        }

        return $response;
    }
}
