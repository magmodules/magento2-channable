<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Unit test bootstrap.
 *
 * Two autoloaders are in play and their order matters:
 *
 * 1. Test/vendor — this module's own PHPUnit. It lives here because Magento's root composer.json
 *    excludes **&#47;Test/** from the generated classmap, which silently mangles PHPUnit's own
 *    class map (PHPUnit ships classes under src/Event/Events/Test/) and leaves the copy in
 *    Magento's vendor unusable.
 * 2. The surrounding Magento installation — supplies Magento\Framework and friends.
 *
 * Magento's autoloader also knows about its own (broken) PHPUnit copy, so after registering it we
 * push the test loader back to the front. Without that, PHPUnit classes get resolved against two
 * different PHPUnit versions in the same process.
 *
 * Magento's root composer.json also excludes **&#47;Test/** from the classmap, so this module's own
 * test classes are never autoloaded from a mounted checkout either — hence the PSR-4 fallback at
 * the bottom.
 */
declare(strict_types=1);

// Test bootstrap, not shipped runtime code: it exits when the environment is unusable and declares
// factory stubs the same way Magento's own code generator does.
// phpcs:disable Magento2.Security.LanguageConstruct.ExitUsage
// phpcs:disable Squiz.PHP.Eval.Discouraged
// phpcs:disable Magento2.Security.InsecureFunction.Found

$moduleDir = dirname(__DIR__);

/** @var \Composer\Autoload\ClassLoader $testLoader */
$testLoader = require __DIR__ . '/vendor/autoload.php';

$magentoAutoload = null;
foreach ([$moduleDir, getcwd()] as $start) {
    $dir = $start;
    while ($dir && $dir !== dirname($dir)) {
        $candidate = $dir . '/vendor/autoload.php';
        if (is_file($candidate) && $candidate !== __DIR__ . '/vendor/autoload.php') {
            $magentoAutoload = $candidate;
            break 2;
        }
        $dir = dirname($dir);
    }
}

if ($magentoAutoload === null) {
    fwrite(STDERR, "Could not locate the surrounding Magento installation's vendor/autoload.php.\n");
    exit(1);
}

require_once $magentoAutoload;

// Re-prepend so this module's PHPUnit wins over the copy in Magento's vendor directory.
$testLoader->unregister();
$testLoader->register(true);

spl_autoload_register(static function (string $class) use ($moduleDir): void {
    $prefix = 'Magmodules\\Channable\\';

    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $moduleDir . '/' . $relative . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }

    // Factories have no source file: Magento generates them into generated/code at runtime, and
    // that directory is not guaranteed to be populated when the unit suite runs (nor does it exist
    // at all in a standalone CI checkout). Synthesize the same shape so they can be mocked.
    //
    // This covers Magento's own factories too — Magento\Customer\Api\Data\AddressInterfaceFactory
    // and friends are generated exactly the same way. The guard is the generator's own rule: a
    // factory only exists for a type that exists, so a typo still fails loudly.
    if (!str_ends_with($class, 'Factory')) {
        return;
    }

    $generatedFor = substr($class, 0, -strlen('Factory'));
    if (!class_exists($generatedFor) && !interface_exists($generatedFor)) {
        return;
    }

    $namespace = substr($class, 0, strrpos($class, '\\'));
    $shortName = substr($class, strrpos($class, '\\') + 1);

    eval(sprintf(
        'namespace %s; class %s { public function create(array $data = []) {} }',
        $namespace,
        $shortName
    ));
});
