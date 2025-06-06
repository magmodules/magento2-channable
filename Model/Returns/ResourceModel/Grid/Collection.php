<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Returns\ResourceModel\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Zend_Db_Expr;

class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addFilterToMap('store_id', 'main_table.store_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('magento_creditmemo_increment_id', 'main_table.magento_creditmemo_increment_id');
        $this->addFilterToMap('magento_increment_id', 'main_table.magento_increment_id');
        $this->addFilterToMap('order_status', 'sales_order.status');

        $this->getSelect()->joinLeft(
            ['sales_order' => $this->getTable('sales_order')],
            'main_table.magento_order_id = sales_order.entity_id',
            [
                'order_status' => 'sales_order.status'
            ]
        )->joinLeft(
            ['sales_creditmemo' => $this->getTable('sales_creditmemo')],
            'main_table.magento_order_id = sales_creditmemo.order_id',
            [
                'qty_creditmemos' => new Zend_Db_Expr('count(sales_creditmemo.entity_id)'),
            ]
        )->group('main_table.entity_id');
    }
}
