<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Product;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CatalogPrice;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory as RuleFactory;

class PriceData
{

    /**
     * @var CatalogPrice
     */
    private $commonPriceModel;
    /**
     * @var RuleFactory
     */
    private $ruleFactory;
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    public function __construct(
        CatalogPrice $commonPriceModel,
        CatalogHelper $catalogHelper,
        RuleFactory $ruleFactory
    ) {
        $this->commonPriceModel = $commonPriceModel;
        $this->ruleFactory = $ruleFactory;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param array $config
     * @param Product $product
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(array $config, Product $product): array
    {
        switch ($product->getTypeId()) {
            case 'configurable':
                /**
                 * Check if config has a final_price (data catalog_product_index_price)
                 * If final_price === null product is not salable (out of stock)
                 */
                if ($product->getData('final_price') === null) {
                    $price = 0;
                    $finalPrice = 0;
                } else {
                    $price = $product->getData('price');
                    $finalPrice = $product->getData('final_price');
                    $specialPrice = $product->getSpecialPrice();
                    $product['min_price'] = $product['min_price'] >= 0 ? $product['min_price'] : null;
                    $product['max_price'] = $product['max_price'] >= 0 ? $product['max_price'] : null;
                }
                break;
            case 'grouped':
                $groupedPriceType = null;
                if (!empty($config['price_config']['grouped_price_type'])) {
                    $groupedPriceType = $config['price_config']['grouped_price_type'];
                }

                $groupedPrices = $this->getGroupedPrices($product, $config);
                $price = $groupedPrices['min_price'];
                $finalPrice = $groupedPrices['min_price'];
                $product['min_price'] = $groupedPrices['min_price'];
                $product['max_price'] = $groupedPrices['max_price'];
                $product['total_price'] = $groupedPrices['total_price'];

                if ($groupedPriceType == 'max') {
                    $price = $groupedPrices['max_price'];
                    $finalPrice = $price;
                }

                if ($groupedPriceType == 'total') {
                    $price = $groupedPrices['total_price'];
                    $finalPrice = $price;
                }

                break;
            case 'bundle':
                $price = $product->getPrice();
                if ((int)$product->getPriceType() === Price::PRICE_TYPE_DYNAMIC) {
                    $product['min_price'] = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getBaseAmount();
                    $product['max_price'] = $product->getPriceInfo()->getPrice('final_price')->getMaximalPrice()->getBaseAmount();
                }
                $finalPrice = $product->getFinalPrice();
                $specialPrice = $product->getSpecialPrice();
                $rulePrice = $this->ruleFactory->create()->getRulePrice(
                    $config['timestamp'],
                    $config['website_id'],
                    '',
                    $product->getId()
                );
                if ($rulePrice !== null && $rulePrice !== false) {
                    $finalPrice = min($finalPrice, $rulePrice);
                }
                break;
            default:
                if ($product->getFinalPrice() !== null) {
                    $price = $product->getPrice();
                    $finalPrice = $product->getFinalPrice();
                    $specialPrice = $product->getSpecialPrice();
                } else {
                    $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getValue();
                    $price = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                    $product['min_price'] = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getBaseAmount();
                    $product['max_price'] = $product->getPriceInfo()->getPrice('final_price')->getMaximalPrice()->getBaseAmount();
                }

                $rulePrice = $this->ruleFactory->create()->getRulePrice(
                    $config['timestamp'],
                    $config['website_id'],
                    '',
                    $product->getId()
                );

                if ($rulePrice !== null && $rulePrice !== false) {
                    $finalPrice = min($finalPrice, $rulePrice);
                }

                break;
        }
        $prices = [];
        $attributes = $config['attributes'];
        $config = $config['price_config'];
        $prices[$config['price']] = $this->processPrice($product, $price, $config);

        if (!empty($config['tax_include_both'])) {
            $prices[$config['price_excl']] = $this->processPrice($product, $price, $config, false);
            $prices[$config['price_incl']] = $this->processPrice($product, $price, $config, true);
        }

        if (isset($finalPrice) && !empty($config['final_price'])) {
            $prices[$config['final_price']] = $this->processPrice($product, $finalPrice, $config);
        }

        if (isset($finalPrice) && ($price > $finalPrice) && !empty($config['sales_price'])) {
            $prices[$config['sales_price']] = $this->processPrice($product, $finalPrice, $config);
            if (!empty($config['tax_include_both'])) {
                $prices[$config['sales_price_excl']] = $this->processPrice($product, $finalPrice, $config, false);
                $prices[$config['sales_price_incl']] = $this->processPrice($product, $finalPrice, $config, true);
            }
        }

        if (isset($specialPrice) && ($specialPrice == $finalPrice) && !empty($config['sales_date_range'])) {
            if ($product->getSpecialFromDate() && $product->getSpecialToDate()) {
                $from = date('Y-m-d', strtotime($product->getSpecialFromDate()));
                $to = date('Y-m-d', strtotime($product->getSpecialToDate()));
                $prices[$config['sales_date_range']] = $from . '/' . $to;
            }
        }

        if ($price <= 0) {
            if (!empty($product['min_price'])) {
                $minPrice = $product['min_price'];
                $prices[$config['price']] = $this->processPrice($product, $minPrice, $config);
                if (!empty($config['tax_include_both'])) {
                    $prices[$config['price_excl']] = $this->processPrice($product, $minPrice, $config, false);
                    $prices[$config['price_incl']] = $this->processPrice($product, $minPrice, $config, true);
                }
            }
        }

        if (!empty($product['min_price']) && !empty($config['min_price'])) {
            if (($finalPrice > 0) && $finalPrice < $product['min_price']) {
                $prices[$config['min_price']] = $this->processPrice($product, $finalPrice, $config);
            } else {
                $prices[$config['min_price']] = $this->processPrice($product, $product['min_price'], $config);
            }
        }

        if (!empty($product['max_price']) && !empty($config['max_price'])) {
            $prices[$config['max_price']] = $this->processPrice($product, $product['max_price'], $config);
        }

        if (!empty($product['total_price']) && !empty($config['total_price'])) {
            $prices[$config['total_price']] = $this->processPrice($product, $product['total_price'], $config);
        }

        if (!empty($config['discount_perc']) && isset($prices[$config['sales_price']])) {
            if ($prices[$config['price']] > 0) {
                $discount = ($prices[$config['sales_price']] - $prices[$config['price']]) / $prices[$config['price']];
                $discount = $discount * -100;
                if ($discount > 0) {
                    $prices[$config['discount_perc']] = round($discount, 1) . '%';
                }
            }
        }

        if ($extraRenderedPriceFields = preg_grep('/^rendered_price__/', array_keys($attributes))) {
            foreach ($extraRenderedPriceFields as $label) {
                $field = $attributes[$label];
                $renderCurrency = $field['actions'][0] ? explode('_', $field['actions'][0])[1] : null;
                if ($renderCurrency !== $config['currency']) {
                    $newConfig = $config;
                    $newConfig['currency'] = $renderCurrency;
                    $newConfig['exchange_rate'] = $config['exchange_rate_' . $renderCurrency] ?? 1;
                    switch ($field['price_source']) {
                        case 'price':
                            $prices[$field['label']] = $this->processPrice($product, $price, $newConfig);
                            break;
                        case 'min_price':
                            $price = $minPrice ?? $price;
                            $prices[$field['label']] = $this->processPrice($product, $price, $newConfig);
                            break;
                        case 'max_price':
                            $price = $maxPrice ?? $price;
                            $prices[$field['label']] = $this->processPrice($product, $price, $newConfig);
                            break;
                    }
                } else {
                    $prices[$field['label']] = $prices[$field['price_source']] ?? null;
                }
            }
        }

        return $prices;
    }

    /**
     * @param Product $product
     * @param array $config
     *
     * @return array|null
     */
    public function getGroupedPrices(Product $product, array $config): ?array
    {
        $subProducts = $product->getTypeInstance()->getAssociatedProducts($product);

        $minPrice = null;
        $maxPrice = null;
        $totalPrice = null;

        foreach ($subProducts as $subProduct) {
            $subProduct->setWebsiteId($config['website_id']);
            if ($subProduct->isSalable()) {
                $price = $this->commonPriceModel->getCatalogPrice($subProduct);
                if ($price < $minPrice || $minPrice === null) {
                    $minPrice = $this->commonPriceModel->getCatalogPrice($subProduct);
                    $product->setTaxClassId($subProduct->getTaxClassId());
                }
                if ($price > $maxPrice || $maxPrice === null) {
                    $maxPrice = $this->commonPriceModel->getCatalogPrice($subProduct);
                    $product->setTaxClassId($subProduct->getTaxClassId());
                }
                if ($subProduct->getQty() > 0) {
                    $totalPrice += $price * $subProduct->getQty();
                } else {
                    $totalPrice += $price;
                }
            }
        }

        return ['min_price' => $minPrice, 'max_price' => $maxPrice, 'total_price' => $totalPrice];
    }

    /**
     * @param Product $product
     * @param $price
     * @param array $config
     * @param bool|null $includingTax
     *
     * @return string
     */
    public function processPrice(Product $product, $price, array $config, ?bool $includingTax = null): string
    {
        if (!empty($config['exchange_rate'])) {
            $price = $price * $config['exchange_rate'];
        }

        if ($includingTax !== null) {
            return $this->formatPrice(
                $this->catalogHelper->getTaxPrice($product, $price, $includingTax),
                $config
            );
        }

        if (isset($config['incl_vat'])) {
            $price = $this->catalogHelper->getTaxPrice($product, $price, ['incl_vat']);
        }

        return $this->formatPrice($price, $config);
    }

    /**
     * Format a price based on the provided configuration.
     *
     * @param float|string $price The price value to be formatted.
     * @param array $config Configuration options for formatting.
     * @return string The formatted price.
     */
    public function formatPrice($price, array $config): string
    {
        $decimalPoint = $config['decimal_point'] ?? '.';
        $currency = $config['currency'] ?? '';
        $useCurrency = !empty($config['use_currency']);

        // Ensure the price is converted to a float.
        $formattedPrice = number_format(
            floatval(str_replace(',', '.', (string)$price)),
            2,
            $decimalPoint,
            ''
        );

        // Append currency if configured.
        if ($useCurrency && $formattedPrice >= 0) {
            $formattedPrice .= ' ' . $currency;
        }

        return $formattedPrice;
    }

    /**
     * Process tier pricing for a product.
     *
     * @param Product $product The product instance.
     * @param array $config Configuration for price formatting.
     * @return array|null An array of reformatted tier prices or null if none exist.
     */
    public function processTierPrice(Product $product, array $config): ?array
    {
        if (!$tierPrices = $product->getData('tier_price')) {
            return null;
        }

        $reformattedTierPrices = [];
        foreach ($tierPrices as $priceTier) {
            $price = !empty($priceTier['percentage_value'])
                ? $product->getPrice() * (1 - ($priceTier['percentage_value'] / 100)) // Subtract discount
                : $priceTier['value'];

            $reformattedTierPrices[] = [
                'price_id' => $priceTier['value_id'] ?? null,
                'website_id' => $priceTier['website_id'] ?? null,
                'all_groups' => $priceTier['all_groups'] ?? 0,
                'cust_group' => $priceTier['customer_group_id'] ?? null,
                'qty' => $priceTier['qty'] ?? 0,
                'price' => $this->formatPrice($price, $config)
            ];
        }

        return $reformattedTierPrices;
    }
}
