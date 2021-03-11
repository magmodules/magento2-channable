<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\SalesRule;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRule\Model\Rule\Action\Discount\Data as DiscountData;
use Magmodules\Channable\Model\Payment\Channable as ChannablePaymentMethod;

/**
 * Class ValidatorProcess
 * Unset discount amount for orders imported by Channable.
 */
class ValidatorProcess implements ObserverInterface
{

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getPayment()->getMethod() == ChannablePaymentMethod::CODE) {
            /** @var DiscountData $discountData */
            $discountData = $observer->getEvent()->getResult();
            $discountData->setAmount(0)->setBaseAmount(0);
        }
    }
}