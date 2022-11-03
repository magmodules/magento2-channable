<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals as MagentoTotals;

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
        $creditmemo = $parent->getCreditmemo();
        if ($creditmemo->getTransactionFee() != 0) {
            $parent->addTotal(
                new DataObject(
                    [
                        'code' => 'transaction_fee',
                        'strong' => false,
                        'value' => $creditmemo->getTransactionFee(),
                        'label' => __('Marketplace Transaction Fee'),
                    ]
                ),
                'transaction_fee'
            );
        }

        return $this;
    }
}
