<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Test;

/**
 * Php extensions test class
 */
class PhpExtensions
{

    /**
     * Test type
     */
    const TYPE = 'php_extensions';

    /**
     * Test description
     */
    const TEST = 'Check if all required PHP extensions are installed';

    /**
     * Visibility
     */
    const VISIBLE = true;

    /**
     * Message on test success
     */
    const SUCCESS_MSG = 'All required extensions are installed.';

    /**
     * Message on test failed
     */
    const FAILED_MSG = 'Required extension(s): %s not found!';

    /**
     * Expected result
     */
    const EXPECTED = 0;

    /**
     * List or required php extensions
     */
    const REQUIRED_PHP_MODULES = ['curl'];

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
        $installedExtension = get_loaded_extensions();
        $extDiff = array_diff(self::REQUIRED_PHP_MODULES, $installedExtension);
        if (count($extDiff) == self::EXPECTED) {
            $result['result_msg'] = self::SUCCESS_MSG;
            $result +=
                [
                    'result_code' => 'success'
                ];
        } else {
            $result['result_msg'] = sprintf(
                self::FAILED_MSG,
                implode(', ', $extDiff)
            );
            $result +=
                [
                    'result_code' => 'failed'
                ];
        }

        return $result;
    }
}
