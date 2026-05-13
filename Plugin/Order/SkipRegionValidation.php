<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Address\Validator\Country;

/**
 * Skip region validation for Channable order imports.
 *
 * Channable sends state_code values (e.g. "099" for LV) that don't exist in
 * Magento's directory_country_region table. This plugin filters out regionId
 * validation errors during Channable imports to prevent order creation failures.
 */
class SkipRegionValidation
{
    private CheckoutSession $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Filter out regionId-related validation errors during Channable order imports.
     *
     * @param Country $subject
     * @param array $result
     * @return array
     */
    public function afterValidate(Country $subject, array $result): array
    {
        if (!$this->checkoutSession->getChannableEnabled()) {
            return $result;
        }

        return array_values(array_filter($result, function ($error) {
            $message = $error instanceof \Magento\Framework\Phrase ? $error->render() : (string)$error;
            return stripos($message, 'regionId') === false
                && stripos($message, 'region_id') === false
                && stripos($message, '"region" is required') === false;
        }));
    }
}
