<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Shipping;

use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Order\Shipment;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Plugin\Shipment\DeliveryBillId as DeliveryBillIdPlugin;

/**
 * Service class to return Fulfillment data
 */
class Fulfillment
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
     * Returns fulfillment data for shipment.
     * See; https://docs.channable.com/api/v1/#shipment-update
     *
     * The delivery_bill_id defaults to the Magento shipment increment ID. This ID is printed on
     * the default Magento packing slip and is therefore available on the physical delivery note.
     * ERP systems can supply their own delivery note number through the channable_delivery_bill_id
     * extension attribute on the shipment.
     *
     * @param Shipment $shipment
     *
     * @return array
     */
    public function execute(Shipment $shipment): array
    {
        $fulfillment = [];
        $storeId = (int)$shipment->getStoreId();

        foreach ($shipment->getAllTracks() as $track) {
            if ($this->isReturnLabel($track, $storeId)) {
                $fulfillment['return_tracking_code'][] = $track->getNumber();
                $fulfillment['return_transporter'][] = $track->getCarrierCode();
            } else {
                $fulfillment['tracking_code'][] = $track->getNumber();
                $fulfillment['title'][] = $track->getTitle();
                $fulfillment['carrier_code'][] = $track->getCarrierCode();
            }
        }

        $fulfillment['delivery_bill_id'] = $this->getDeliveryBillId($shipment);

        return $fulfillment;
    }

    /**
     * Returns the delivery bill ID for the shipment.
     *
     * Uses the value supplied by an ERP through the channable_delivery_bill_id extension
     * attribute, and falls back to the Magento shipment increment ID when not set.
     *
     * @param Shipment $shipment
     *
     * @return string
     */
    private function getDeliveryBillId(Shipment $shipment): string
    {
        // Shipments loaded through a collection carry the value as data, not as extension attribute
        $deliveryBillId = $shipment->getData(DeliveryBillIdPlugin::ATTRIBUTE_CODE);

        if (!$deliveryBillId) {
            $extensionAttributes = $shipment->getExtensionAttributes();
            $deliveryBillId = $extensionAttributes !== null
                ? $extensionAttributes->getChannableDeliveryBillId()
                : null;
        }

        return $deliveryBillId ? (string)$deliveryBillId : (string)$shipment->getIncrementId();
    }

    /**
     * Check if tracking entry should be used as a return tracking label
     *
     * @param ShipmentTrackInterface $track
     * @param int $storeId
     *
     * @return bool
     */
    public function isReturnLabel(ShipmentTrackInterface $track, int $storeId): bool
    {
        switch ($this->configProvider->useReturnLabel($storeId)) {
            case 'regex':
                foreach ($this->configProvider->getReturnLabelRegexp($storeId) as $conditions) {
                    if ($conditions['carrier_code'] != $track->getCarrierCode()
                        && $conditions['carrier_code'] != 'all'
                    ) {
                        return false;
                    }
                    if (strpos((string)$track->getTitle(), (string)$conditions['title_regexp']) !== false) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}
