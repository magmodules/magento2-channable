<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\Totals as MagentoTotals;
use Magento\Sales\Helper\Admin;

/**
 * Class to add Marketplace Transaction Fee
 */
class Totals extends MagentoTotals
{
    /**
     * @var CartRepositoryInterface
     */
    private $quote;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        CartRepositoryInterface $cartRepositoryInterface,
        array $data = []
    ) {
        $this->quote = $cartRepositoryInterface;
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * Initialize all order totals relates with tax
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    public function initTotals()
    {
        $quoteId = $this->getOrder()->getQuoteId();
        $quote = $this->quote->get($quoteId);
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        if ($quote->getTransactionFee() > 0) {
            $fee = new \Magento\Framework\DataObject(
                [
                    'code' => 'transaction_fee',
                    'strong' => false,
                    'value' => $quote->getTransactionFee(),
                    'label' => __('Marketplace Transaction Fee'),
                ]
            );

            $parent->addTotal($fee, 'transaction_fee');
        }

        return $this;
    }
}
