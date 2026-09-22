<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Test\Unit\Service\Order\Quote;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Directory\Model\RegionFactory;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Service\Order\Quote\AddressHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Street sanitizing is the only thing standing between a Channable payload and the stored address,
 * so every character it drops silently changes where a parcel is delivered.
 */
class AddressHandlerTest extends TestCase
{
    private const STORE_ID = 1;

    /**
     * Punctuation that carries meaning in a house number must survive: dropping the separator in
     * "55/3" does not just lose a character, it names a different house.
     */
    #[DataProvider('preservedPunctuationProvider')]
    public function testPreservesPunctuationThatCarriesAddressMeaning(
        string $houseNumber,
        string $expectedStreet
    ): void {
        $handler = $this->createHandler(separateHousenumber: true, streetLines: 1);

        $street = $handler->getStreet(
            $this->payload(street: 'Examplestreet', houseNumber: $houseNumber),
            self::STORE_ID
        );

        self::assertSame($expectedStreet, $street);
    }

    public static function preservedPunctuationProvider(): array
    {
        return [
            'slash separator (AT, PL, CZ, IT)' => ['55/3', 'Examplestreet 55/3'],
            'hash unit number'                 => ['#12', 'Examplestreet #12'],
            'parenthetical addition'           => ['12 (achter)', 'Examplestreet 12 (achter)'],
            'colon box number'                 => ['12 bus: 4', 'Examplestreet 12 bus: 4'],
            'plain number stays plain'         => ['31', 'Examplestreet 31'],
        ];
    }

    /**
     * Widening the allowlist must not turn it into an open door: everything outside it is still
     * stripped, which is what keeps the stored address free of markup and control punctuation.
     */
    #[DataProvider('strippedCharactersProvider')]
    public function testStillStripsCharactersOutsideTheAllowlist(string $street, string $expected): void
    {
        $handler = $this->createHandler(separateHousenumber: true, streetLines: 1);

        $result = $handler->getStreet($this->payload(street: $street, houseNumber: '31'), self::STORE_ID);

        self::assertSame($expected, $result);
    }

    public static function strippedCharactersProvider(): array
    {
        return [
            'angle brackets' => ['Example<script>street', 'Examplescriptstreet 31'],
            'semicolon'      => ['Example;street', 'Examplestreet 31'],
            'pipe'           => ['Example|street', 'Examplestreet 31'],
            'braces'         => ['Example{street}', 'Examplestreet 31'],
            'asterisk'       => ['Example*street', 'Examplestreet 31'],
        ];
    }

    /**
     * The separate-house-number path splits the number away from the street, so the separator has
     * to survive there as well - "/ 3" collapsing to "3" produced the same wrong house number.
     */
    public function testKeepsSeparatorInHouseNumberExtension(): void
    {
        $handler = $this->createHandler(separateHousenumber: true, streetLines: 1);

        $street = $handler->getStreet(
            $this->payload(street: 'Examplestreet', houseNumber: '55', houseNumberExt: '/ 3'),
            self::STORE_ID
        );

        self::assertSame('Examplestreet 55 / 3', $street);
    }

    /**
     * customer/address/street_lines decides how the three parts are folded together; the separator
     * must survive each of those foldings, not just the single-line one.
     */
    #[DataProvider('streetLineProvider')]
    public function testPreservesSeparatorAcrossStreetLineConfigurations(
        int $streetLines,
        string $expectedStreet
    ): void {
        $handler = $this->createHandler(separateHousenumber: true, streetLines: $streetLines);

        $street = $handler->getStreet(
            $this->payload(street: 'Examplestreet', houseNumber: '55/3', houseNumberExt: 'A'),
            self::STORE_ID
        );

        self::assertSame($expectedStreet, $street);
    }

    public static function streetLineProvider(): array
    {
        return [
            'one line'    => [1, 'Examplestreet 55/3 A'],
            'two lines'   => [2, "Examplestreet\n55/3 A"],
            'three lines' => [3, "Examplestreet\n55/3\nA"],
        ];
    }

    /**
     * With separate house numbers switched off the address arrives as pre-composed lines, which is
     * a different code path through the same pattern.
     */
    public function testPreservesSeparatorInCombinedAddressLines(): void
    {
        $handler = $this->createHandler(separateHousenumber: false, streetLines: 2);

        $street = $handler->getStreet(
            [
                'street'           => '',
                'house_number'     => '',
                'house_number_ext' => '',
                'address_line_1'   => 'Examplestreet 55/3',
                'address_line_2'   => 'Building #2 (rear)',
            ],
            self::STORE_ID
        );

        self::assertSame("Examplestreet 55/3\nBuilding #2 (rear)", $street);
    }

    private function payload(
        string $street,
        string $houseNumber,
        string $houseNumberExt = ''
    ): array {
        return [
            'street'           => $street,
            'house_number'     => $houseNumber,
            'house_number_ext' => $houseNumberExt,
            'address_line_1'   => '',
            'address_line_2'   => '',
        ];
    }

    private function createHandler(bool $separateHousenumber, int $streetLines): AddressHandler
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('seperateHousenumber')->willReturn($separateHousenumber);
        $configProvider->method('getCustomerStreetLines')->willReturn($streetLines);

        return new AddressHandler(
            $this->createMock(AddressRepositoryInterface::class),
            $this->createMock(AddressInterfaceFactory::class),
            $this->createMock(RegionFactory::class),
            $configProvider
        );
    }
}
