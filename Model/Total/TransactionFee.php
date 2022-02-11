<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Total;

use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class Fee
 */
class TransactionFee extends AbstractTotal
{

    const TRANSACTION_DATA = [
        'code' => 'transaction_fee',
        'title' => 'Transaction Fee',
    ];

    /**
     * Collect grand total address amount
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $total->setTotalAmount('transaction_fee', $quote->getTransactionFee());
        $total->setBaseTotalAmount('transaction_fee', $quote->getTransactionFee());
        $total->setTransactionFee($quote->getTransactionFee());
        $total->setBaseTransactionFee($quote->getTransactionFee());
        $total->setGrandTotal($total->getGrandTotal());
        $total->setBaseGrandTotal($total->getBaseGrandTotal());
        return $this;
    }

    /**
     * Assign subtotal amount and label to address object
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(Quote $quote, Total $total): array
    {
        return [
            'code' => self::TRANSACTION_DATA['code'],
            'title' => self::TRANSACTION_DATA['title'],
            'value' => $quote->getTransactionFee()
        ];
    }

    /**
     * Get Subtotal label
     *
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Marketplace Transaction Fee');
    }
}
