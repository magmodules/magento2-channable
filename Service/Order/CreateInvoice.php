<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magmodules\Channable\Model\Config;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;

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
     * @var Config
     */
    private $config;

    /**
     * CreateInvoice constructor.
     *
     * @param InvoiceService      $invoiceService
     * @param Transaction         $transaction
     * @param InvoiceSender       $invoiceSender
     * @param OrderCommentHistory $orderCommentHistory
     * @param Config              $config
     */
    public function __construct(
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        Config $config
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(OrderInterface $order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $this->transaction->addObject($invoice);

            $order->setState(Order::STATE_PROCESSING);
            if ($status = $this->config->processingStatus($order->getStoreId())) {
                $order->setStatus($status);
            } else {
                $order->setStatus(Order::STATE_PROCESSING);
            }

            $this->transaction->addObject($order)->save();
            $this->sendInvoice($invoice, $order);
        }
    }

    /**
     * @param InvoiceInterface $invoice
     * @param OrderInterface   $order
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function sendInvoice(InvoiceInterface $invoice, OrderInterface $order)
    {
        /** @var Invoice $invoice */
        if ($invoice->getEmailSent() || !$this->config->sendInvoiceEmail($invoice->getStoreId())) {
            return;
        }
        try {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (\Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }

}