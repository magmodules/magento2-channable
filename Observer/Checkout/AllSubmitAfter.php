<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;

/**
 * Class AllSubmitAfter
 *
 * @package Magmodules\Channable\Observer\Checkout
 */
class AllSubmitAfter implements ObserverInterface
{

    const OBSERVER_TYPE = 'AllSubmitAfter';
    /**
     * @var ItemModel
     */
    private $itemModel;
    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * AllSubmitAfter constructor.
     *
     * @param ItemModel  $itemModel
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        ItemModel $itemModel,
        ItemHelper $itemHelper
    ) {
        $this->itemModel = $itemModel;
        $this->itemHelper = $itemHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->itemHelper->invalidateByObserver()) {
            return $this;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order) {
                foreach ($order->getAllItems() as $item) {
                    if ($item->getProductType() == 'simple') {
                        $this->itemModel->invalidateProduct($item->getProductId(), self::OBSERVER_TYPE);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->itemHelper->addTolog(self::OBSERVER_TYPE, $e->getMessage());
        }

        return $this;
    }
}
