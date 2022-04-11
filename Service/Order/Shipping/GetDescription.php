<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Sales\Api\Data\OrderInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Model\Carrier\Channable as ChannableCarrier;

/**
 * Get shipping price for quote
 */
class GetDescription
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param OrderInterface $order
     * @param array $orderData
     * @return string|null
     */
    public function execute(OrderInterface $order, array $orderData): ?string
    {
        if ($order->getShippingMethod(true)->getMethod() !== ChannableCarrier::CODE) {
            return null;
        }

        $title = str_replace(
            '{{channable_channel_label}}',
            !empty($orderData['channable_channel_label']) ? $orderData['channable_channel_label'] : 'Channable',
            $this->configProvider->getCarrierTitle($order->getStoreId())
        );

        $name = str_replace(
            '{{shipment_method}}',
            !empty($orderData['shipment_method']) ? $orderData['shipment_method'] : 'Shipping',
            $this->configProvider->getCarrierName($order->getStoreId())
        );

        return implode(' - ', [$title, $name]);
    }
}
