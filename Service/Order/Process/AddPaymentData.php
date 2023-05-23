<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Save additional information about the marketplace order.
 */
class AddPaymentData
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * AddPaymentData constructor.
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Save Channable specific data to the order
     *
     * @param OrderInterface $order
     * @param array $orderData
     * @param bool $lvb
     */
    public function execute($order, $orderData, $lvb = false)
    {
        $payment = $order->getPayment();
        if (!empty($orderData['channable_id'])) {
            $payment->setAdditionalInformation('channable_id', $orderData['channable_id']);
        }

        if (!empty($orderData['channel_id'])) {
            $payment->setAdditionalInformation('channel_id', $orderData['channel_id']);
        }

        if (!empty($orderData['channel_customer_number'])) {
            $payment->setAdditionalInformation('channel_customer_number', $orderData['channel_customer_number']);
        }

        if (!empty($orderData['shipment_promise'])) {
            $payment->setAdditionalInformation('shipment_promise', $orderData['shipment_promise']);
        }

        if ($orderData['price']['payment_method'] == 'Zalando') {
            $externalArticleNumbers = [];
            foreach ($orderData['products'] as $product) {
                $externalArticleNumbers[] = $product['article_number'] ?? null;
            }
            $payment->setAdditionalInformation(
                'external_article_numbers',
                implode(', ', array_filter($externalArticleNumbers))
            );
        }

        if (!empty($orderData['shipment_promise'])) {
            $payment->setAdditionalInformation('shipment_promise', $orderData['shipment_promise']);
        }

        $commissionValue = isset($orderData['price']['commission']) ? $orderData['price']['commission'] : 0;
        $commission = $orderData['price']['currency'] . ' ' . $commissionValue;
        $payment->setAdditionalInformation('commission', $commission);

        if (!empty($orderData['channel_name'])) {
            if ($lvb) {
                $payment->setAdditionalInformation('channel_name', ucfirst($orderData['channel_name']) . ' LVB');
            } else {
                $payment->setAdditionalInformation('channel_name', ucfirst($orderData['channel_name']));
            }
        }

        if (!empty($orderData['channable_channel_label'])) {
            $payment->setAdditionalInformation('channel_label', $orderData['channable_channel_label']);
        }

        $itemRows = [];
        foreach ($orderData['products'] as $product) {
            $itemRows[] = [
                'title' => $product['title'],
                'ean' => $product['ean'],
                'delivery_period' => $product['delivery_period']
            ];
        }
        $payment->setAdditionalInformation('delivery', $itemRows);
        if (!empty($orderData['memo'])) {
            $payment->setAdditionalInformation('memo', $orderData['memo']);
        }

        $this->orderRepository->save($order);
    }
}
