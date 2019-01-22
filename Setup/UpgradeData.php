<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
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

/**
 * Class UpgradeData
 *
 * @package Magmodules\Channable\Setup
 */
class UpgradeData implements UpgradeDataInterface
{

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
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "0.9.6", "<")) {
            $this->addSalesOrderFields($setup);
        }

        if (version_compare($context->getVersion(), "1.0.5", "<")) {
            $this->convertSerializedDataToJson($setup);
        }

        if (version_compare($context->getVersion(), "1.0.9", "<")) {
            $this->changeConfigPaths();
        }

        if (version_compare($context->getVersion(), "1.0.12", "<")) {
            $this->addSalesOrderGridFields($setup);
        }

        $setup->endSetup();
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

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function addSalesOrderFields(ModuleDataSetupInterface $setup)
    {
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

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function addSalesOrderGridFields(ModuleDataSetupInterface $setup)
    {
        $salesConnection = $setup->getConnection('sales');
        $orderGridTable = $setup->getTable('sales_order_grid');
        $salesConnection->addColumn(
            $orderGridTable,
            'channable_id',
            [
                'type'     => Table::TYPE_INTEGER,
                'default'  => 0,
                'nullable' => false,
                'comment'  => 'Channable: Order ID'
            ]
        );
        $salesConnection->addColumn(
            $orderGridTable,
            'channel_id',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Channable: Channel ID'
            ]
        );
        $salesConnection->addColumn(
            $orderGridTable,
            'channel_name',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Channable: Channel Name'
            ]
        );
    }
}
