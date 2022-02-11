<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;

/**
 * Add comment to order
 */
class OrderCommentHistory
{

    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $historyRepository;

    /**
     * OrderCommentHistory constructor.
     *
     * @param HistoryFactory $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     */
    public function __construct(
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository
    ) {
        $this->historyFactory = $historyFactory;
        $this->historyRepository = $historyRepository;
    }

    /**
     * @param OrderInterface $order
     * @param Phrase $message
     * @param bool $isCustomerNotified
     *
     * @throws CouldNotSaveException
     */
    public function add($order, $message, $isCustomerNotified = false)
    {
        if (!$message->getText()) {
            return;
        }
        /** @var OrderStatusHistoryInterface $history */
        $history = $this->historyFactory->create();
        $history->setParentId($order->getEntityId())
            ->setComment($message)
            ->setStatus($order->getStatus())
            ->setIsCustomerNotified($isCustomerNotified)
            ->setEntityName('order');
        $this->historyRepository->save($history);
    }
}
