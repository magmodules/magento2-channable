<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Webhook;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magmodules\Channable\Service\Order\Shipping\Fulfillment;

class Shipments
{

    /**
     * @var ShipmentCollectionFactory
     */
    private $shipmentCollectionFactory;

    /**
     * @var DateTime
     */
    private $coreDate;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Fulfillment
     */
    private $fulfillment;

    /**
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param DateTime $coreDate
     * @param TimezoneInterface $localeDate
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Fulfillment $fulfillment
     */
    public function __construct(
        ShipmentCollectionFactory $shipmentCollectionFactory,
        DateTime $coreDate,
        TimezoneInterface $localeDate,
        OrderCollectionFactory $orderCollectionFactory,
        Fulfillment $fulfillment
    ) {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->coreDate = $coreDate;
        $this->localeDate = $localeDate;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fulfillment = $fulfillment;
    }

    /**
     * Get all shipments by timespan offset
     *
     * @param int $timespan
     * @return array
     */
    public function execute(int $timespan): array
    {
        $response = [];
        $orderIncrements = [];

        $collection = $this->shipmentCollectionFactory->create();
        $collection->addFieldToFilter(
            'main_table.created_at',
            ['gteq' => $this->getDateTime($timespan)]
        )->join(
            ['so' => $collection->getTable('sales_order_grid')],
            'main_table.order_id = so.entity_id',
            [
                'order_increment_id' => 'so.increment_id',
                'channable_id' => 'so.channable_id',
                'status' => 'so.status'
            ]
        )->join(
            ['sop' => $collection->getTable('sales_order_payment')],
            'main_table.order_id = sop.parent_id',
            ['payment_method' => 'sop.method']
        )->addFieldToFilter('sop.method', 'channable');

        foreach ($collection as $shipment) {
            $response[] = [
                'id' => $shipment->getOrderIncrementId(),
                'type'=> 'shipment',
                'status' => $shipment->getStatus(),
                'date' => $this->localeDate->date($shipment->getCreatedAt())->format('Y-m-d H:i:s'),
                'fulfillment' => $this->fulfillment->execute($shipment)
            ];
            $orderIncrements[] = $shipment->getOrderIncrementId();
            unset($data);
        }

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter(
                'updated_at',
                ['gteq' => $this->getDateTime($timespan)]
            )->addFieldToFilter(
                'state',
                ['in' => [OrderModel::STATE_COMPLETE, OrderModel::STATE_CLOSED, OrderModel::STATE_CANCELED]]
            )->join(
                ['sop' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = sop.parent_id',
                ['payment_method' => 'sop.method']
            )->addFieldToFilter('sop.method', 'channable');

        if (!empty($orderIncrements)) {
            $orders->addFieldToFilter('increment_id', ['nin' => $orderIncrements]);
        }
        foreach ($orders as $order) {
            $response[] = [
                'id' => $order->getIncrementId(),
                'type' => 'order',
                'status' => $order->getState() == OrderModel::STATE_COMPLETE
                    ? OrderModel::STATE_COMPLETE
                    : OrderModel::STATE_CANCELED,
                'date' => $this->localeDate->date($order->getUpdatedAt())->format('Y-m-d H:i:s')
            ];
        }

        return $response;
    }

    /**
     * Get start date by offset
     *
     * @param int $timespan
     * @return false|string
     */
    private function getDateTime(int $timespan)
    {
        return date(
            'Y-m-d H:i:s',
            strtotime(sprintf('- %s hours', $timespan), strtotime($this->coreDate->date("Y-m-d H:i:s")))
        );
    }
}
