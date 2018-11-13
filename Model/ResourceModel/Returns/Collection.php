<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\ResourceModel\Returns;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Magmodules\Channable\Model\ResourceModel\Returns
 */
class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init(
            'Magmodules\Channable\Model\Returns',
            'Magmodules\Channable\Model\ResourceModel\Returns'
        );
    }
}
