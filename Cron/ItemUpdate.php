<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Cron;

use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;

class ItemUpdate
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
     * Execute: ItemUpdate Cron
     */
    public function execute()
    {
        try {
            $cronEnabled = $this->itemHelper->isCronEnabled();
            if ($cronEnabled) {
                $this->itemModel->updateAll();
            }
        } catch (\Exception $e) {
            $this->itemHelper->addTolog('Cron ItemUpdate', $e->getMessage());
        }

        return $this;
    }
}
