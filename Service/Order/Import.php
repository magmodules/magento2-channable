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
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Order\Data\DataInterface as ChannableOrderData;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;
use Magmodules\Channable\Exceptions\CouldNotImportOrder;
use Magmodules\Channable\Model\Config\Source\Status;

/**
 * Class Order Import
 */
class Import
{

    const LVB_AUTO_SHIP_MESSAGE = 'LVB Order, Automaticly Shipped';
    const COULD_NOT_IMPORT_ORDER = 'Could not import order %1, error: %2';

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
     * @var Items\Validate
     */
    private $validateItems;

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
     * @var Json
     */
    private $json;

    /**
     * @var Shipping\GetDescription
     */
    private $getShippingDescription;

    /**
     * Import constructor.
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param QuoteManagement $quoteManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckoutSession $checkoutSession
     * @param ItemsForReindex $itemsForReindex
     * @param Items\Validate $validateItems
     * @param Items\Add $addItems
     * @param Quote\Create $createQuote
     * @param Shipping\CalculatePrice $calculateShippingPrice
     * @param Shipping\GetMethod $getShippingMethod
     * @param Process\CreateInvoice $createInvoice
     * @param Process\CreateShipment $createShipment
     * @param Process\AddPaymentData $addPaymentData
     * @param Process\GetCustomIncrementId $getCustomIncrementId
     * @param ChannableOrderRepository $channableOrderRepository
     * @param Json $json
     */
    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        CheckoutSession $checkoutSession,
        ItemsForReindex $itemsForReindex,
        Items\Validate $validateItems,
        Items\Add $addItems,
        Quote\Create $createQuote,
        Shipping\CalculatePrice $calculateShippingPrice,
        Shipping\GetMethod $getShippingMethod,
        Shipping\GetDescription $getShippingDescription,
        Process\CreateInvoice $createInvoice,
        Process\CreateShipment $createShipment,
        Process\AddPaymentData $addPaymentData,
        Process\GetCustomIncrementId $getCustomIncrementId,
        ChannableOrderRepository $channableOrderRepository,
        Json $json
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->itemsForReindex = $itemsForReindex;
        $this->validateItems = $validateItems;
        $this->addItems = $addItems;
        $this->createQuote = $createQuote;
        $this->calculateShippingPrice = $calculateShippingPrice;
        $this->getShippingMethod = $getShippingMethod;
        $this->getShippingDescription = $getShippingDescription;
        $this->createInvoice = $createInvoice;
        $this->createShipment = $createShipment;
        $this->addPaymentData = $addPaymentData;
        $this->getCustomIncrementId = $getCustomIncrementId;
        $this->channableOrderRepository = $channableOrderRepository;
        $this->json = $json;
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
            $store = $this->storeManager->getStore((int)$orderData['store_id']);
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

            $quote->setPaymentMethod('channable');
            $quote->setInventoryProcessed(false);
            $quote->getPayment()->importData(['method' => 'channable']);
            $totals = $quote->getTotals();

            $quote->setTotals($totals);
            $quote->collectTotals();
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $quote->save();

            if ($lvbOrder && $this->configProvider->disableStockMovementForLvbOrders((int)$store->getId())) {
                $quote->setInventoryProcessed(true);
                $this->itemsForReindex->clear();
            }
            $orderId = $this->quoteManagement->placeOrder($quote->getId());
            $order = $this->orderRepository->get($orderId);
            if (isset($orderData['price']['discount']) && $orderData['price']['discount']) {
                $order->setDiscountDescription(__('Channable discount'));
                $order->setBaseDiscountAmount($orderData['price']['discount']);
                $order->setGrandTotal($order->getGrandTotal() - $orderData['price']['discount']);
                $order->setBaseGrandTotal($order->getBaseGrandTotal() - $orderData['price']['discount']);
                $order->setDiscountAmount($orderData['price']['discount'])->save();
            }
            $store->setCurrentCurrencyCode($store->getBaseCurrencyCode());

            if ($customIncrementId = $this->getCustomIncrementId->execute($orderData, $store)) {
                $order->setIncrementId($customIncrementId);
            }

            if ($shippingDescription = $this->getShippingDescription->execute($order, $orderData)) {
                $order->setShippingDescription($shippingDescription);
            }

            $this->addPaymentData->execute($order, $orderData, $lvbOrder);

            if ($this->configProvider->autoInvoiceOrderOnImport((int)$orderData['store_id'])) {
                $this->createInvoice->execute($order);
            }

            if ($lvbOrder && $this->configProvider->autoShipLvbOrders((int)$orderData['store_id'])) {
                $this->createShipment->execute($order, self::LVB_AUTO_SHIP_MESSAGE);
            }

            $this->setChannableOrderImportSuccess($channableOrder, $order);
            return $order;
        } catch (Exception $exception) {
            $coulNotImportMsg = self::COULD_NOT_IMPORT_ORDER;
            $message = __(
                (string)$coulNotImportMsg,
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
     * Add 'success' data to Channable Order on successfull import
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
    public function setChannableOrderImportError(ChannableOrderData $channableOrder, $errorMsg): void
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
    }
}
