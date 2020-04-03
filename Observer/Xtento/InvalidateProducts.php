<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Xtento;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magmodules\Channable\Model\Item as ItemModel;

/**
 * Class InvalidateProducts
 */
class InvalidateProducts implements ObserverInterface
{

    const OBSERVER_TYPE = 'XtentoInvalidateProducts';
    const INVALIDATION_TYPE = '3rd party module';

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @var ItemModel
     */
    private $itemModel;

    /**
     * InvalidateProducts constructor.
     * @param ItemHelper $itemHelper
     * @param ItemModel $itemModel
     */
    public function __construct(
        ItemHelper $itemHelper,
        ItemModel $itemModel
    ) {
        $this->itemHelper = $itemHelper;
        $this->itemModel = $itemModel;
    }

    /**
     * Invalidate Channable Items observer for the Xtento Stock Import module.
     * Fired upon the xtento_stockimport_stockupdate_after event.
     *
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
            foreach ($observer->getModifiedStockItems() as $productId) {
                $this->itemModel->invalidateProduct($productId, self::INVALIDATION_TYPE);
            }
        } catch (\Exception $e) {
            $this->itemHelper->addTolog(self::OBSERVER_TYPE, $e->getMessage());
        }

        return $this;
    }
}