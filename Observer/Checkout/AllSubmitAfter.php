<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\General as GeneralHelper;

class AllSubmitAfter implements ObserverInterface
{

    const OBSERVER_TYPE = 'AllSubmitAfter';

    private $itemModel;
    private $generalHelper;

    /**
     * AllSubmitAfter constructor.
     *
     * @param ItemModel     $itemModel
     * @param GeneralHelper $generalHelper
     */
    public function __construct(
        ItemModel $itemModel,
        GeneralHelper $generalHelper
    ) {
        $this->itemModel = $itemModel;
        $this->generalHelper = $generalHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->generalHelper->getMarketplaceEnabled()) {
            return;
        }

        $order = $observer->getEvent()->getOrder();
        if ($order) {
            foreach ($order->getAllItems() as $product) {
                if ($product->getProductType() == 'simple') {
                    $this->itemModel->invalidateProduct($product->getProductId(), self::OBSERVER_TYPE);
                }
            }
        }
    }
}
