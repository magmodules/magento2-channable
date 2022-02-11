<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConverter;

/**
 * Create shipment for order.
 */
class CreateShipment
{

    /**
     * @var OrderConverter
     */
    private $orderConverter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * CreateShipment constructor.
     *
     * @param OrderConverter $orderConverter
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderCommentHistory $orderCommentHistory
     */
    public function __construct(
        OrderConverter $orderConverter,
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderCommentHistory $orderCommentHistory
    ) {
        $this->orderConverter = $orderConverter;
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    /**
     * Create a shipment for order
     *
     * @param OrderInterface $order
     * @param string $msg
     * @param bool $isCustomerNotified
     *
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function execute($order, string $msg = '', bool $isCustomerNotified = false)
    {
        if ($order->canShip()) {
            $shipment = $this->orderConverter->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $qtyShipped = $orderItem->getQtyToShip();
                $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                $shipment->addItem($shipmentItem);
            }

            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);

            /**
             * Check if we need to set source of stock when MSI is enabled.
             */
            if (method_exists($shipment->getExtensionAttributes(), 'setSourceCode')) {
                $shipment->getExtensionAttributes()->setSourceCode('default');
            }

            $this->shipmentRepository->save($shipment);
            $this->orderRepository->save($shipment->getOrder());

            if ($msg) {
                $this->orderCommentHistory->add($order, __($msg), $isCustomerNotified);
            }
        }
    }
}
