<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Webhook;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magmodules\Channable\Model\Payment\Channable;
use Magmodules\Channable\Service\Order\Shipping\Fulfillment;

class OrderStatus
{

    private const NO_ORDER_FOUND = 'No order found with Increment ID: %s';
    private const NO_CHANNABLE_ORDER = 'Order with Increment ID %s is not a Channable Order';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var Fulfillment
     */
    private $fulfillment;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param Fulfillment $fulfillment
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        Fulfillment $fulfillment
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->fulfillment = $fulfillment;
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
            return [
                'validated' => 'false',
                'errors' => sprintf(self::NO_ORDER_FOUND, $incrementId)
            ];
        }

        if ($order->getPayment()->getMethod() !== Channable::CODE) {
            return [
                'validated' => 'false',
                'errors' => sprintf(self::NO_CHANNABLE_ORDER, $incrementId)
            ];
        }

        $response = [
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus()
        ];

        if ($fulfillment = $this->getFulfillment($order)) {
            $response['fulfillment'] = $fulfillment;
        }

        return $response;
    }

    /**
     * @param OrderInterface $order
     *
     * @return array|bool
     */
    private function getFulfillment(OrderInterface $order)
    {
        $fulfillment = [];
        $shipmentCollection = $order->getShipmentsCollection();
        foreach ($shipmentCollection as $shipment) {
            $fulfillment += $this->fulfillment->execute($shipment);
        }

        return (!empty($fulfillment))
            ? ($fulfillment)
            : (false);
    }
}
