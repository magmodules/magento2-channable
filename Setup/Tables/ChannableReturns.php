<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup\Tables;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class ChannableReturns
 *
 * @package Magmodules\Channable\Setup\Tables
 */
class ChannableReturns
{

    const TABLE_NAME = 'channable_returns';

    /**
     * @var array
     */
    protected static $tableData = [
        'title'   => self::TABLE_NAME,
        'columns' => [
            'id'                   => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            ],
            'store_id'             => [
                'type'   => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
            ],
            'order_id'             => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            ],
            'channel_name'         => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true],
            ],
            'channel_id'           => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            ],
            'channable_id'         => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            ],
            'magento_order_id'     => [
                'type'   => Table::TYPE_INTEGER,
                'length' => 255,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            ],
            'magento_increment_id' => [
                'type'   => Table::TYPE_TEXT,
                'length' => 50,
                'option' => ['nullable' => true]
            ],
            'item'                 => [
                'type'   => Table::TYPE_TEXT,
                'length' => '',
                'option' => ['nullable' => true]
            ],
            'customer_name'        => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'customer'             => [
                'type'   => Table::TYPE_TEXT,
                'length' => '',
                'option' => ['nullable' => true]
            ],
            'address'              => [
                'type'   => Table::TYPE_TEXT,
                'length' => '',
                'option' => ['nullable' => true]
            ],
            'reason'               => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'comment'              => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'status'               => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'created_at'           => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['default' => Table::TIMESTAMP_INIT]
            ],
            'updated_at'           => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['default' => Table::TIMESTAMP_INIT_UPDATE]
            ],
        ],
        'comment' => 'Channable Returns Table',
        'indexes' => [
            'store_id',
            'order_id',
            'magento_order_id',
            'magento_increment_id',
            'created_at',
            'status'
        ]
    ];

    /**
     * @return array
     */
    public static function getData()
    {
        return self::$tableData;
    }
}
