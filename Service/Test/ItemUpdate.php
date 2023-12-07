<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Feed test class
 */
class ItemUpdate
{

    /**
     * Test type
     */
    const TYPE = 'item_update_test';

    /**
     * Test description
     */
    const TEST = 'Check if item updates are enabled';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Enabled Store-view(s): %s';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Item Updates: Not Enabled';

    /**
     * Expected result
     */
    const EXPECTED = true;

    /**
     * Link to get support
     */
    const SUPPORT_URL = 'https://www.magmodules.eu/help/magento2-channable/channable-magento2-selftest-results';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ConfigProvider        $configProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type'    => self::TYPE,
            'test'    => self::TEST,
            'visible' => self::VISIBLE,
        ];

        if ($names = $this->getEnabledStores()) {
            $result += [
                'result_msg'  => sprintf(self::SUCCESS_MSG, implode(', ', $names)),
                'result_code' => 'success'
            ];
        } else {
            $result += [
                'result_msg'  => self::FAILED_MSG,
                'result_code' => 'failed',
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getEnabledStores(): ?array
    {
        $names = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->configProvider->isItemUpdateEnabled((int)$store->getStoreId())) {
                $names[] = sprintf('"%s"', $store->getName());
            }
        }

        return !empty($names) ? $names : null;
    }
}
