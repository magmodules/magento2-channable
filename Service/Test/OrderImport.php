<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Feed test class
 */
class OrderImport
{

    /**
     * Test type
     */
    const TYPE = 'order_import';

    /**
     * Test description
     */
    const TEST = 'Check if order import is enabled';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Order import is enabled.';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Order Import: Not Enabled';

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
     * OrderImport constructor.
     *
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE,
        ];

        if ($this->configProvider->isOrderEnabled()) {
            $result +=
                [
                    'result_msg'=> self::SUCCESS_MSG,
                    'result_code' => 'success'
                ];
        } else {
            $result +=
                [
                    'result_msg' => self::FAILED_MSG,
                    'result_code' => 'failed',
                    'support_link' => self::SUPPORT_URL
                ];
        }

        return $result;
    }
}
