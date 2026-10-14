<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Test\Unit\Etc;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A plugin declared on a class name that does not exist is not an error Magento reports: the
 * declaration is simply never matched, and the plugin silently never runs. That is how the source
 * deduction plugin sat on "SourceDeductionServicer" for releases without anyone noticing.
 */
class DiConfigTest extends TestCase
{
    /**
     * Magento is a composer requirement and Magmodules is our own code, so both have to resolve.
     * Anything else is an integration with a module a merchant may or may not have installed, and
     * a missing class there says nothing about this repository.
     */
    private const REQUIRED_VENDORS = ['Magmodules\\', 'Magento\\'];

    #[DataProvider('declaredTypeProvider')]
    public function testDeclaredTypeExists(string $file, string $class): void
    {
        $required = false;
        foreach (self::REQUIRED_VENDORS as $prefix) {
            $required = $required || str_starts_with($class, $prefix);
        }

        if (!$required) {
            self::markTestSkipped(sprintf('%s belongs to a module that need not be installed', $class));
        }

        self::assertTrue(
            class_exists($class) || interface_exists($class),
            sprintf('%s declares %s, which does not exist', $file, $class)
        );
    }

    public static function declaredTypeProvider(): array
    {
        $moduleDir = dirname(__DIR__, 3);
        $cases = [];

        foreach (glob($moduleDir . '/etc/{,*/}di.xml', GLOB_BRACE) as $path) {
            $file = substr($path, strlen($moduleDir) + 1);
            $xml = simplexml_load_file($path);

            foreach (['//type/@name', '//plugin/@type', '//preference/@for', '//preference/@type'] as $xpath) {
                foreach ($xml->xpath($xpath) ?: [] as $attribute) {
                    $class = (string)$attribute;
                    $cases[$file . ' ' . $xpath . ' ' . $class] = [$file, $class];
                }
            }
        }

        return $cases;
    }
}
