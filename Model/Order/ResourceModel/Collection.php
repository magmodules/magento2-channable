<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Order\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magmodules\Channable\Model\Order\DataModel as ChannableOrderData;
use Magmodules\Channable\Model\Order\ResourceModel\ResourceModel as ChannableOrderResource;

/**
 * Orders collection
 */
class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            ChannableOrderData::class,
            ChannableOrderResource::class
        );
    }
}
