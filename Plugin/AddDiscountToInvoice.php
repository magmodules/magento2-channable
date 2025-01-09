<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin;

use Magento\Sales\Model\Order\Invoice;

class AddDiscountToInvoice
{
    /**
     * Add channable discount total to the invoice.
     *
     * @param Invoice $invoice
     * @param callable $proceed
     * @return void
     */
    public function aroundCollectTotals(Invoice $invoice, callable $proceed)
    {
        $invoice = $proceed();

        $order = $invoice->getOrder();
        if ($order->getPayment()->getMethod() == 'channable') {
            $discountAmount = abs((float)$order->getDiscountAmount());
            if ($discountAmount > 0) {
                $invoice->setDiscountAmount(-$discountAmount);
                $invoice->setBaseDiscountAmount(-$discountAmount);
                $invoice->setGrandTotal($invoice->getGrandTotal() - $discountAmount);
                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $discountAmount);
            }
        }

        return $invoice;
    }
}
