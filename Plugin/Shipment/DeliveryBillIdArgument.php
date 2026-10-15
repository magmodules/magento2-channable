<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin\Shipment;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;

/**
 * Plugin to transfer the delivery bill ID supplied as shipment creation argument.
 *
 * This allows ERP systems to ship an order and supply their own delivery note number
 * in a single API call: POST /V1/order/:orderId/ship
 */
class DeliveryBillIdArgument
{

    /**
     * @param ShipmentDocumentFactory $subject
     * @param ShipmentInterface $result
     * @param OrderInterface $order
     * @param array $items
     * @param array $tracks
     * @param ShipmentCommentCreationInterface|null $comment
     * @param bool $appendComment
     * @param array $packages
     * @param ShipmentCreationArgumentsInterface|null $arguments
     *
     * @return ShipmentInterface
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(
        ShipmentDocumentFactory $subject,
        ShipmentInterface $result,
        OrderInterface $order,
        array $items = [],
        array $tracks = [],
        ?ShipmentCommentCreationInterface $comment = null,
        bool $appendComment = false,
        array $packages = [],
        ?ShipmentCreationArgumentsInterface $arguments = null
    ): ShipmentInterface {
        $extensionAttributes = $arguments !== null ? $arguments->getExtensionAttributes() : null;
        if ($extensionAttributes === null) {
            return $result;
        }

        $deliveryBillId = $extensionAttributes->getChannableDeliveryBillId();
        if ($deliveryBillId !== null) {
            $result->setData(DeliveryBillId::ATTRIBUTE_CODE, $deliveryBillId);
        }

        return $result;
    }
}
