<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
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
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\Service\OrderService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Tax\Model\Calculation as TaxCalculationn;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Shipping\Model\ShippingFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class Order
{

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
     * @var OrderInterface
     */
    private $order;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

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
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param OrderInterface              $order
     * @param InvoiceService              $invoiceService
     * @param Transaction                 $transaction
     * @param CartRepositoryInterface     $cartRepositoryInterface
     * @param CartManagementInterface     $cartManagementInterface
     * @param TaxCalculationn             $taxCalculation
     * @param RateRequestFactory          $rateRequestFactory
     * @param ShippingFactory             $shippingFactory
     * @param GeneralHelper               $generalHelper
     * @param OrderlHelper                $orderHelper
     * @param CheckoutSession             $checkoutSession
     * @param LoggerInterface             $logger
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
        OrderInterface $order,
        InvoiceService $invoiceService,
        Transaction $transaction,
        CartRepositoryInterface $cartRepositoryInterface,
        CartManagementInterface $cartManagementInterface,
        TaxCalculationn $taxCalculation,
        RateRequestFactory $rateRequestFactory,
        ShippingFactory $shippingFactory,
        GeneralHelper $generalHelper,
        OrderlHelper $orderHelper,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
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
        $this->order = $order;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->taxCalculation = $taxCalculation;
        $this->generalHelper = $generalHelper;
        $this->orderHelper = $orderHelper;
        $this->shippingFactory = $shippingFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param $data
     * @param $storeId
     *
     * @return array
     */
    public function importOrder($data, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $importCustomer = $this->orderHelper->getImportCustomer($storeId);

        if ($errors = $this->checkItems($data['products'])) {
            return $this->jsonRepsonse($errors, '', $data['channable_id']);
        }

        try {
            $cartId = $this->cartManagementInterface->createEmptyCart();
            $cart = $this->cartRepositoryInterface->get($cartId);
            $cart->setStore($store);
            $cart->setCurrency();

            if ($importCustomer) {
                $customerGroupId = $this->orderHelper->getCustomerGroupId($storeId);
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId);
                $customer->loadByEmail($data['customer']['email']);
                if (!$customerId = $customer->getEntityId()) {
                    $customer->setWebsiteId($websiteId)
                        ->setStore($store)
                        ->setFirstname($data['customer']['first_name'])
                        ->setMiddlename($data['customer']['middle_name'])
                        ->setLastname($data['customer']['last_name'])
                        ->setEmail($data['customer']['email'])
                        ->setPassword($data['customer']['email'])
                        ->setGroupId($customerGroupId)
                        ->save();
                    $customerId = $customer->getId();
                }
                $customer = $this->customerRepository->getById($customerId);
                $cart->assignCustomer($customer);
            } else {
                $customerId = 0;
                $cart->setCustomerId($customerId)
                    ->setCustomerEmail($data['customer']['email'])
                    ->setCustomerFirstname($data['customer']['first_name'])
                    ->setCustomerMiddlename($data['customer']['middle_name'])
                    ->setCustomerLastname($data['customer']['last_name'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }

            $billingAddress = $this->getAddressData('billing', $data, $customerId, $importCustomer);
            if (!empty($billingAddress['errors'])) {
                return $this->jsonRepsonse($billingAddress['errors'], '', $data['channable_id']);
            } else {
                $cart->getBillingAddress()->addData($billingAddress);
            }

            $shippingAddress = $this->getAddressData('shipping', $data, $customerId, $importCustomer);
            if (!empty($shippingAddress['errors'])) {
                return $this->jsonRepsonse($shippingAddress['errors'], '', $data['channable_id']);
            } else {
                $cart->getShippingAddress()->addData($shippingAddress);
            }

            $orderTotal = 0;
            $orderWeight = 0;
            $itemCount = 0;
            $taxCalculation = $this->orderHelper->getNeedsTaxCalulcation('price', $storeId);
            foreach ($data['products'] as $item) {
                $product = $this->product->create()->load($item['id']);
                if (!empty($taxCalculation)) {
                    $price = $item['price'];
                } else {
                    $request = $this->taxCalculation
                        ->getRateRequest($cart->getShippingAddress(), $cart->getBillingAddress(), null, $store);
                    $taxClassId = $product->getData('tax_class_id');
                    $percent = $this->taxCalculation->getRate($request->setProductClassId($taxClassId));
                    $price = ($item['price'] / (100 + $percent) * 100);
                }
                $product->setPrice($price);
                $cart->addProduct($product, intval($item['quantity']));
                $orderTotal += $price;
                $orderWeight += ($product->getWeight() * $item['quantity']);
                $itemCount += $item['quantity'];
            }

            $taxCalculation = $this->orderHelper->getNeedsTaxCalulcation('shipping', $storeId);
            if (!empty($taxCalculation)) {
                $shippingPriceCal = $data['price']['shipping'];
            } else {
                $request = $this->taxCalculation
                    ->getRateRequest($cart->getShippingAddress(), $cart->getBillingAddress(), null, $store);
                $taxRateId = $this->orderHelper->getTaxClassShipping($storeId);
                $percent = $this->taxCalculation->getRate($request->setProductClassId($taxRateId));
                $shippingPriceCal = ($data['price']['shipping'] / (100 + $percent) * 100);
            }

            $this->checkoutSession->setChannableEnabled(1);
            $this->checkoutSession->setChannableShipping($shippingPriceCal);
            $shippingMethod = $this->getShippingMethod($cart, $store, $orderTotal, $orderWeight, $itemCount);

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

            $orderId = $this->cartManagementInterface->placeOrder($cart->getId());
            $order = $this->order->load($orderId);

            if (!empty($data['channel_name'])) {
                $orderComment = __(
                    '<b>%1 order</b><br/>Channable id: %2<br>%3 id: %4<br/>Commission: %5',
                    ucfirst($data['channel_name']),
                    $data['channable_id'],
                    ucfirst($data['channel_name']),
                    $data['channel_id'],
                    $data['price']['commission']
                );
                $order->addStatusHistoryComment($orderComment, false);
                $order->setChannableId($data['channable_id'])
                    ->setChannelId($data['channel_id'])
                    ->setChannelName($data['channel_name']);
            } elseif (!empty($data['channable_id'])) {
                $order->setChannableId($data['channable_id']);
            }

            $order->save();

            if ($this->orderHelper->getInvoiceOrder($storeId)) {
                $this->invoiceOrder($order);
            }
        } catch (LocalizedException $e) {
            $this->logger->debug($e);
            return $this->jsonRepsonse($e->getMessage(), '', $data['channable_id']);
        }

        $this->checkoutSession->unsChannableEnabled();
        $this->checkoutSession->unsChannableShipping();

        return $this->jsonRepsonse('', $order->getIncrementId());
    }

    /**
     * @param $items
     *
     * @return array|bool
     */
    public function checkItems($items)
    {
        $error = [];
        foreach ($items as $item) {
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
                if (!$product->isSalable()) {
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
    public function jsonRepsonse($errors = '', $orderId = '', $channableId = '')
    {
        $response = $this->orderHelper->jsonResponse($errors, $orderId, $channableId);
        if ($this->orderHelper->isLoggingEnabled()) {
            $this->orderHelper->addTolog($channableId, $response);
        }

        return $response;
    }

    /**
     * @param        $type
     * @param        $order
     * @param string $customerId
     * @param bool   $importCustomer
     *
     * @return array
     */
    public function getAddressData($type, $order, $customerId = '', $importCustomer = false)
    {
        $seprate = 0;

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

        $street = $this->getStreet($address, $seprate);

        $addressData = [
            'customer_id' => $customerId,
            'company'     => $address['company'],
            'firstname'   => $address['first_name'],
            'middlename'  => $address['middle_name'],
            'lastname'    => $address['last_name'],
            'street'      => $street,
            'city'        => $address['city'],
            'country_id'  => $address['country_code'],
            'postcode'    => $address['zip_code'],
            'telephone'   => $telephone
        ];

        if ($importCustomer) {
            $newAddress = $this->addressFactory->create();
            $newAddress->setCustomerId($customerId)
                ->setCompany($addressData['company'])
                ->setFirstname($addressData['firstname'])
                ->setMiddlename($addressData['middlename'])
                ->setLastname($addressData['lastname'])
                ->setStreet($addressData['street'])
                ->setCity($addressData['city'])
                ->setCountryId($addressData['country_id'])
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
     * @param $address
     * @param $seperateHousnumber
     *
     * @return array|string
     */
    public function getStreet($address, $seperateHousnumber)
    {
        $street = [];
        if (!empty($seperateHousnumber)) {
            $street[] = $address['street'];
            $street[] = trim($address['house_number'] . ' ' . $address['house_number_ext']);
            $street = implode("\n", $street);
        } else {
            if (!empty($address['address_line_1'])) {
                $street[] = $address['address_line_1'];
                $street[] = $address['address_line_2'];
                $street = implode("\n", $street);
            } else {
                $street = $address['street'] . ' ';
                $street .= trim($address['house_number'] . ' ' . $address['house_number_ext']);
            }
        }
        return $street;
    }

    /**
     * @param $cart
     * @param $store
     * @param $orderTotal
     * @param $orderWeight
     * @param $itemCount
     *
     * @return mixed|string
     */
    public function getShippingMethod($cart, $store, $orderTotal, $orderWeight, $itemCount)
    {

        $shippingMethod = $this->orderHelper->getShippingMethod($store->getId());
        $shippingMethodFallback = $this->orderHelper->getShippingMethodFallback($store->getId());

        $destCountryId = $cart->getShippingAddress()->getCountryId();
        $destPostcode = $cart->getShippingAddress()->getPostcode();

        $request = $this->rateRequestFactory->create();
        $request->setAllItems($cart->getAllItems());
        $request->setDestCountryId($destCountryId);
        $request->setDestPostcode($destPostcode);
        $request->setPackageValue($orderTotal);
        $request->setPackageValueWithDiscount($orderTotal);
        $request->setPackageWeight($orderWeight);
        $request->setPackageQty($itemCount);
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());
        $request->setLimitCarrier('');
        $request->setBaseSubtotalInclTax($orderTotal);
        $shipping = $this->shippingFactory->create();
        $result = $shipping->collectRates($request)->getResult();

        if ($result) {
            $shippingRates = $result->getAllRates();
            foreach ($shippingRates as $shippingRate) {
                $method = $shippingRate->getCarrier() . '_' . $shippingRate->getMethod();
                if ($method == $shippingMethod) {
                    return $shippingMethod;
                }
            }
        }

        return $shippingMethodFallback;
    }

    /**
     * @param $order
     */
    public function invoiceOrder($order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->save();
        }
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getOrderById($id)
    {
        $order = $this->order->loadByIncrementId($id);
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
     * @param $order
     *
     * @return array|bool
     */
    public function getTracking($order)
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
}
