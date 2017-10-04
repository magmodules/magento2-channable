<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Cron;

use Magmodules\Channable\Model\Item as ItemModel;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Psr\Log\LoggerInterface as Logger;

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
     * @var Logger
     */
    private $logger;

    /**
     * ItemUpdate constructor.
     *
     * @param ItemModel  $itemModel
     * @param ItemHelper $itemHelper
     * @param Logger     $logger
     */
    public function __construct(
        ItemModel $itemModel,
        ItemHelper $itemHelper,
        Logger $logger
    ) {
        $this->itemHelper = $itemHelper;
        $this->itemModel = $itemModel;
        $this->logger = $logger;
    }

    /**
     * Execute: Item Update Cron
     */
    public function execute()
    {
        try {
            $cronEnabled = $this->itemHelper->isCronEnabled();
            if ($cronEnabled) {
                $this->itemModel->updateAll();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }
}
