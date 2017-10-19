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
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;

class UpgradeData implements UpgradeDataInterface
{

    const TABLE_NAME_ITEMS = 'channable_items';

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * UpgradeData constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface   $objectManager
     * @param SalesSetupFactory        $salesSetupFactory
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->productMetadata = $productMetadata;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->objectManager = $objectManager;
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

        if (version_compare($context->getVersion(), "1.0.3", "<")) {
            $this->addIndexes($setup);
        }

        if (version_compare($context->getVersion(), "1.0.5", "<")) {
            $this->convertSerializedDataToJson($setup);
        }

        $setup->endSetup();
    }

    /**
     * Add Indexes to Items Table.
     * @param ModuleDataSetupInterface $setup
     */
    public function addIndexes(ModuleDataSetupInterface $setup)
    {
        $itemsTable = $setup->getTable(self::TABLE_NAME_ITEMS);
        if ($setup->getConnection()->isTableExists($itemsTable)) {
            $setup->getConnection()->addIndex(
                $itemsTable,
                $setup->getConnection()->getIndexName(
                    $itemsTable,
                    'store_id',
                    'store_id'
                ),
                'store_id'
            );
            $setup->getConnection()->addIndex(
                $itemsTable,
                $setup->getConnection()->getIndexName(
                    $itemsTable,
                    'id',
                    'id'
                ),
                'id'
            );
            $setup->getConnection()->addIndex(
                $itemsTable,
                $setup->getConnection()->getIndexName(
                    $itemsTable,
                    'needs_update',
                    'needs_update'
                ),
                'needs_update'
            );
            $setup->getConnection()->addIndex(
                $itemsTable,
                $setup->getConnection()->getIndexName(
                    $itemsTable,
                    'updated_at',
                    'updated_at'
                ),
                'updated_at'
            );
        }
    }

    /**
     * Convert Serialzed Data fields to Json for Magento 2.2
     * Using Object Manager for backwards compatability.
     *
     * @param ModuleDataSetupInterface $setup
     */
    public function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        $magentoVersion = $this->productMetadata->getVersion();
        if (version_compare($magentoVersion, '2.2.0', '>=')) {
            $fieldDataConverter = $this->objectManager
                ->create(\Magento\Framework\DB\FieldDataConverterFactory::class)
                ->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);

            $queryModifier = $this->objectManager
                ->create(\Magento\Framework\DB\Select\QueryModifierFactory::class)
                ->create(
                    'in',
                    [
                        'values' => [
                            'path' => [
                                'magmodules_channable/advanced/extra_fields',
                                'magmodules_channable/advanced/delivery_time',
                                'magmodules_channable/filter/filters_data'
                            ]
                        ]
                    ]
                );

            $fieldDataConverter->convert(
                $setup->getConnection(),
                $setup->getTable('core_config_data'),
                'config_id',
                'value',
                $queryModifier
            );
        }
    }
}
