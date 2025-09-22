<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Quote;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Service class to get formatted addresses by Channable Address Array
 */
class AddressHandler
{
    // Strip any characters not explicitly allowed for each field
    private const PATTERN_NAME      = '/[^\p{L}\p{M},\-_\.\'’`&\s\d]/u';
    private const PATTERN_CITY      = '/[^\p{L}\p{M}\d\s\-_\'’\.,&\(\)]/u';
    private const PATTERN_STREET    = '/[^\p{L}\p{M}"\[\],\-\.\'’`&\s\d]/u';
    private const PATTERN_TELEPHONE = '/[^0-9\+\-\(\)\s]/u';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressFactory,
        RegionFactory $regionFactory,
        ConfigProvider $configProvider
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * Add Billing and Shipping address data to Quote
     *
     * @param Quote $quote
     * @param array $orderData
     *
     * @return Quote
     * @throws LocalizedException
     */
    public function addAddressData(Quote $quote, array $orderData): Quote
    {
        $quote->getBillingAddress()->addData(
            $this->getAddressData('billing', $orderData, $quote)
        );

        $quote->getShippingAddress()->addData(
            $this->getAddressData('shipping', $orderData, $quote)
        );
        if (isset($orderData['shipping']['pickup_point_name']) && $orderData['shipping']['pickup_point_name']) {
            $extensionAttributes = $quote->getShippingAddress()->getExtensionAttributes();
            $extensionAttributes->setChannablePickupLocation($orderData['shipping']['pickup_point_name']);
            $quote->getShippingAddress()->setExtensionAttributes($extensionAttributes);
        }

        return $quote;
    }

    /**
     * Format address data
     *
     * @param string $type
     * @param array $orderData
     * @param Quote $quote
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAddressData(string $type, array $orderData, Quote $quote): array
    {
        $storeId = $quote->getStoreId();
        $customerId = $quote->getCustomerId();

        $address = $orderData[$type === 'billing' ? 'billing' : 'shipping'];
        $telephone = $orderData['customer']['mobile'] ?? $orderData['customer']['phone'] ?? '000';
        $company = $this->getCompany($address['company'], (int)$storeId);

        $email = $this->cleanEmail($address['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $this->cleanEmail($orderData['customer']['email'] ?? '');
        }

        $addressData = [
            'customer_id' => $customerId,
            'company' => $this->sanitize($company, self::PATTERN_NAME, 255),
            'firstname' => $this->sanitize($address['first_name'], self::PATTERN_NAME, 255) ?? '-',
            'middlename' => $this->sanitize($address['middle_name'], self::PATTERN_NAME, 255),
            'lastname' => $this->sanitize($address['last_name'], self::PATTERN_NAME, 255) ?? '-',
            'street' => $this->getStreet($address, (int)$storeId),
            'city' => $this->sanitize($address['city'], self::PATTERN_CITY, 100),
            'country_id' => $address['country_code'],
            'region' => !empty($address['state_code'])
                ? $this->getRegionId($address['state_code'], $address['country_code'])
                : null,
            'postcode' => $address['zip_code'],
            'telephone' => $this->sanitize($telephone, self::PATTERN_TELEPHONE, 20) ?? '000',
            'vat_id' => $this->getVatId($type, $orderData, $storeId),
            'email' => $email
        ];

        if ($this->configProvider->createCustomerOnImport((int)$storeId)) {
            $this->saveAddress($addressData, $customerId, $type);
        }

        return $addressData;
    }

    /**
     * Sanitize a string by stripping disallowed characters using a negated character-class pattern,
     * normalizing whitespace, and enforcing an optional maximum length.
     *
     * @param null|string|int $value  The raw input value
     * @param string          $pattern A negated character-class regex (e.g. '/[^0-9\\+\\-\\(\\)\\s]/u')
     * @param int|null        $maxLength Optional hard limit on string length (applied after cleanup)
     *
     * @return string|null Sanitized string or null if empty
     */
    private function sanitize(null|string|int $value, string $pattern, ?int $maxLength = null): ?string
    {
        $value = (string) $value;

        // Remove all disallowed characters using the provided negated pattern
        $cleaned = preg_replace($pattern, '', $value) ?? '';

        // Collapse and trim whitespace
        $cleaned = trim(preg_replace('/\s+/u', ' ', $cleaned) ?? '');

        // Enforce max length if specified
        if ($maxLength !== null) {
            $cleaned = mb_substr($cleaned, 0, $maxLength);
        }

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Channable only sets VAT ID on billing address
     * In some cases we also need this on shipping address (due to OSS/MOSS)
     *
     * @param string $type
     * @param array $orderData
     * @param int $storeId
     * @return string|null
     */
    private function getVatId(string $type, array $orderData, int $storeId): ?string
    {
        if (!$this->configProvider->isBusinessOrderEnabled($storeId)) {
            return null;
        }

        $vatId = null;

        // Attempt to retrieve VAT ID from billing data:
        // Channable may supply this value under 'vat_number' or 'vat_id'.
        // We prefer 'vat_number' first, then fallback to 'vat_id'.
        foreach (['vat_number', 'vat_id'] as $vatKey) {
            if (isset($orderData['billing'][$vatKey]) && trim($orderData['billing'][$vatKey]) !== '') {
                $vatId = $orderData['billing'][$vatKey];
                break;
            }
        }

        if ($type == 'billing' || !$vatId) {
            return $vatId;
        }

        if (empty($orderData['customer']['business_order']) || !$this->configProvider->importCompanyName($storeId)) {
            return null;
        }

        return $orderData['billing']['company'] == $orderData['shipping']['company']
            ? $vatId
            : null;
    }

    /**
     * Removed unwanted characters from email.
     * Some Marketplaces add ":" to email what can cause import to fail.
     *
     * @param string $email
     *
     * @return string
     */
    private function cleanEmail(string $email): string
    {
        return str_replace([':'], '', $email);
    }

    /**
     * Get customer company
     *
     * @param string|null $company
     * @param int $storeId
     * @return string|null
     */
    private function getCompany(?string $company, int $storeId): ?string
    {
        $company = $this->configProvider->importCompanyName((int)$storeId) ? $company : null;
        if (!$company && $this->configProvider->isCompanyRequired($storeId)) {
            $company = '-';
        }
        return $company;
    }

    /**
     * Format address lines based on 'separate house-number' and on the number of street lines there are available.
     * This number is configurable via 'customer/address/street_lines'.
     *
     * @param array $address
     * @param int $storeId
     *
     * @return string
     */
    public function getStreet(array $address, int $storeId): string
    {
        $seperateHousenumber = $this->configProvider->seperateHousenumber((int)$storeId);
        $numberOfStreetLines = $this->configProvider->getCustomerStreetLines((int)$storeId);

        if ($seperateHousenumber || empty($address['address_line_1'])) {
            $street[] = $this->sanitize($address['street'], self::PATTERN_STREET);
            $street[] = $this->sanitize($address['house_number'], self::PATTERN_STREET);
            $street[] = $this->sanitize($address['house_number_ext'], self::PATTERN_STREET);
        } else {
            $street[] = $this->sanitize($address['address_line_1'], self::PATTERN_STREET);
            $street[] = $this->sanitize($address['address_line_2'], self::PATTERN_STREET);
            $street[] = null;
        }

        if ($numberOfStreetLines == 1) {
            $street = [trim(implode(' ', $street))];
        }

        if ($numberOfStreetLines == 2) {
            $street = [$street[0], trim($street[1] . ' ' . $street[2])];
        }

        return trim(implode("\n", $street));
    }

    /**
     * @param string $code
     * @param string $countryId
     * @return mixed
     */
    private function getRegionId(string $code, string $countryId)
    {
        $region = $this->regionFactory->create();
        return $region->loadByCode($code, $countryId)->getId();
    }

    /**
     * Save customer address
     *
     * @param array $addressData
     * @param int $customerId
     * @param string $type
     *
     * @throws LocalizedException
     */
    private function saveAddress(array $addressData, int $customerId, string $type): void
    {
        $address = $this->addressFactory->create();
        $address->setCustomerId($customerId)
            ->setCompany($addressData['company'])
            ->setFirstname($addressData['firstname'])
            ->setMiddlename($addressData['middlename'])
            ->setLastname($addressData['lastname'])
            ->setStreet(explode("\n", (string)$addressData['street']))
            ->setCity($addressData['city'])
            ->setCountryId($addressData['country_id'])
            ->setRegionId($addressData['region'])
            ->setPostcode($addressData['postcode'])
            ->setVatId($addressData['vat_id'])
            ->setTelephone($addressData['telephone']);

        if ($type == 'billing') {
            $address->setIsDefaultBilling('1');
        } else {
            $address->setIsDefaultShipping('1');
        }

        $this->addressRepository->save($address);
    }
}
