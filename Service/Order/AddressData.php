<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order;

use Magmodules\Channable\Model\Config;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Directory\Model\RegionFactory;

class AddressData
{

    /**
     * @var Config
     */
    private $config;

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
     * AddressData constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param Config $config
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressFactory,
        Config $config,
        RegionFactory $regionFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->config = $config;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param      $type
     * @param      $order
     * @param      $storeId
     * @param null $customerId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($type, $order, $storeId, $customerId = null)
    {
        if ($type == 'billing') {
            $address = $order['billing'];
        } else {
            $address = $order['shipping'];
        }

        $telephone = '000';
        if (!empty($order['customer']['phone'])) {
            $telephone = $order['customer']['phone'];
        }
        if (!empty($order['customer']['mobile'])) {
            $telephone = $order['customer']['mobile'];
        }

        $email = $this->cleanEmail($address['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $this->cleanEmail($order['customer']['email']);
        }

        $addressData = [
            'customer_id' => $customerId,
            'company'     => $this->config->importCompanyName($storeId) ? $address['company'] : null,
            'firstname'   => $address['first_name'],
            'middlename'  => $address['middle_name'],
            'lastname'    => $address['last_name'],
            'street'      => $this->getStreet($address, $storeId),
            'city'        => $address['city'],
            'country_id'  => $address['country_code'],
            'region' => !empty($address['state_code'])
                ? $this->getRegionId($address['state_code'], $address['country_code'])
                : null,
            'postcode'    => $address['zip_code'],
            'telephone'   => $telephone,
            'vat_id'      => !empty($address['vat_id']) ? $address['vat_id'] : null,
            'email'       => $email
        ];

        if ($this->config->importCustomer($storeId)) {
            $this->saveAddress($addressData, $customerId, $type);
        }

        return $addressData;
    }

    /**
     * @param $address
     * @param $storeId
     *
     * @return array
     */
    private function getStreet($address, $storeId)
    {
        $seperateHousenumber = $this->config->getSeperateHousenumber($storeId);
        $numberOfStreetLines = $this->config->getCustomerStreetLines($storeId);

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

        return $street;
    }

    /**
     * @param $addressData
     * @param $customerId
     * @param $type
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function saveAddress($addressData, $customerId, $type)
    {
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address = $this->addressFactory->create();
        $address->setCustomerId($customerId)
            ->setCompany($addressData['company'])
            ->setFirstname($addressData['firstname'])
            ->setMiddlename($addressData['middlename'])
            ->setLastname($addressData['lastname'])
            ->setStreet($addressData['street'])
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

    /**
     * @param $email
     *
     * @return mixed
     */
    private function cleanEmail($email)
    {
        return str_replace([':'], '', $email);
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
}