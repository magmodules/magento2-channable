<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Quote;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Service class to assign customer to quote
 */
class CustomerHandler
{

    private const PATTERN_NAME = '/(?:[\p{L}\p{M}\,\-\_\.\'’`\s\d]){1,255}+/u';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * CustomerHandler constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        ConfigProvider $configProvider
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * Assign customer / Guest to Quote
     *
     * @param Quote $quote
     * @param array $orderData
     *
     * @return Quote
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws LocalizedException
     * @throws InputMismatchException
     */
    public function assignCustomer(Quote $quote, array $orderData)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        $storeId = $quote->getStoreId();
        $email = $this->cleanEmail($orderData['customer']['email']);

        if (!$this->configProvider->createCustomerOnImport((int)$storeId)) {
            $quote->setCustomerEmail($email);
            $quote->setCustomerFirstname($this->validateName($orderData['customer']['first_name'], 'first_name'));
            $quote->setCustomerMiddlename($this->validateName($orderData['customer']['middle_name'], 'middle_name'));
            $quote->setCustomerLastname($this->validateName($orderData['customer']['last_name'], 'last_name'));
            $quote->setCustomerIsGuest(1);
            $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);

            return $quote;
        }

        try {
            $customer = $this->customerRepository->get($email, $websiteId);
        } catch (NoSuchEntityException $exception) {
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->setFirstname($this->validateName($orderData['customer']['first_name'], 'first_name'));
            $customer->setMiddlename($this->validateName($orderData['customer']['middle_name'], 'middle_name'));
            $customer->setLastname($this->validateName($orderData['customer']['last_name'], 'last_name'));
            $customer->setEmail($email);
            $customer->setGroupId($this->configProvider->customerGroupForOrderImport((int)$storeId));
            $this->customerRepository->save($customer);
            $customer = $this->customerRepository->get($email, $websiteId);
        }

        $quote->assignCustomer($customer);
        $quote->setCustomerIsGuest(0);

        return $quote;
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
     * @param string|null $nameValue
     * @param string $type
     * @return string
     */
    private function validateName(?string $nameValue, string $type): string
    {
        if (!$nameValue && ($type != 'middle_name')) {
            return '-';
        }
        preg_match_all(self::PATTERN_NAME, (string)$nameValue, $matches);
        return implode($matches[0]);
    }
}
