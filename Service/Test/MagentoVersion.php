<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Magento version test class
 */
class MagentoVersion
{

    /**
     * Test type
     */
    const TYPE = 'magento_version';

    /**
     * Test description
     */
    const TEST = 'Check if current Magento version is supported for this module version';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'Magento %s is supported by this version.';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Minimum required Magento 2 version is %s, current version is %s.';

    /**
     * Link to get support
     */
    const SUPPORT_LINK = 'https://www.magmodules.eu/help/magento2/minimum-magento-version.html';

    /**
     * Expected result
     */
    const EXPECTED = '2.3.3';

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Repository constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
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
        $magentoVersion = $this->configRepository->getMagentoVersion();
        if (version_compare(self::EXPECTED, $magentoVersion) <= 0) {
            $result +=
                [
                    'result_msg' => sprintf(self::SUCCESS_MSG, $magentoVersion),
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = sprintf(
                self::FAILED_MSG,
                self::EXPECTED,
                $magentoVersion
            );
            $result +=
                [
                    'result_code' => 'failed',
                    'support_link' => self::SUPPORT_LINK
                ];
        }
        return $result;
    }
}
