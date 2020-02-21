<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\SalesRule;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class ValidatorProcess
 *
 * @package Magmodules\Channable\Observer\SalesRule
 */
class ValidatorProcess implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getPaymentMethod() == \Magmodules\Channable\Model\Payment\Channable::CODE) {
            /** @var \Magento\SalesRule\Model\Rule $salesRule */
            $salesRule = $observer->getEvent()->getRule();
            $salesRule->setRuleId('')->setStopRulesProcessing(true);
        }
    }
}