<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order;

use Exception;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LoggerRepository;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;
use Magmodules\Channable\Model\Config\Source\Status;

/**
 * Class Order Import
 */
class Import
{
    /**
     * Exception messages
     */
    private const LVB_AUTO_SHIP_MESSAGE = 'LVB Order, automatically shipped';
    private const COULD_NOT_IMPORT_ORDER = 'Could not import order %1: %2';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ItemsForReindex
     */
    private $itemsForReindex;

    /**
     * @var Items\Add
     */
    private $addItems;

    /**
     * @var Quote\Create
     */
    private $createQuote;

    /**
     * @var Shipping\CalculatePrice
     */
    private $calculateShippingPrice;

    /**
     * @var Shipping\GetMethod
     */
    private $getShippingMethod;

    /**
     * @var Process\CreateInvoice
     */
    private $createInvoice;

    /**
     * @var Process\SendOrderEmail
     */
    private $sendOrderEmail;

    /**
     * @var Process\CreateShipment
     */
    private $createShipment;

    /**
     * @var Process\AddPaymentData
     */
    private $addPaymentData;

    /**
     * @var Process\GetCustomIncrementId
     */
    private $getCustomIncrementId;

    /**
     * @var ChannableOrderRepository
     */
    private $channableOrderRepository;

    /**
     * @var Shipping\GetDescription
     */
    private $getShippingDescription;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var LoggerRepository
     */
    private $logger;

    /**
     * Import constructor.
     *
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param QuoteManagement $quoteManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckoutSession $checkoutSession
     * @param ItemsForReindex $itemsForReindex
     * @param Items\Add $addItems
     * @param Quote\Create $createQuote
     * @param Shipping\CalculatePrice $calculateShippingPrice
     * @param Shipping\GetMethod $getShippingMethod
     * @param Shipping\GetDescription $getShippingDescription
     * @param Process\SendOrderEmail $sendOrderEmail
     * @param Process\CreateInvoice $createInvoice
     * @param Process\CreateShipment $createShipment
     * @param Process\AddPaymentData $addPaymentData
     * @param Process\GetCustomIncrementId $getCustomIncrementId
     * @param ChannableOrderRepository $channableOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerRepository $logger
     */
    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        CheckoutSession $checkoutSession,
        ItemsForReindex $itemsForReindex,
        Items\Add $addItems,
        Quote\Create $createQuote,
        Shipping\CalculatePrice $calculateShippingPrice,
        Shipping\GetMethod $getShippingMethod,
        Shipping\GetDescription $getShippingDescription,
        Process\SendOrderEmail $sendOrderEmail,
        Process\CreateInvoice $createInvoice,
        Process\CreateShipment $createShipment,
        Process\AddPaymentData $addPaymentData,
        Process\GetCustomIncrementId $getCustomIncrementId,
        ChannableOrderRepository $channableOrderRepository,
        CartRepositoryInterface $quoteRepository,
        LoggerRepository $logger
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->itemsForReindex = $itemsForReindex;
        $this->addItems = $addItems;
        $this->createQuote = $createQuote;
        $this->calculateShippingPrice = $calculateShippingPrice;
        $this->getShippingMethod = $getShippingMethod;
        $this->getShippingDescription = $getShippingDescription;
        $this->sendOrderEmail = $sendOrderEmail;
        $this->createInvoice = $createInvoice;
        $this->createShipment = $createShipment;
        $this->addPaymentData = $addPaymentData;
        $this->getCustomIncrementId = $getCustomIncrementId;
        $this->channableOrderRepository = $channableOrderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Create a Magento order from Channable order data
     *
     * @param ChannableOrderData $orderData
     *
     * @return OrderInterface $order
     * @throws CouldNotImportOrder
     * @throws LocalizedException
     */
    public function execute(ChannableOrderData $orderData): OrderInterface
    {
        $channableOrder = $orderData;
        $orderData = $orderData->getData();

        try {
            $storeId = (int)$orderData['store_id'];
            $store = $this->storeManager->getStore($storeId);
            $store->setCurrentCurrencyCode($orderData['price']['currency']);
            $lvbOrder = $orderData['order_status'] == 'shipped';
            $quote = $this->createQuote->createCustomerQuote($orderData, $store);
            $itemCount = $this->addItems->execute($quote, $orderData, $store, $lvbOrder);

            $shippingPrice = $this->calculateShippingPrice->execute($quote, $orderData, $store);
            $quote->collectTotals();

            $this->setCheckoutSessionData((float)$shippingPrice);

            $shippingMethod = $this->getShippingMethod->execute($quote, $store, $itemCount, $shippingPrice);
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);

            foreach ($shippingAddress->getShippingRatesCollection() as $rate) {
                /** @var \Magento\Quote\Model\Quote\Address\Rate $rate */
                $rate->setPrice($shippingPrice);
                $rate->setCost($shippingPrice);
            }

            $quote->setPaymentMethod('channable');
            $quote->setInventoryProcessed(false);
            $quote->getPayment()->importData(['method' => 'channable']);
            $totals = $quote->getTotals();

            $quote->setTotals($totals);
            $quote->collectTotals();
            $quote->setTotalsCollectedFlag(false)->collectTotals();

            if ($customIncrementId = $this->getCustomIncrementId->execute($orderData, $store)) {
                $quote->setReservedOrderId($customIncrementId);
            }

            $this->quoteRepository->save($quote);

            if ($lvbOrder && $this->configProvider->disableStockMovementForLvbOrders($storeId)) {
                $quote->setInventoryProcessed(true);
                $this->itemsForReindex->clear();
            }

            $order = $this->quoteManagement->submit($quote);
            $order->setTransactionFee($quote->getTransactionFee());

            if (isset($orderData['price']['discount']) && !empty((float)$orderData['price']['discount'])) {
                $discountAmount = abs((float)$orderData['price']['discount']);
                $order->setDiscountDescription(__('Channable discount'));
                $order->setBaseDiscountAmount($discountAmount);
                $order->setDiscountAmount($discountAmount);
                $order->setGrandTotal($order->getGrandTotal() - $discountAmount);
                $order->setBaseGrandTotal($order->getBaseGrandTotal() - $discountAmount);
            }

            $store->setCurrentCurrencyCode($store->getBaseCurrencyCode());

            if ($shippingDescription = $this->getShippingDescription->execute($order, $orderData)) {
                $order->setShippingDescription($shippingDescription);
            }

            $this->addPaymentData->execute($order, $orderData, $lvbOrder);
            $this->afterOrderImport($order, $storeId, $lvbOrder);
            $this->setChannableOrderImportSuccess($channableOrder, $order);
            $this->orderRepository->save($order);
            return $order;
        } catch (Exception $exception) {
            $couldNotImportMsg = self::COULD_NOT_IMPORT_ORDER;
            $message = __(
                $couldNotImportMsg,
                $orderData['channable_id'],
                $exception->getMessage()
            );
            $this->setChannableOrderImportError($channableOrder, $message->render());
            throw new CouldNotImportOrder($message);
        } finally {
            $this->unsetCheckoutSessionData();
        }
    }

    /**
     * Add shipping info to the checkout-session
     *
     * @param float $shippingPrice
     */
    private function setCheckoutSessionData(float $shippingPrice): void
    {
        $this->checkoutSession->setChannableEnabled(1);
        $this->checkoutSession->setChannableShipping($shippingPrice);
    }

    /**
     * Check if we need to invoice and ship order
     *
     * @param OrderInterface $order
     * @param int            $storeId
     * @param bool           $lvbOrder
     *
     * @return void
     */
    private function afterOrderImport(OrderInterface $order, int $storeId, bool $lvbOrder)
    {
        try {
            if ($this->configProvider->sendOrderEmailOnImport($storeId)) {
                $this->sendOrderEmail->execute($order);
            }
            if ($this->configProvider->autoInvoiceOrderOnImport($storeId)) {
                $this->createInvoice->execute($order);
            }
            if ($lvbOrder && $this->configProvider->autoShipLvbOrders($storeId)) {
                $this->createShipment->execute($order, self::LVB_AUTO_SHIP_MESSAGE);
            }
        } catch (\Exception $exception) {
            $this->logger->addErrorLog(
                'AfterOrderImport',
                sprintf('Order %s: %s', $order->getIncrementId(), $exception->getMessage())
            );
        }
    }

    /**
     * Add 'success' data to Channable Order on successfully import
     *
     * @param ChannableOrderData $channableOrder
     * @param OrderInterface     $order
     *
     * @throws LocalizedException
     */
    public function setChannableOrderImportSuccess(ChannableOrderData $channableOrder, OrderInterface $order): void
    {
        $channableOrder->setStatus(Status::IMPORTED);
        $channableOrder->setMagentoIncrementId($order->getIncrementId());
        $channableOrder->setMagentoOrderId((int)$order->getEntityId());
        $channableOrder->setErrorMsg('');

        $this->channableOrderRepository->save($channableOrder);
    }

    /**
     * Add 'error' data to Channable Order on failed import
     *
     * @param ChannableOrderData $channableOrder
     * @param string $errorMsg
     *
     * @throws LocalizedException
     */
    public function setChannableOrderImportError(ChannableOrderData $channableOrder, string $errorMsg): void
    {
        $channableOrder->setErrorMsg($errorMsg);
        $attempts = $channableOrder->getAttempts();
        if ($attempts >= 3) {
            $channableOrder->setStatus(Status::FAILED);
        } else {
            $attempts++;
            $channableOrder->setAttempts($attempts);
            $channableOrder->setStatus(Status::ERROR);
        }

        $this->channableOrderRepository->save($channableOrder);
    }

    /**
     * Unset shipping info from checkout-session
     */
    private function unsetCheckoutSessionData(): void
    {
        $this->checkoutSession->unsChannableEnabled();
        $this->checkoutSession->unsChannableShipping();
        $this->checkoutSession->unsChannableSkipQtyCheck();
        $this->checkoutSession->unsChannableSkipReservation();
    }
}
