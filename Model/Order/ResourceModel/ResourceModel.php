<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Order\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Channable Orders Resource Model
 */
class ResourceModel extends AbstractDb
{

    /**
     * Constants related to specific db layer
     */
    const ID_FIELD_NAME = 'entity_id';
    /**
     * Serializable field: regels
     *
     * @var array
     */
    protected $_serializableFields = [
        'products' => [[], []],
        'customer' => [[], []],
        'billing' => [[], []],
        'shipping' => [[], []],
        'price' => [[], []]
    ];
    /**
     * @var DateTime
     */
    private $date;

    /**
     * Resource constructor.
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
     * Check is entity exists and returns entity_id
     *
     * @param int $channableId
     * @return bool
     */
    public function getByChannableId($channableId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('channable_orders'), self::ID_FIELD_NAME)
            ->where('channable_id = :channable_id');
        $bind = [':channable_id' => $channableId];
        return $connection->fetchOne($select, $bind);
    }

    /**
     * Initialize with table name and primary field
     */
    protected function _construct()
    {
        $this->_init($this->getTable('channable_orders'), self::ID_FIELD_NAME);
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
