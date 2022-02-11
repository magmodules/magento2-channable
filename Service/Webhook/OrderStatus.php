<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Webhook;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magmodules\Channable\Model\Payment\Channable;

class OrderStatus
{

    const NO_ORDER_FOUND = 'No order found with Increment ID: %s';
    const NO_CHANNABLE_ORDER = 'Order with Increment ID %s is not a Channable Order';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $incrementId
     * @return array
     */
    public function execute(string $incrementId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        /** @var OrderInterface|null $order */
        $order = reset($orders);

        if (!$order) {
            return  [
                'validated' => 'false',
                'errors' => sprintf(self::NO_ORDER_FOUND, $incrementId)
            ];
        }

        if ($order->getPayment()->getMethod() !== Channable::CODE) {
            return  [
                'validated' => 'false',
                'errors' => sprintf(self::NO_CHANNABLE_ORDER. $incrementId)
            ];
        }

        $response = [
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus()
        ];
        if (!$tracking = $this->getTracking($order)) {
            return $response;
        }
        foreach ($tracking as $track) {
            $response['fulfillment']['tracking_code'][] = $track['tracking'];
            $response['fulfillment']['title'][] = $track['title'];
            $response['fulfillment']['carrier_code'][] = $track['carrier_code'];
        }
        return $response;
    }

    /**
     * @param OrderInterface $order
     *
     * @return array|bool
     */
    private function getTracking(OrderInterface $order)
    {
        $tracking = [];
        $shipmentCollection = $order->getShipmentsCollection();
        foreach ($shipmentCollection as $shipment) {
            foreach ($shipment->getAllTracks() as $tracknum) {
                $tracking[] = [
                    'tracking' => $tracknum->getNumber(),
                    'title' => $tracknum->getTitle(),
                    'carrier_code' => $tracknum->getCarrierCode()
                ];
            }
        }
        return (!empty($tracking))
            ? ($tracking)
            : (false);
    }
}
