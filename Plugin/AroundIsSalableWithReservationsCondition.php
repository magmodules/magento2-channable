<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\Framework\App\ObjectManager;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class AroundIsSalableWithReservationsCondition
 *
 * This class also has a hidden dependency not listed in the constuctor:
 * - \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory
 *
 * This class is only loaded when MSI is enabled, but when setup:di:compile runs it will still fail on this class
 * in Magento 2.2 because it does not exist. That's why they are using the object manager.
 */
class AroundIsSalableWithReservationsCondition
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * AroundIsSalableWithReservationsCondition constructor.
     * @param CheckoutSession $checkoutSession
     * @param ObjectManager $objectManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ObjectManager $objectManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->objectManager = $objectManager;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return mixed
     */
    public function aroundExecute(
        $subject,
        \Closure $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        if ($this->checkoutSession->getChannableSkipQtyCheck()
            && class_exists(ProductSalableResultInterfaceFactory::class)
        ) {
            return $this->objectManager->getInstance()->create(
                ProductSalableResultInterfaceFactory::class,
                ['errors' => []]
            );
        }

        $proceed($sku, $stockId, $requestedQty);
    }
}