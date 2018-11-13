<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magmodules\Channable\Setup\Tables\ChannableItems;
use Magmodules\Channable\Setup\Tables\ChannableReturns;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class UpgradeSchema
 *
 * @package Magmodules\Channable\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "0.9.7", "<")) {
            $this->createTable($setup, ChannableItems::getData());
        }

        if (version_compare($context->getVersion(), "0.9.9", "<")) {
            $this->addGtinColumnToItemsTable($setup);
        }

        if (version_compare($context->getVersion(), "1.0.11", "<")) {
            $this->addParentIdToColumnToItemsTable($setup);
        }

        if (version_compare($context->getVersion(), "1.0.3", "<")) {
            $this->addMissingIndexesToItemTable($setup);
        }

        if (version_compare($context->getVersion(), "1.4.0", "<")) {
            $this->createTable($setup, ChannableReturns::getData());
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param                      $tableData
     *
     * @throws \Zend_Db_Exception
     */
    public function createTable(SchemaSetupInterface $setup, $tableData)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable($tableData['title']);

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName);
            foreach ($tableData['columns'] as $columnName => $columnData) {
                $table->addColumn($columnName, $columnData['type'], $columnData['length'], $columnData['option']);
            }
            if (!empty($tableData['indexes'])) {
                foreach ($tableData['indexes'] as $sIndex) {
                    $table->addIndex($setup->getIdxName($tableData['title'], $sIndex), $sIndex);
                }
            }
            $table->setComment($tableData['comment']);
            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addGtinColumnToItemsTable(SchemaSetupInterface $setup)
    {
        $itemsTable = $setup->getTable(ChannableItems::TABLE_NAME);
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

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addParentIdToColumnToItemsTable(SchemaSetupInterface $setup)
    {
        $itemsTable = $setup->getTable(ChannableItems::TABLE_NAME);
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

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addMissingIndexesToItemTable(SchemaSetupInterface $setup)
    {
        $itemsTable = $setup->getTable(ChannableItems::TABLE_NAME);
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
}
