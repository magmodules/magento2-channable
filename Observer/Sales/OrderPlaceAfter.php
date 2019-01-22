<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Helper\Item as ItemHelper;

/**
 * Class OrderPlaceAfter
 *
 * @package Magmodules\Channable\Observer\Sales
 */
class OrderPlaceAfter implements ObserverInterface
{

    const OBSERVER_TYPE = 'OrderPlaceAfter';

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * OrderItemCancel constructor.
     *
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        ItemHelper $itemHelper
    ) {
        $this->itemHelper = $itemHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order->getPayment()->getMethod() == 'channable') {
                $order->setCanSendNewEmailFlag(false);
            }
        } catch (\Exception $e) {
            $this->itemHelper->addTolog(self::OBSERVER_TYPE, $e->getMessage());
        }
        return $this;
    }
}
