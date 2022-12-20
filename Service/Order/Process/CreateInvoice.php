<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Throwable;

/**
 * Create invoice for order
 */
class CreateInvoice
{
    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var OrderConfig
     */
    private $orderConfig;

    /**
     * CreateInvoice constructor.
     *
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentHistory $orderCommentHistory
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        ConfigProvider $configProvider,
        OrderConfig $orderConfig
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->configProvider = $configProvider;
        $this->orderConfig = $orderConfig;
    }

    /**
     * Create invoice for specific order
     *
     * @param OrderInterface $order
     *
     * @throws LocalizedException
     */
    public function execute(OrderInterface $order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->setTransactionFee($order->getTransactionFee());
            $invoice->register();

            $this->transaction->addObject($invoice);

            $order->setState(Order::STATE_PROCESSING);

            if ($this->configProvider->updateOrderStatusAfterImport((int)$order->getStoreId())
                && $status = $this->configProvider->getOrderProcessingStatus((int)$order->getStoreId())) {
                $order->setStatus($status);
            } else {
                $order->setStatus(
                    $this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING)
                        ?: Order::STATE_PROCESSING
                );
            }

            $this->transaction->addObject($order)->save();
            $this->sendInvoice($invoice, $order);
        }
    }

    /**
     * Send invoice email
     *
     * @param InvoiceInterface $invoice
     * @param OrderInterface $order
     *
     * @throws CouldNotSaveException
     */
    private function sendInvoice(InvoiceInterface $invoice, $order)
    {
        /** @var Invoice $invoice */
        if ($invoice->getEmailSent() || !$this->configProvider->sendInvoiceEmailOnImport($invoice->getStoreId())) {
            return;
        }
        try {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
