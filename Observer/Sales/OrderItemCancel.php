<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\General as GeneralHelper;

class OrderItemCancel implements ObserverInterface
{

    const OBSERVER_TYPE = 'OrderItemCancel';

    /**
     * @var ItemModel
     */
    private $itemModel;

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * OrderItemCancel constructor.
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

        $item = $observer->getEvent()->getItem();
        $childrenItems = $item->getChildrenItems();
        if (empty($childrenItems)) {
            $this->itemModel->invalidateProduct($item->getProductId(), self::OBSERVER_TYPE);
        }
    }
}
