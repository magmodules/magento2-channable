<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Creates tax rates and tax rules for cross-border E2E testing.
 * Tax rates: NL 21%, DE 19%, AT 20%, BE 21%, FR 20%
 * Links to: "Taxable Goods" product tax class + "Retail Customer" customer tax class
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);

try {
    $state->setAreaCode('adminhtml');
} catch (\Throwable $e) {
    // Area already set
}

/** @var \Magento\Tax\Api\TaxRateRepositoryInterface $taxRateRepository */
$taxRateRepository = $objectManager->get(\Magento\Tax\Api\TaxRateRepositoryInterface::class);

/** @var \Magento\Tax\Api\TaxRuleRepositoryInterface $taxRuleRepository */
$taxRuleRepository = $objectManager->get(\Magento\Tax\Api\TaxRuleRepositoryInterface::class);

/** @var \Magento\Tax\Api\Data\TaxRateInterfaceFactory $taxRateFactory */
$taxRateFactory = $objectManager->get(\Magento\Tax\Api\Data\TaxRateInterfaceFactory::class);

/** @var \Magento\Tax\Api\Data\TaxRuleInterfaceFactory $taxRuleFactory */
$taxRuleFactory = $objectManager->get(\Magento\Tax\Api\Data\TaxRuleInterfaceFactory::class);

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

// Remove all existing tax rules (sample data creates US-based rules that interfere)
$searchCriteria = $searchCriteriaBuilder->create();
$existingRules = $taxRuleRepository->getList($searchCriteria);
foreach ($existingRules->getItems() as $rule) {
    echo "Removing existing tax rule: {$rule->getCode()} (ID: {$rule->getId()})\n";
    $taxRuleRepository->deleteById($rule->getId());
}

// Remove all existing tax rates
$existingRates = $taxRateRepository->getList($searchCriteriaBuilder->create());
foreach ($existingRates->getItems() as $rate) {
    echo "Removing existing tax rate: {$rate->getCode()} (ID: {$rate->getId()})\n";
    $taxRateRepository->deleteById($rate->getId());
}

// Define tax rates per country
$rates = [
    ['code' => 'NL-21', 'country' => 'NL', 'rate' => 21.0],
    ['code' => 'DE-19', 'country' => 'DE', 'rate' => 19.0],
    ['code' => 'AT-20', 'country' => 'AT', 'rate' => 20.0],
    ['code' => 'BE-21', 'country' => 'BE', 'rate' => 21.0],
    ['code' => 'FR-20', 'country' => 'FR', 'rate' => 20.0],
];

$rateIds = [];

foreach ($rates as $rateData) {
    // Check if rate already exists
    $searchCriteria = $searchCriteriaBuilder
        ->addFilter('code', $rateData['code'])
        ->create();

    $existingRates = $taxRateRepository->getList($searchCriteria);

    if ($existingRates->getTotalCount() > 0) {
        $items = $existingRates->getItems();
        $existing = reset($items);
        $rateIds[] = $existing->getId();
        echo "Tax rate {$rateData['code']} already exists (ID: {$existing->getId()})\n";
        continue;
    }

    /** @var \Magento\Tax\Api\Data\TaxRateInterface $taxRate */
    $taxRate = $taxRateFactory->create();
    $taxRate->setCode($rateData['code']);
    $taxRate->setTaxCountryId($rateData['country']);
    $taxRate->setTaxRegionId(0);
    $taxRate->setTaxPostcode('*');
    $taxRate->setRate($rateData['rate']);
    $taxRate->setZipIsRange(false);

    $savedRate = $taxRateRepository->save($taxRate);
    $rateIds[] = $savedRate->getId();
    echo "Created tax rate {$rateData['code']} (ID: {$savedRate->getId()})\n";
}

// Look up tax class IDs dynamically
/** @var \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository */
$taxClassRepository = $objectManager->get(\Magento\Tax\Api\TaxClassRepositoryInterface::class);

$searchCriteria = $searchCriteriaBuilder->create();
$taxClasses = $taxClassRepository->getList($searchCriteria);

$productTaxClassId = null;
$customerTaxClassId = null;

foreach ($taxClasses->getItems() as $taxClass) {
    echo "Found tax class: {$taxClass->getClassName()} (ID: {$taxClass->getClassId()}, type: {$taxClass->getClassType()})\n";
    if ($taxClass->getClassType() === 'PRODUCT' && $taxClass->getClassName() === 'Taxable Goods') {
        $productTaxClassId = (int)$taxClass->getClassId();
    }
    if ($taxClass->getClassType() === 'CUSTOMER' && $taxClass->getClassName() === 'Retail Customer') {
        $customerTaxClassId = (int)$taxClass->getClassId();
    }
}

if (!$productTaxClassId) {
    echo "ERROR: 'Taxable Goods' product tax class not found!\n";
    exit(1);
}
if (!$customerTaxClassId) {
    echo "ERROR: 'Retail Customer' customer tax class not found!\n";
    exit(1);
}

echo "Using product tax class ID: {$productTaxClassId}, customer tax class ID: {$customerTaxClassId}\n";

// Ensure product ID 1 has the correct tax class
/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

try {
    $product = $productRepository->getById(1);
    $currentTaxClass = $product->getTaxClassId();
    echo "Product ID 1 ({$product->getSku()}) tax class ID: {$currentTaxClass}\n";
    if ((int)$currentTaxClass !== $productTaxClassId) {
        $product->setTaxClassId($productTaxClassId);
        $productRepository->save($product);
        echo "Updated product ID 1 tax class to {$productTaxClassId}\n";
    }
} catch (\Throwable $e) {
    echo "Warning: Could not check product ID 1: {$e->getMessage()}\n";
}

// Create tax rule linking all rates to the correct tax classes
$ruleName = 'E2E Cross-Border Tax Rule';

$searchCriteria = $searchCriteriaBuilder
    ->addFilter('code', $ruleName)
    ->create();

$existingRules = $taxRuleRepository->getList($searchCriteria);

if ($existingRules->getTotalCount() > 0) {
    echo "Tax rule '{$ruleName}' already exists, updating rates...\n";
    $items = $existingRules->getItems();
    $existingRule = reset($items);
    $existingRule->setTaxRateIds($rateIds);
    $existingRule->setProductTaxClassIds([$productTaxClassId]);
    $existingRule->setCustomerTaxClassIds([$customerTaxClassId]);
    $taxRuleRepository->save($existingRule);
    echo "Updated tax rule with rate IDs: " . implode(', ', $rateIds) . "\n";
} else {
    /** @var \Magento\Tax\Api\Data\TaxRuleInterface $taxRule */
    $taxRule = $taxRuleFactory->create();
    $taxRule->setCode($ruleName);
    $taxRule->setPriority(0);
    $taxRule->setPosition(0);
    $taxRule->setCustomerTaxClassIds([$customerTaxClassId]);
    $taxRule->setProductTaxClassIds([$productTaxClassId]);
    $taxRule->setTaxRateIds($rateIds);

    $savedRule = $taxRuleRepository->save($taxRule);
    echo "Created tax rule '{$ruleName}' (ID: {$savedRule->getId()})\n";
}

echo "Tax setup complete.\n";
