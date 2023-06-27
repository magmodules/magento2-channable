<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Cron;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Model\Item as ItemModel;

class ItemUpdate
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ItemModel
     */
    private $itemModel;

    /**
     * ItemUpdate constructor.
     *
     * @param ItemModel $itemModel
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     */
    public function __construct(
        ItemModel $itemModel,
        ConfigProvider $configProvider,
        LogRepository $logRepository
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->itemModel = $itemModel;
    }

    /**
     * Execute: ItemUpdate Cron
     */
    public function execute()
    {
        try {
            if ($this->configProvider->isItemCronEnabled()) {
                $this->itemModel->updateAll();
            }
        } catch (\Exception $e) {
            $this->logRepository->addErrorLog('Cron ItemUpdate', $e->getMessage());
        }
    }
}
