<?php
/**
 *  Copyright © 2019 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup\Tables;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class ChannableItems
 *
 * @package Magmodules\Channable\Setup\Tables
 */
class ChannableItems
{

    const TABLE_NAME = 'channable_items';

    /**
     * @var array
     */
    protected static $tableData = [
        'title'   => self::TABLE_NAME,
        'columns' => [
            'item_id'        => [
                'type'   => Table::TYPE_BIGINT,
                'length' => null,
                'option' => ['identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            ],
            'store_id'       => [
                'type'   => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
            ],
            'id'             => [
                'type'   => Table::TYPE_INTEGER,
                'length' => null,
                'option' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
            ],
            'title'          => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => false],
            ],
            'price'          => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'option' => ['nullable' => false],
            ],
            'discount_price' => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'option' => ['nullable' => false],
            ],
            'qty'            => [
                'type'   => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'option' => ['default' => '0.0000'],
            ],
            'is_in_stock'    => [
                'type'   => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['nullable' => false, 'default' => '0'],
            ],
            'created_at'     => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => false, 'default' => Table::TIMESTAMP_INIT]
            ],
            'updated_at'     => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => [
                    'nullable' => false,
                    'default'  => Table::TIMESTAMP_INIT_UPDATE
                ]
            ],
            'last_call'      => [
                'type'   => Table::TYPE_TIMESTAMP,
                'length' => null,
                'option' => ['nullable' => true, 'default' => '']
            ],

            'call_result' => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'status'      => [
                'type'   => Table::TYPE_TEXT,
                'length' => 255,
                'option' => ['nullable' => true]
            ],
            'needs_update'      => [
                'type'   => Table::TYPE_SMALLINT,
                'length' => null,
                'option' => ['nullable' => false, 'default' => '0'],
            ],
        ],
        'comment' => 'Channable Items Table',
        'indexes' => [
            'store_id',
            'id',
            'needs_update',
            'updated_at'
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
