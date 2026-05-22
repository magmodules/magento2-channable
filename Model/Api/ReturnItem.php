<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Api;

use Magento\Framework\DataObject;
use Magmodules\Channable\Api\Returns\ItemInterface;

class ReturnItem extends DataObject implements ItemInterface
{
    public function getEntityId(): int
    {
        return (int) $this->getData('entity_id');
    }

    public function getChannableId(): int
    {
        return (int) $this->getData('channable_id');
    }

    public function getChannelId(): ?string
    {
        return $this->getData('channel_id');
    }

    public function getChannelName(): ?string
    {
        return $this->getData('channel_name');
    }

    public function getChannelReturnId(): ?string
    {
        return $this->getData('channel_return_id');
    }

    public function getChannelOrderId(): ?string
    {
        return $this->getData('channel_order_id');
    }

    public function getChannelOrderIdInternal(): ?string
    {
        return $this->getData('channel_order_id_internal');
    }

    public function getPlatformOrderId(): ?string
    {
        return $this->getData('platform_order_id');
    }

    public function getMagentoOrderId(): ?int
    {
        $val = $this->getData('magento_order_id');
        return $val !== null ? (int) $val : null;
    }

    public function getMagentoIncrementId(): ?string
    {
        return $this->getData('magento_increment_id');
    }

    public function getMagentoCreditmemoId(): ?int
    {
        $val = $this->getData('magento_creditmemo_id');
        return $val !== null ? (int) $val : null;
    }

    public function getMagentoCreditmemoIncrementId(): ?string
    {
        return $this->getData('magento_creditmemo_increment_id');
    }

    public function getCustomerName(): ?string
    {
        return $this->getData('customer_name');
    }

    public function getReason(): ?string
    {
        return $this->getData('reason');
    }

    public function getComment(): ?string
    {
        return $this->getData('comment');
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}
