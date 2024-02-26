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
    private const PATTERN_NAME = '/(?:[\p{L}\p{M}\,\-\_\.\'’`\s\d]){1,255}+/u';

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

    /**
     * AddressHandler constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionFactory $regionFactory
     * @param ConfigProvider $configProvider
     */
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

        if ($type == 'billing') {
            $address = $orderData['billing'];
        } else {
            $address = $orderData['shipping'];
        }

        $telephone = '000';
        if (!empty($orderData['customer']['phone'])) {
            $telephone = $orderData['customer']['phone'];
        }
        if (!empty($orderData['customer']['mobile'])) {
            $telephone = $orderData['customer']['mobile'];
        }

        $email = $this->cleanEmail($address['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $this->cleanEmail($orderData['customer']['email']);
        }

        $addressData = [
            'customer_id' => $customerId,
            'company' => $this->getCompany($address['company'], (int)$storeId),
            'firstname' => $this->validateName($address['first_name'], 'first_name'),
            'middlename' => $this->validateName($address['middle_name'], 'middle_name'),
            'lastname' => $this->validateName($address['last_name'], 'last_name'),
            'street' => $this->getStreet($address, (int)$storeId),
            'city' => $address['city'],
            'country_id' => $address['country_code'],
            'region' => !empty($address['state_code'])
                ? $this->getRegionId($address['state_code'], $address['country_code'])
                : null,
            'postcode' => $address['zip_code'],
            'telephone' => $telephone,
            'vat_id' => !empty($address['vat_id']) ? $address['vat_id'] : null,
            'email' => $email
        ];
        if (isset($address['vat_number']) && $this->configProvider->isBusinessOrderEnabled()) {
            $addressData['vat_id'] = $address['vat_number'];
        }

        if ($this->configProvider->createCustomerOnImport((int)$storeId)) {
            $this->saveAddress($addressData, $customerId, $type);
        }

        return $addressData;
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
     * @param string|null $nameValue
     * @param string $type
     * @return string|null
     */
    private function validateName(?string $nameValue, string $type): ?string
    {
        if (preg_match_all(self::PATTERN_NAME, (string)$nameValue, $matches)) {
            return implode($matches[0]);
        }

        return $type != 'middle_name' ? '-' : null;
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
            $street[] = $address['street'];
            $street[] = $address['house_number'];
            $street[] = $address['house_number_ext'];
        } else {
            $street[] = $address['address_line_1'];
            $street[] = $address['address_line_2'];
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
