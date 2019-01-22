<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;

/**
 * Class ProductSaveAfter
 *
 * @package Magmodules\Channable\Observer\Catalog
 */
class ProductSaveAfter implements ObserverInterface
{

    const OBSERVER_TYPE = 'ProductSaveAfter';
    /**
     * @var ItemModel
     */
    private $itemModel;
    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * ProductSaveAfter constructor.
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
            $product = $observer->getData('product');
            $this->itemModel->invalidateProduct($product->getId(), self::OBSERVER_TYPE);
        } catch (\Exception $e) {
            $this->itemHelper->addTolog(self::OBSERVER_TYPE, $e->getMessage());
        }

        return $this;
    }
}
