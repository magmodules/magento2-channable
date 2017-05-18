<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\General as GeneralHelper;

class ProductSaveAfter implements ObserverInterface
{

    const OBSERVER_TYPE = 'ProductSaveAfter';

    private $itemModel;
    private $generalHelper;

    /**
     * ProductSaveAfter constructor.
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
        if ($this->generalHelper->getMarketplaceEnabled()) {
            return;
        }

        $product = $observer->getData('product');
        $this->itemModel->invalidateProduct($product->getId(), self::OBSERVER_TYPE);
    }
}
