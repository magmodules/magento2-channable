<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Returns\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Channable Returns Resource Model
 */
class ResourceModel extends AbstractDb
{

    /**
     * Constants related to specific db layer
     */
    public const ID_FIELD_NAME = 'id';

    /**
     * @var DateTime
     */
    private $date;

    /**
     * ResourceModel constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param null $resourcePrefix
     */
    public function __construct(
        Context $context,
        DateTime $date,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->date = $date;
    }

    /**
     * Initialize with table name and primary field
     */
    protected function _construct()
    {
        $this->_init('channable_returns', self::ID_FIELD_NAME);
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel $object
     *
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object): AbstractDb
    {
        $object->setData('updated_at', $this->date->gmtDate());
        return parent::_beforeSave($object);
    }
}
