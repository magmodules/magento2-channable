<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Send Order Email
 */
class SendOrderEmail
{
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @param OrderSender $orderSender
     * @param OrderCommentHistory $orderCommentHistory
     */
    public function __construct(
        OrderSender $orderSender,
        OrderCommentHistory $orderCommentHistory
    ) {
        $this->orderSender = $orderSender;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    /**
     * @param OrderInterface $order
     * @throws CouldNotSaveException
     */
    public function execute(OrderInterface $order)
    {
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
            $this->orderCommentHistory->add(
                $order,
                __('The order confirmation email was sent'),
                true
            );
        }
    }
}
