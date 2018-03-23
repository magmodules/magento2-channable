<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup;

use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

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
     * @var ValueInterface
     */
    private $configReader;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * UpgradeData constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface   $objectManager
     * @param SalesSetupFactory        $salesSetupFactory
     * @param ValueInterface           $configReader
     * @param WriterInterface          $configWriter
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager,
        SalesSetupFactory $salesSetupFactory,
        ValueInterface $configReader,
        WriterInterface $configWriter
    ) {
        $this->productMetadata = $productMetadata;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->objectManager = $objectManager;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @throws \Zend_Db_Exception
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "0.9.6", "<")) {

            /** @var SalesSetup $salesSetup */
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
                        'Product id'
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

        if (version_compare($context->getVersion(), "1.0.9", "<")) {
            $this->changeConfigPaths();
        }

        if (version_compare($context->getVersion(), "1.0.11", "<")) {
            $itemsTable = $setup->getTable(self::TABLE_NAME_ITEMS);
            if ($setup->getConnection()->isTableExists($itemsTable)) {
                $setup->getConnection()
                    ->addColumn(
                        $itemsTable,
                        'parent_id',
                        [
                            'type'     => Table::TYPE_INTEGER,
                            'default'  => 0,
                            'nullable' => false,
                            'comment'  => 'Parent Id',
                            'after'    => 'id'
                        ]
                    );
            }
        }

        if (version_compare($context->getVersion(), "1.0.12", "<")) {
            $orderGridTable = $setup->getTable('sales_order_grid');
            $setup->getConnection()
                ->addColumn(
                    $orderGridTable,
                    'channable_id',
                    [
                        'type'     => Table::TYPE_INTEGER,
                        'default'  => 0,
                        'nullable' => false,
                        'comment'  => 'Channable: Order ID'
                    ]
                );
            $setup->getConnection()
                ->addColumn(
                    $orderGridTable,
                    'channel_id',
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Channable: Channel ID'
                    ]
                );
            $setup->getConnection()
                ->addColumn(
                    $orderGridTable,
                    'channel_name',
                    [
                        'type'     => Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Channable: Channel Name'
                    ]
                );

            $itemsTable = $setup->getTable(self::TABLE_NAME_ITEMS);
            if ($setup->getConnection()->isTableExists($itemsTable)) {
                if (!$setup->getConnection()->tableColumnExists($itemsTable, 'parent_id')) {
                    $setup->getConnection()
                        ->addColumn(
                            $itemsTable,
                            'parent_id',
                            [
                                'type'     => Table::TYPE_INTEGER,
                                'default'  => 0,
                                'nullable' => false,
                                'comment'  => 'Parent Id',
                                'after'    => 'id'
                            ]
                        );
                }
            }

        }

        $setup->endSetup();
    }

    /**
     * Add Indexes to Items Table.
     *
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

            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $fieldDataConverter = $this->objectManager
                ->create(\Magento\Framework\DB\FieldDataConverterFactory::class)
                ->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);

            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
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

    /**
     * Change config paths for fields due to changes in config options.
     */
    public function changeConfigPaths()
    {
        $collection = $this->configReader->getCollection()
            ->addFieldToFilter("path", "magmodules_channable/advanced/parent_atts");

        foreach ($collection as $config) {
            /** @var \Magento\Framework\App\Config\Value $config */
            $this->configWriter->save(
                "magmodules_channable/types/configurable_parent_atts",
                $config->getValue(),
                $config->getScope(),
                $config->getScopeId()
            );
            $this->configWriter->delete(
                "magmodules_channable/advanced/parent_atts",
                $config->getScope(),
                $config->getScopeId()
            );
        }

        $collection = $this->configReader->getCollection()
            ->addFieldToFilter("path", "magmodules_channable/advanced/relations");

        foreach ($collection as $config) {
            /** @var \Magento\Framework\App\Config\Value $config */
            if ($config->getValue() == 1) {
                $this->configWriter->save(
                    "magmodules_channable/types/configurable",
                    'simple',
                    $config->getScope(),
                    $config->getScopeId()
                );
            }
            $this->configWriter->delete(
                "magmodules_channable/advanced/relations",
                $config->getScope(),
                $config->getScopeId()
            );
        }
    }
}
