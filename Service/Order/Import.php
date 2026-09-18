<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order;

use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as QuoteEntity;
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
use Magmodules\Channable\Service\Order\Currency\Converter as CurrencyConverter;
use Throwable;

/**
 * Class Order Import
 */
class Import
{
    private const LVB_AUTO_SHIP_MESSAGE = 'LVB Order, automatically shipped';
    private const COULD_NOT_IMPORT_ORDER = 'Could not import order %1: %2';
    private const SUBMIT_RETURNED_NULL = 'Order placement failed, no order was created for quote %1. '
        . 'Magento returns no order when the quote holds no visible items at submit '
        . '(quote items: %2, visible: %3), or when a third party plugin on '
        . 'Magento\\Quote\\Api\\CartManagementInterface::submit() does not return the result of proceed(). '
        . 'Check plugins and observers on the quote submit process.';

    protected CartRepositoryInterface $quoteRepository;
    private ConfigProvider $configProvider;
    private StoreManagerInterface $storeManager;
    private QuoteManagement $quoteManagement;
    private OrderRepositoryInterface $orderRepository;
    private CheckoutSession $checkoutSession;
    private ItemsForReindex $itemsForReindex;
    private Items\Add $addItems;
    private Quote\Create $createQuote;
    private Shipping\CalculatePrice $calculateShippingPrice;
    private Shipping\SetShippingMethod $setShippingMethod;
    private Process\CreateInvoice $createInvoice;
    private Process\SendOrderEmail $sendOrderEmail;
    private Process\CreateShipment $createShipment;
    private Process\AddPaymentData $addPaymentData;
    private Process\GetCustomIncrementId $getCustomIncrementId;
    private ChannableOrderRepository $channableOrderRepository;
    private Shipping\GetDescription $getShippingDescription;
    private ManagerInterface $eventManager;
    private LoggerRepository $logger;
    private CurrencyConverter $currencyConverter;

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
        Shipping\SetShippingMethod $setShippingMethod,
        Shipping\GetDescription $getShippingDescription,
        Process\SendOrderEmail $sendOrderEmail,
        Process\CreateInvoice $createInvoice,
        Process\CreateShipment $createShipment,
        Process\AddPaymentData $addPaymentData,
        Process\GetCustomIncrementId $getCustomIncrementId,
        ChannableOrderRepository $channableOrderRepository,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $eventManager,
        LoggerRepository $logger,
        CurrencyConverter $currencyConverter
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
        $this->setShippingMethod = $setShippingMethod;
        $this->getShippingDescription = $getShippingDescription;
        $this->sendOrderEmail = $sendOrderEmail;
        $this->createInvoice = $createInvoice;
        $this->createShipment = $createShipment;
        $this->addPaymentData = $addPaymentData;
        $this->getCustomIncrementId = $getCustomIncrementId;
        $this->channableOrderRepository = $channableOrderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * Create a Magento order from Channable order data
     *
     * @param ChannableOrderData $orderData
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
            $this->addItems->execute($quote, $orderData, $store, $lvbOrder);

            $shippingPrice = $this->calculateShippingPrice->execute($quote, $orderData, $store);
            $quote->collectTotals();

            $this->setCheckoutSessionData((float)$shippingPrice);
            $this->setShippingMethod->execute($quote, $shippingPrice, $channableOrder);

            $quote->setPaymentMethod('channable');
            $quote->setInventoryProcessed(false);
            $payment = $quote->getPayment();
            $payment->setMethod('channable');
            $payment->importData(['method' => 'channable']);
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

            $this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

            $order = $this->assertOrderCreated($this->quoteManagement->submit($quote), $quote);
            $order->setTransactionFee($quote->getTransactionFee());

            if (isset($orderData['price']['discount']) && !empty((float)$orderData['price']['discount'])) {
                if (!$this->hasItemLevelDiscounts($orderData)) {
                    $orderCurrency = $orderData['price']['currency'] ?? '';
                    $discountAmount = abs((float)$orderData['price']['discount']);
                    $baseDiscountAmount = $this->currencyConverter->convertToBase(
                        $discountAmount,
                        $orderCurrency,
                        $storeId
                    );
                    $order->setDiscountDescription($orderData['channel_name']);
                    $order->setBaseDiscountAmount($baseDiscountAmount * -1);
                    $order->setDiscountAmount($discountAmount * -1);
                    $order->setGrandTotal($order->getGrandTotal() - $discountAmount);
                    $order->setBaseGrandTotal($order->getBaseGrandTotal() - $baseDiscountAmount);
                }
            }

            $store->setCurrentCurrencyCode($store->getBaseCurrencyCode());

            if ($shippingDescription = $this->getShippingDescription->execute($order, $orderData)) {
                $order->setShippingDescription($shippingDescription);
            }

            $this->addPaymentData->execute($order, $orderData, $lvbOrder);
            $this->afterOrderImport($order, $storeId, $lvbOrder);
            $this->setChannableOrderImportSuccess($channableOrder, $order);
            $this->orderRepository->save($order);

            $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);

            return $order;
        } catch (Throwable $exception) {
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
     * Assert Magento actually created an order for the submitted quote
     *
     * QuoteManagement::submit() returns null instead of throwing when the quote holds no
     * visible items. Without this guard that null surfaces as a fatal error on the next
     * setter call, which bypasses the error handling below and leaves the Channable order
     * without a readable reason for the failure.
     *
     * @param OrderInterface|null $order
     * @param QuoteEntity $quote
     * @return OrderInterface
     * @throws CouldNotImportOrder
     */
    private function assertOrderCreated(?OrderInterface $order, QuoteEntity $quote): OrderInterface
    {
        if ($order !== null) {
            return $order;
        }

        $submitReturnedNullMsg = self::SUBMIT_RETURNED_NULL;
        $message = __(
            $submitReturnedNullMsg,
            (string)$quote->getId(),
            count($quote->getAllItems()),
            count($quote->getAllVisibleItems())
        );

        $this->logger->addErrorLog('SubmitQuote', $message->render());

        throw new CouldNotImportOrder($message);
    }

    /**
     * Check if any product in the order data has an item-level discount.
     */
    private function hasItemLevelDiscounts(array $orderData): bool
    {
        foreach ($orderData['products'] ?? [] as $item) {
            if (isset($item['discount']) && (float)$item['discount'] > 0) {
                return true;
            }
        }
        return false;
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
     * @param int $storeId
     * @param bool $lvbOrder
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
     * @param OrderInterface $order
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
