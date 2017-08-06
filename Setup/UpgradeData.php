<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup;

use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeData implements UpgradeDataInterface
{

    const TABLE_NAME_ITEMS = 'channable_items';

    private $salesSetupFactory;

    /**
     * UpgradeSchema constructor.
     *
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "0.9.6", "<")) {
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

            $channableId = [
                'type'     => 'int',
                'visible'  => false,
                'required' => false,
                'label'    => 'Channable: Order ID'
            ];
            $salesSetup->addAttribute('order', 'channable_id', $channableId);

            $channelId = [
                'type'     => 'varchar',
                'visible'  => false,
                'required' => false,
                'label'    => 'Channable: Channel ID'
            ];
            $salesSetup->addAttribute('order', 'channel_id', $channelId);

            $channelName = [
                'type'     => 'varchar',
                'visible'  => false,
                'required' => false,
                'label'    => 'Channable: Channel Name'
            ];
            $salesSetup->addAttribute('order', 'channel_name', $channelName);
        }

        if (version_compare($context->getVersion(), "0.9.7", "<")) {
            $itemsTable = $setup->getTable(self::TABLE_NAME_ITEMS);
            if ($setup->getConnection()->isTableExists($itemsTable) != true) {
                $itemsTable = $setup->getConnection()
                    ->newTable($itemsTable)
                    ->addColumn(
                        'item_id',
                        Table::TYPE_BIGINT,
                        null,
                        [
                            'identity' => false,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary'  => true
                        ],
                        'Item id'
                    )
                    ->addColumn(
                        'store_id',
                        Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => false, 'default' => '0'],
                        'Store id'
                    )
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => '0'],
                        'Store id'
                    )
                    ->addColumn(
                        'title',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => false],
                        'Product Title'
                    )
                    ->addColumn(
                        'price',
                        Table::TYPE_DECIMAL,
                        '12,4',
                        ['nullable' => false],
                        'Product Price'
                    )
                    ->addColumn(
                        'discount_price',
                        Table::TYPE_DECIMAL,
                        '12,4',
                        ['nullable' => true],
                        'Product Discount Price'
                    )
                    ->addColumn(
                        'qty',
                        Table::TYPE_DECIMAL,
                        '12,4',
                        ['default' => '0.0000'],
                        'Product Discount Price'
                    )
                    ->addColumn(
                        'is_in_stock',
                        Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => false, 'default' => '0'],
                        'Stock Status'
                    )
                    ->addColumn(
                        'created_at',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                        'Created At'
                    )
                    ->addColumn(
                        'updated_at',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                        'Updated At'
                    )
                    ->addColumn(
                        'last_call',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => true, 'default' => ''],
                        'Last Call'
                    )
                    ->addColumn(
                        'call_result',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => true],
                        'Call Result'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_TEXT,
                        255,
                        ['nullable' => true],
                        'Status'
                    )
                    ->addColumn(
                        'needs_update',
                        Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => false, 'default' => '0'],
                        'Needs Update'
                    )
                    ->setComment("Channable Items Table")
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                $setup->getConnection()->createTable($itemsTable);
            }
        }

        if (version_compare($context->getVersion(), "0.9.9", "<")) {
            $itemsTable = $setup->getTable(self::TABLE_NAME_ITEMS);
            if ($setup->getConnection()->isTableExists($itemsTable)) {
                $setup->getConnection()
                    ->addColumn(
                        $itemsTable,
                        'gtin',
                        [
                            'type'     => Table::TYPE_TEXT,
                            'length'   => 255,
                            'nullable' => false,
                            'comment'  => 'Product GTIN',
                            'after'    => 'title'
                        ]
                    );
            }
        }

        $setup->endSetup();
    }
}
