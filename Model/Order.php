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
use Magento\Sales\Model\Service\OrderService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\ShippingFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Framework\Registry;
use Magmodules\Channable\Service\Order\CreateInvoice;
use Magmodules\Channable\Service\Order\AddressData;
use Magmodules\Channable\Service\Order\Items\Add as AddItems;

/**
 * Class Order
 *
 * @package Magmodules\Channable\Model
 */
class Order
{

    /**
     * @var null
     */
    public $storeId = null;
    /**
     * @var bool
     */
    public $importCustomer = false;
    /**
     * @var bool
     */
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
     * @var OrderConverter
     */
    private $orderConverter;
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;
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
     * @var CreateInvoice
     */
    private $createInvoice;
    /**
     * @var AddressData
     */
    private $addressData;
    /**
     * @var AddItems
     */
    private $addItems;
    /**
     * @var Config
     */
    private $config;

    /**
     * Order constructor.
     *
     * @param StoreManagerInterface       $storeManager
     * @param ProductFactory              $product
     * @param FormKey                     $formkey
     * @param QuoteFactory                $quote
     * @param QuoteManagement             $quoteManagement
     * @param CustomerFactory             $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderService                $orderService
     * @param OrderRepositoryInterface    $orderRepository
     * @param OrderConverter              $orderConverter
     * @param ShipmentRepositoryInterface $shipmentRepository
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
     * @param CreateInvoice               $createInvoice
     * @param AddressData                 $addressData
     * @param AddItems                    $addItems
     * @param Config                      $config
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductFactory $product,
        FormKey $formkey,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderService $orderService,
        OrderRepositoryInterface $orderRepository,
        OrderConverter $orderConverter,
        ShipmentRepositoryInterface $shipmentRepository,
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
        Registry $registry,
        CreateInvoice $createInvoice,
        AddressData $addressData,
        AddItems $addItems,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->orderConverter = $orderConverter;
        $this->shipmentRepository = $shipmentRepository;
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
        $this->createInvoice = $createInvoice;
        $this->addressData = $addressData;
        $this->addItems = $addItems;
        $this->config = $config;
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
        $this->lvb = ($data['order_status'] == 'shipped') ? true : false;

        try {
            $this->checkoutSession->setForceBackorder($this->config->getEnableBackorders());

            $store = $this->storeManager->getStore($storeId);
            $store->setCurrentCurrencyCode($data['price']['currency']);

            $cartId = $this->cartManagementInterface->createEmptyCart();
            $cart = $this->cartRepositoryInterface->get($cartId)->setStore($store)->setCurrency()->setIsSuperMode(true);
            $customerId = $this->setCustomerCart($cart, $store, $data);

            $billingAddress = $this->addressData->execute('billing', $data, $storeId, $customerId);
            $cart->getBillingAddress()->addData($billingAddress);

            $shippingAddress = $this->addressData->execute('shipping', $data, $storeId, $customerId);
            $cart->getShippingAddress()->addData($shippingAddress);

            $itemCount = $this->addItems->execute($cart, $data, $store, $this->lvb);
            $shippingPriceCal = $this->getShippingPrice($cart, $data, $store);
            $cart->collectTotals();

            $this->checkoutSession->setChannableEnabled(1);
            $this->checkoutSession->setChannableShipping($shippingPriceCal);

            $shippingMethod = $this->getShippingMethod($cart, $store, $itemCount, $shippingPriceCal);
            $shippingAddress = $cart->getShippingAddress();
            $shippingAddress->setFreeShipping($shippingPriceCal <= 0); // Some carriers also check this flag.

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);

            $cart->setPaymentMethod('channable');
            $cart->setInventoryProcessed(false);
            $cart->getPayment()->importData(['method' => 'channable']);
            $cart->setTotalsCollectedFlag(false)->collectTotals();
            $cart->save();

            $cart = $this->cartRepositoryInterface->get($cart->getId());

            if ($this->lvb && $this->orderHelper->getLvbSkipStock($store->getId())) {
                $cart->setInventoryProcessed(true);
                $this->itemsForReindex->clear();
            }

            if ($this->orderHelper->getUseChannelOrderId($storeId)) {
                $newIncrementId = $this->orderHelper->getUniqueIncrementId($data['channel_id'], $storeId);
                $cart->setReservedOrderId($newIncrementId);
            }

            $orderId = $this->cartManagementInterface->placeOrder($cart->getId());
            $store->setCurrentCurrencyCode($store->getBaseCurrencyCode());

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);

            if ($shippingDescription = $this->getShippingDescription($order, $data)) {
                $order->setShippingDescription($shippingDescription);
            }

            $this->addPaymentData($order, $data);

            if ($this->orderHelper->getInvoiceOrder($storeId)) {
                $this->createInvoice->execute($order);
            }

            if ($this->lvb && $this->orderHelper->getLvbAutoShip($storeId)) {
                $this->shipOrder($order);
            }

            return $this->jsonRepsonse('', $order->getIncrementId());
        } catch (\Exception $e) {
            $this->generalHelper->addTolog('importOrder: ' . $data['channable_id'], $e->getMessage());
            return $this->jsonRepsonse($e->getMessage(), '', $data['channable_id']);
        } finally {
            $this->checkoutSession->unsForceBackorder();
            $this->checkoutSession->unsChannableEnabled();
            $this->checkoutSession->unsChannableShipping();
            $this->checkoutSession->unsChannableSkipQtyCheck();
        }
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
            $cart->setCustomerIsGuest(false)->assignCustomer($customer);
        } else {
            $customerId = 0;
            $cart->setCustomerId($customerId)
                ->setCustomerEmail($email)
                ->setCustomerFirstname($data['customer']['first_name'])
                ->setCustomerMiddlename($data['customer']['middle_name'])
                ->setCustomerLastname($data['customer']['last_name'])
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID)
                ->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        }

        return $customerId;
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
     * @param StoreManagerInterface $store
     * @param int $itemCount
     * @param float|int $shippingPriceCal
     *
     * @return mixed|null|string
     */
    private function getShippingMethod($cart, $store, $itemCount, $shippingPriceCal)
    {
        $shippingMethod = $this->orderHelper->getShippingMethod($store->getId());
        $shippingMethodFallback = $this->orderHelper->getShippingMethodFallback($store->getId());

        $destCountryId = $cart->getShippingAddress()->getCountryId();
        $destPostcode = $cart->getShippingAddress()->getPostcode();
        $total = $cart->getGrandTotal();

        /** @var \Magento\Quote\Model\Quote\Address\RateRequest $request */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($cart->getAllItems());
        $request->setDestCountryId($destCountryId);
        $request->setDestPostcode($destPostcode);
        $request->setPackageValue($total);
        $request->setPackageValueWithDiscount($total);
        $request->setPackageQty($itemCount);
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());
        $request->setLimitCarrier('');
        $request->setBaseSubtotalInclTax($total);
        $request->setFreeShipping($shippingPriceCal <= 0);
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
     * @param array $data
     *
     * @return mixed
     */
    private function getShippingDescription($order, $data)
    {
        if ($order->getShippingMethod() !== 'channable_channable') {
            return;
        }

        $title = str_replace(
            '{{channable_channel_label}}',
            !empty($data['channable_channel_label']) ? $data['channable_channel_label'] : 'Channable',
            $this->config->getCarrierTitle($order->getStoreId())
        );

        $name = str_replace(
            '{{shipment_method}}',
            !empty($data['shipment_method']) ? $data['shipment_method'] : 'Shipping',
            $this->config->getCarrierName($order->getStoreId())
        );

        return implode(' - ', [$title, $name]);
    }

    /**
     * @param OrderModel $order
     * @param array      $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
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

        if (!empty($data['channel_customer_number'])) {
            $payment->setAdditionalInformation('channel_customer_number', $data['channel_customer_number']);
        }

        if (!empty($data['shipment_promise'])) {
            $payment->setAdditionalInformation('shipment_promise', $data['shipment_promise']);
        }

        $commissionValue = isset($data['price']['commission']) ? $data['price']['commission'] : 0;
        $commission = $data['price']['currency'] . ' ' . $commissionValue;
        $payment->setAdditionalInformation('commission', $commission);

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
        if (!empty($data['memo'])) {
            $payment->setAdditionalInformation('memo', $data['memo']);
        }

        $this->orderRepository->save($order);
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
