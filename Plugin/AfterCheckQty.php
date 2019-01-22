<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Plugin;

class AfterCheckQty
{

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * StockStateProvider constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockState $subject
     * @param                                            $result
     *
     * @return mixed
     */
    public function afterCheckQty(\Magento\CatalogInventory\Model\StockState $subject, $result)
    {
        if ($this->registry->registry('channable_skip_qty_check')) {
            return true;
        }

        return $result;
    }
}
