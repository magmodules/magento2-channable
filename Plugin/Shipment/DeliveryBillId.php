<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin\Shipment;

use Magento\Sales\Api\Data\ShipmentExtensionFactory;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Plugin to load and persist the Channable delivery bill ID as extension attribute.
 *
 * Allows ERP systems to push their own delivery note number when creating a shipment
 * through the Magento API. When left empty the shipment increment ID is used instead.
 */
class DeliveryBillId
{

    public const ATTRIBUTE_CODE = 'channable_delivery_bill_id';

    /**
     * @var ShipmentExtensionFactory
     */
    private $extensionFactory;

    /**
     * @param ShipmentExtensionFactory $extensionFactory
     */
    public function __construct(
        ShipmentExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Copy the extension attribute to the entity data prior to saving.
     *
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     *
     * @return array
     */
    public function beforeSave(
        ShipmentRepositoryInterface $subject,
        ShipmentInterface $shipment
    ): array {
        $extensionAttributes = $shipment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return [$shipment];
        }

        $deliveryBillId = $extensionAttributes->getChannableDeliveryBillId();
        if ($deliveryBillId !== null) {
            $shipment->setData(self::ATTRIBUTE_CODE, $deliveryBillId);
        }

        return [$shipment];
    }

    /**
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     */
    public function afterGet(
        ShipmentRepositoryInterface $subject,
        ShipmentInterface $shipment
    ): ShipmentInterface {
        return $this->addExtensionAttribute($shipment);
    }

    /**
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     */
    public function afterSave(
        ShipmentRepositoryInterface $subject,
        ShipmentInterface $shipment
    ): ShipmentInterface {
        return $this->addExtensionAttribute($shipment);
    }

    /**
     * @param ShipmentRepositoryInterface $subject
     * @param ShipmentSearchResultInterface $searchResult
     *
     * @return ShipmentSearchResultInterface
     */
    public function afterGetList(
        ShipmentRepositoryInterface $subject,
        ShipmentSearchResultInterface $searchResult
    ): ShipmentSearchResultInterface {
        foreach ($searchResult->getItems() as $shipment) {
            $this->addExtensionAttribute($shipment);
        }

        return $searchResult;
    }

    /**
     * Expose the stored delivery bill ID as extension attribute.
     *
     * @param ShipmentInterface $shipment
     *
     * @return ShipmentInterface
     */
    private function addExtensionAttribute(ShipmentInterface $shipment): ShipmentInterface
    {
        $extensionAttributes = $shipment->getExtensionAttributes() ?: $this->extensionFactory->create();
        $extensionAttributes->setChannableDeliveryBillId(
            $shipment->getData(self::ATTRIBUTE_CODE) !== null
                ? (string)$shipment->getData(self::ATTRIBUTE_CODE)
                : null
        );
        $shipment->setExtensionAttributes($extensionAttributes);

        return $shipment;
    }
}
