<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

/**
 * Observer to convert pickup_location
 */
class QuoteSubmitBefore implements ObserverInterface
{
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * QuoteSubmitBefore constructor.
     * @param LogRepository $logRepository
     */
    public function __construct(
        LogRepository $logRepository
    ) {
        $this->logRepository = $logRepository;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            /** @var \Magento\Quote\Model\Quote\ $quote */
            $quote = $observer->getEvent()->getQuote();
            if ($pickupPoint = $quote->getShippingAddress()->getExtensionAttributes()->getChannablePickupLocation()) {
                $order->setData('channable_pickup_location', $pickupPoint);
            }
        } catch (\Exception $e) {
            $this->logRepository->addErrorLog('convert quote', $e->getMessage());
        }
        return $this;
    }
}
