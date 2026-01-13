<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magmodules\Channable\Api\Order\RepositoryInterface as ChannableOrderRepository;

class UpdateMemo
{
    private ChannableOrderRepository $channableOrderRepository;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        ChannableOrderRepository $channableOrderRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->channableOrderRepository = $channableOrderRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Append memo text to existing order memo
     *
     * @param int $channableId
     * @param string $memoText
     * @return array
     * @throws LocalizedException
     */
    public function execute(int $channableId, string $memoText): array
    {
        $memoText = trim($memoText);
        if ($memoText === '') {
            throw new LocalizedException(__('Memo text cannot be empty.'));
        }

        $channableOrderId = $this->channableOrderRepository->getByChannableId($channableId);
        if (!$channableOrderId) {
            throw new NoSuchEntityException(
                __('Order with channable_id "%1" does not exist.', $channableId)
            );
        }

        $channableOrder = $this->channableOrderRepository->get((int)$channableOrderId);
        $magentoOrderId = $channableOrder->getMagentoOrderId();

        if (!$magentoOrderId) {
            throw new LocalizedException(
                __('Channable order "%1" has not been imported to Magento yet.', $channableId)
            );
        }

        $order = $this->orderRepository->get($magentoOrderId);
        $payment = $order->getPayment();
        if ($payment === null) {
            throw new LocalizedException(
                __('Order "%1" has no payment information.', $order->getIncrementId())
            );
        }

        $existingMemo = $payment->getAdditionalInformation('memo');
        if ($existingMemo) {
            $newMemo = $existingMemo . "\n" . $memoText;
        } else {
            $newMemo = $memoText;
        }

        $payment->setAdditionalInformation('memo', $newMemo);
        $this->orderRepository->save($order);

        return [
            'success' => true,
            'order_id' => $order->getIncrementId(),
            'channable_id' => $channableId
        ];
    }
}
