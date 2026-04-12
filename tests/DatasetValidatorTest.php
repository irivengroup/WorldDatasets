<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;

use Iriven\WorldDatasets\Domain\Country;
use Iriven\WorldDatasets\Domain\DatasetValidator;
use Iriven\WorldDatasets\Exception\DatasetValidationException;
use PHPUnit\Framework\TestCase;

final class DatasetValidatorTest extends TestCase
{
    use CountryFactoryTrait;

    public function testValidDatasetProducesValidReport(): void
    {
        $validator = new DatasetValidator();
        $report = $validator->validate($this->makeCountries(), false);

        self::assertTrue($report->isValid());
        self::assertSame([], $report->duplicates());
        self::assertSame([], $report->invalidCodes());
    }

    public function testInvalidCodesAreReported(): void
    {
        $validator = new DatasetValidator();
        $countries = [
            $this->makeCountry('F1', 'FR1', '25', 'Brokenland'),
        ];

        $report = $validator->validate($countries, false);

        self::assertFalse($report->isValid());
        self::assertCount(3, $report->invalidCodes());
    }

    public function testDuplicatesAreReported(): void
    {
        $validator = new DatasetValidator();
        $countries = [
            $this->makeCountry('FR', 'FRA', '250', 'France'),
            $this->makeCountry('FR', 'USA', '840', 'Duplicate'),
        ];

        $report = $validator->validate($countries, false);

        self::assertFalse($report->isValid());
        self::assertNotSame([], $report->duplicates());
    }

    public function testWarningsAreReported(): void
    {
        $validator = new DatasetValidator();
        $country = Country::fromDatabaseRow([
            'alpha2' => 'FR',
            'alpha3' => 'FRA',
            'numeric_code' => '250',
            'country_name' => 'France',
            'capital' => 'Paris',
            'tld' => '.fr',
            'region_alpha_code' => 'EU',
            'region_num_code' => '150',
            'region_name' => 'Europe',
            'sub_region_code' => '',
            'sub_region_name' => '',
            'language' => 'fr',
            'currency_code' => 'EUR',
            'currency_name' => '',
            'postal_code_pattern' => '',
            'phone_code' => '33',
            'intl_dialing_prefix' => '00',
            'natl_dialing_prefix' => '0',
            'subscriber_phone_pattern' => '(',
        ]);

        $report = $validator->validate([$country], false);

        self::assertNotSame([], $report->warnings());
    }

    public function testStrictModeThrowsOnInvalidDataset(): void
    {
        $validator = new DatasetValidator();

        $this->expectException(DatasetValidationException::class);
        $validator->validate([$this->makeCountry('F1', 'FRA', '250', 'Brokenland')], true);
    }
}
