<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Block\Adminhtml\Order\Totals as MagentoTotals;

/**
 * Class to add Marketplace Transaction Fee
 */
class Totals extends MagentoTotals
{

    /**
     * Add Marketplace Transaction Fee to totals
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $order = $parent->getOrder();

        if ($order->getTransactionFee() != 0) {
            $parent->addTotal(
                new \Magento\Framework\DataObject(
                    [
                        'code'   => 'transaction_fee',
                        'strong' => false,
                        'value'  => $order->getTransactionFee(),
                        'label'  => __('Marketplace Transaction Fee'),
                    ]
                ),
                'transaction_fee'
            );
        }

        return $this;
    }
}
