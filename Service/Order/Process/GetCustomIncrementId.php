<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Process;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Check and get custom Increment ID based on marketplace order ID
 */
class GetCustomIncrementId
{

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollection;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * GetCustomIncrementId constructor.
     * @param OrderCollectionFactory $orderCollection
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        OrderCollectionFactory $orderCollection,
        ConfigProvider $configProvider
    ) {
        $this->orderCollection = $orderCollection;
        $this->configProvider = $configProvider;
    }

    /**
     * Generate and validate new increment ID based on marketplace ID
     *
     * @param array $orderData
     * @param StoreInterface $store
     * @return null|string
     */
    public function execute(array $orderData, StoreInterface $store): ?string
    {
        if (!$this->configProvider->useChannelOrderAsOrderIncrementId((int)$store->getId())) {
            return null;
        }

        $channelId = $orderData['channel_id'];
        $prefix = $this->configProvider->getOrderIdPrefix((int)$store->getId());
        if ($this->configProvider->stripChannelId((int)$store->getId())) {
            $newIncrementId = $prefix . preg_replace('/[^a-zA-Z0-9-]+/', '', $channelId);
        } else {
            $newIncrementId = $prefix . preg_replace('/\s+/', '', $channelId);
        }

        $orderCheck = $this->orderCollection->create()
            ->addFieldToFilter('increment_id', ['eq' => $newIncrementId])
            ->getSize();

        if ($orderCheck) {
            /** @var Order $lastOrder */
            $lastOrder = $this->orderCollection->create()
                ->addFieldToFilter('increment_id', ['like' => $newIncrementId . '-%'])
                ->getLastItem();

            if ($lastOrder->getIncrementId()) {
                $suffix = substr($lastOrder->getIncrementId(), strlen($newIncrementId) + 1);
                if (is_numeric($suffix)) {
                    $newIncrementId .= '-' . ((int)$suffix + 1);
                } else {
                    $newIncrementId .= '-1';
                }
            } else {
                $newIncrementId .= '-1';
            }
        }

        return $newIncrementId;
    }
}
