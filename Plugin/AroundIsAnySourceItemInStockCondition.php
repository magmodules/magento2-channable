<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsAnySourceItemInStockCondition;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;

/**
 * Plugin for IsAnySourceItemInStockCondition
 *
 * This class has also a hidden dependency not listed in the constuctor:
 * - \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface
 *
 * This class is only loaded when MSI is enabled, but when setup:di:compile runs it will still fail on this class
 * in Magento 2.2 because it doesn't exist. That's why they are using the object manager.
 */
class AroundIsAnySourceItemInStockCondition
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AroundIsAnySourceItemInStockCondition constructor.
     *
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Skip stock condition check for adding products to order.
     *
     * Out-of-stock products: depending on configuration setting
     * LVB Orders: these orders are shipped from external warehouse
     *
     * @param IsAnySourceItemInStockCondition $subject
     * @param \Closure                        $proceed
     * @param string                          $sku
     * @param int                             $stockId
     * @param float                           $requestedQty
     *
     * @return mixed
     */
    public function aroundExecute(
        IsAnySourceItemInStockCondition $subject,
        $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        if ($this->checkoutSession->getChannableSkipReservation()
            && interface_exists(ProductSalableResultInterface::class)
        ) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            return $objectManager->getInstance()->create(
                ProductSalableResultInterface::class,
                ['errors' => []]
            );
        }
        return $proceed($sku, $stockId, $requestedQty);
    }
}
