<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Order\Quote;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Create quote (guest or customer)
 */
class Create
{

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var CustomerHandler
     */
    private $customerHandler;

    /**
     * @var AddressHandler
     */
    private $addressHandler;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * QuoteCreation constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param CustomerHandler $customerHandler
     * @param AddressHandler $addressHandler
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        CustomerHandler $customerHandler,
        AddressHandler $addressHandler,
        CartRepositoryInterface $cartRepository
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->customerHandler = $customerHandler;
        $this->addressHandler = $addressHandler;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Create Quote and append customer and adress data
     *
     * @param array $orderData
     * @param StoreInterface $store
     *
     * @return Quote
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     * @throws Exception
     */
    public function createCustomerQuote(array $orderData, StoreInterface $store): Quote
    {
        $quote = $this->quoteFactory->create();
        $quote->setStoreId((int)$store->getId());
        $quote->setCurrency();

        $quote = $this->customerHandler->assignCustomer($quote, $orderData);
        $quote = $this->addressHandler->addAddressData($quote, $orderData);

        $quote->setInventoryProcessed(false);

        if (strtolower($orderData['channel_name']) == 'cdiscount') {
            $quote->setTransactionFee($orderData['price']['transaction_fee']);
        }

        // Make sure the cart is registered by the repository
        $this->cartRepository->save($quote);
        $quote = $this->cartRepository->get($quote->getId());

        return $quote;
    }
}
