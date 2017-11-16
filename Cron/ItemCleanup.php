<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Cron;

use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;

/**
 * Class ItemCleanup
 *
 * @package Magmodules\Channable\Cron
 */
class ItemCleanup
{

    /**
     * @var ItemHelper
     */
    private $itemHelper;
    /**
     * @var ItemModel
     */
    private $itemModel;

    /**
     * ItemUpdate constructor.
     *
     * @param ItemModel  $itemModel
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        ItemModel $itemModel,
        ItemHelper $itemHelper
    ) {
        $this->itemHelper = $itemHelper;
        $this->itemModel = $itemModel;
    }

    /**
     * Execute: Cleanup old entries /items
     */
    public function execute()
    {
        if (!$this->itemHelper->isCronEnabled()) {
            return;
        }

        try {
            $this->itemModel->cleanOldEntries();
        } catch (\Exception $e) {
            $this->itemHelper->addTolog('Cron ItemCleanup', $e->getMessage());
        }
    }
}
