<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Helper script for E2E tests: sets Magento config values and flushes cache.
 * Deployed to pub/opt/ during CI setup.
 *
 * Usage: POST /opt/config-setter.php
 * Body: {"token": "<admin_token>", "configs": [{"path": "...", "value": "..."}]}
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['token']) || empty($input['configs'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or configs']);
    exit;
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Validate admin token
/** @var \Magento\Integration\Model\Oauth\TokenFactory $tokenFactory */
$tokenFactory = $objectManager->get(\Magento\Integration\Model\Oauth\TokenFactory::class);
$token = $tokenFactory->create()->loadByToken($input['token']);

if (!$token->getId() || !$token->getAdminId()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid admin token']);
    exit;
}

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

/** @var \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList */
$cacheTypeList = $objectManager->get(\Magento\Framework\App\Cache\TypeListInterface::class);

$results = [];
foreach ($input['configs'] as $config) {
    $path = $config['path'] ?? '';
    $value = $config['value'] ?? '';
    $scope = $config['scope'] ?? 'default';
    $scopeId = $config['scopeId'] ?? 0;

    if (!$path) {
        continue;
    }

    $configWriter->save($path, $value, $scope, (int)$scopeId);
    $results[] = "{$path} = {$value}";
}

// Flush config cache
$cacheTypeList->cleanType('config');

echo json_encode(['success' => true, 'configs_set' => $results]);
