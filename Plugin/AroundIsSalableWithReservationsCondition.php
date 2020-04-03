<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class AroundIsSalableWithReservationsCondition
{

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * AroundIsSalableWithReservationsCondition constructor.
     * @param CheckoutSession $checkoutSession
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ProductSalableResultInterfaceFactory $productSalableResultFactory
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->productSalableResultFactory = $productSalableResultFactory;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface
     */
    public function aroundExecute(
        $subject,
        \Closure $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        if ($this->checkoutSession->getChannableSkipQtyCheck()) {
            return $this->productSalableResultFactory->create(['errors' => []]);
        }

        $proceed($sku, $stockId, $requestedQty);
    }
}