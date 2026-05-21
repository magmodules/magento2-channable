<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Api;

use Magento\Framework\DataObject;
use Magmodules\Channable\Api\Order\ItemInterface;

class OrderItem extends DataObject implements ItemInterface
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

    public function getChannelLabel(): ?string
    {
        return $this->getData('channel_label');
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

    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    public function getChannableOrderStatus(): ?string
    {
        return $this->getData('channable_order_status');
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
