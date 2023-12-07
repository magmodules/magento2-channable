<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

use Exception;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

/**
 * Extension version version test class
 */
class ExtensionVersion
{

    /**
     * Test type
     */
    const TYPE = 'extension_version';

    /**
     * Test description
     */
    const TEST = 'Check if current version is the latest version';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'You are using the latest version of the module: v%s.';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Version %s is available, current version %s.';

    /**
     * Expected result
     */
    const EXPECTED = [-1, 0];

    /**
     * Link to get support
     */
    const SUPPORT_LINK = 'https://www.magmodules.eu/help/magento2/update-extension.html';


    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var JsonSerializer
     */
    private $json;
    /**
     * @var File
     */
    private $file;
    /**
     * @var LogRepository
     */
    private $logRepository;

    /**
     * ExtensionVersion constructor.
     *
     * @param ConfigRepository $configRepository
     * @param LogRepository $logRepository
     * @param JsonSerializer $json
     * @param File $file
     */
    public function __construct(
        ConfigRepository $configRepository,
        LogRepository $logRepository,
        JsonSerializer $json,
        File $file
    ) {
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->json = $json;
        $this->file = $file;
    }

    /**
     * @return array
     */
    public function execute(): array
    {
        $result = [
            'type' => self::TYPE,
            'test' => self::TEST,
            'visible' => self::VISIBLE
        ];
        $extensionVersion = preg_replace('/^v/', '', $this->configRepository->getExtensionVersion());
        try {
            $data = $this->file->fileGetContents(
                sprintf('https://version.magmodules.eu/%s.json', ConfigRepository::EXTENSION_CODE)
            );
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('Extension version test', $e->getMessage());
            $result +=
                [
                    'result_msg' => sprintf(self::SUCCESS_MSG, $extensionVersion),
                    'result_code' => 'success',
                ];
            return $result;
        }
        $data = $this->json->unserialize($data);
        $versions = array_keys($data);
        $latest = preg_replace('/^v/', '', reset($versions));

        if (in_array(version_compare($latest, $extensionVersion), self::EXPECTED)) {
            $result +=
                [
                    'result_msg' => sprintf(self::SUCCESS_MSG, $extensionVersion),
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = sprintf(
                self::FAILED_MSG,
                'v' . $latest,
                'v' . $extensionVersion
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
