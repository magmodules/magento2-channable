<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Psr\Log\LoggerInterface;

class OrderItemCancel implements ObserverInterface
{

    const OBSERVER_TYPE = 'OrderItemCancel';

    /**
     * @var ItemModel
     */
    private $itemModel;

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OrderItemCancel constructor.
     *
     * @param ItemModel       $itemModel
     * @param ItemHelper      $itemHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ItemModel $itemModel,
        ItemHelper $itemHelper,
        LoggerInterface $logger
    ) {
        $this->itemModel = $itemModel;
        $this->itemHelper = $itemHelper;
        $this->logger = $logger;
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
            $item = $observer->getEvent()->getItem();
            $childrenItems = $item->getChildrenItems();
            if (empty($childrenItems)) {
                $this->itemModel->invalidateProduct($item->getProductId(), self::OBSERVER_TYPE);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->logger->debug('exception');
        }
    }
}