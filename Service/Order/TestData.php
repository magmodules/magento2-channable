<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Service\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Math\Random;

class TestData
{

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Random
     */
    private $random;

    /**
     * TestData constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Random                     $random
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Random $random
    ) {
        $this->productRepository = $productRepository;
        $this->random = $random;
    }

    /**
     * @param        $productId
     * @param bool   $lvb
     * @param string $country
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrder($productId, $lvb = false, $country = 'NL')
    {
        $product = $this->productRepository->getById($productId);
        $random = $this->random->getRandomString(5, '0123456789');

        return [
            "channable_id"            => $random,
            "channable_channel_label" => "Channable Test",
            "channel_id"              => "TEST-" . $random,
            "channel_name"            => "Channable",
            "order_status"            => $lvb ? "shipped" : "not_shipped",
            "customer"                => [
                "gender"      => "male",
                "phone"       => "01234567890",
                "mobile"      => "01234567890",
                "email"       => "dontemail@me.net",
                "first_name"  => "Test",
                "middle_name" => "From",
                "last_name"   => "Channable",
                "company"     => "TestCompany"
            ],
            "billing"                 => [
                "first_name"       => "Test",
                "middle_name"      => "From",
                "last_name"        => "Channable",
                "company"          => "Do Not Ship",
                "vat_id"           => 'NL0001',
                "email"            => "dontemail@me.net",
                "address_line_1"   => "Billing Line 1",
                "address_line_2"   => "Billing Line 2",
                "street"           => "Street",
                "house_number"     => 1,
                "house_number_ext" => "",
                "zip_code"         => "1000 AA",
                "city"             => "UTRECHT",
                "country_code"     => $country,
                "state"            => $country == "US" ? "Texas" : "",
                "state_code"       => $country == "US" ? "TX" : "",
            ],
            "shipping"                => [
                "first_name"       => "Test",
                "middle_name"      => "From",
                "last_name"        => "Channable",
                "company"          => "Do Not Ship",
                "email"            => "dontemail@me.net",
                "address_line_1"   => "Billing Line 1",
                "address_line_2"   => "Billing Line 2",
                "street"           => "Street",
                "house_number"     => 1,
                "house_number_ext" => "",
                "zip_code"         => "1000 AA",
                "city"             => "UTRECHT",
                "country_code"     => $country,
                "state"            => $country == "US" ? "Texas" : "",
                "state_code"       => $country == "US" ? "TX" : "",
            ],
            "price"                   => [
                "payment_method"  => "bol",
                "currency"        => "EUR",
                "subtotal"        => ($product->getPrice() * 2),
                "payment"         => 0,
                "shipping"        => 0,
                "total"           => ($product->getPrice() * 2),
                "transaction_fee" => 0,
                "commission"      => 4.59
            ],
            "products"                => [
                [
                    "id"              => $product->getId(),
                    "quantity"        => 2,
                    "price"           => $product->getPrice(),
                    "ean"             => $product->getSku(),
                    "reference_code"  => $product->getSku(),
                    "title"           => $product->getName(),
                    "delivery_period" => "2019-04-17+02=>00",
                    "shipping"        => 0,
                    "commission"      => 4.59
                ]
            ],
            "memo"                    => "Test Order from Channable",
        ];
    }

}