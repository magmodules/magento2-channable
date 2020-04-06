<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class AroundIsSalableWithReservationsCondition
 *
 * This class has also a hidden dependencies not listed in the constuctor:
 * - \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface
 *
 * This class is only loaded when MSI is enabled, but when setup:di:compile runs it will still fail on thoses classes
 * in Magento 2.2 because they don't exists. That's why they are using the object manager.
 */
class AroundIsSalableWithReservationsCondition
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * AroundIsSalableWithReservationsCondition constructor.
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Skip MSI Salable With Reservations check on submitting quote for LVB Orders,
     * or with "Enable order for out of stock items" enabled.
     *
     * LVB Orders are shipped from external warehouse and have no impact on stock movement,
     * thus should also be skipped from Salable Check.
     *
     * @param $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return mixed
     */
    public function aroundExecute(
        $subject,
        $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        if ($this->checkoutSession->getChannableSkipQtyCheck()
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