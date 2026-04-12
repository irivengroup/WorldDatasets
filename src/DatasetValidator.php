<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Exception\DatasetValidationException;

final class DatasetValidator
{
    /**
     * @param array<int, Country> $worldDatasets
     */
    public function validate(array $worldDatasets, bool $strict = true): DatasetValidationReport
    {
        $indexes = $this->createDuplicateIndexes();
        $duplicates = [];
        $invalid = [];
        $warnings = [];

        foreach ($worldDatasets as $country) {
            $this->collectInvalidIsoCodes($country, $invalid);
            $this->collectDuplicates($country, $indexes, $duplicates);
            $this->collectWarnings($country, $warnings);
        }

        $report = new DatasetValidationReport($duplicates, $invalid, $warnings, $strict);

        $this->assertStrictValidity($report, $strict);

        return $report;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function createDuplicateIndexes(): array
    {
        return [
            'alpha2' => [],
            'alpha3' => [],
            'numeric' => [],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $invalid
     */
    private function collectInvalidIsoCodes(Country $country, array &$invalid): void
    {
        $this->collectInvalidCode(
            field: 'alpha2',
            value: $country->alpha2(),
            countryName: $country->name(),
            pattern: '/^[A-Z]{2}$/',
            invalid: $invalid,
        );

        $this->collectInvalidCode(
            field: 'alpha3',
            value: $country->alpha3(),
            countryName: $country->name(),
            pattern: '/^[A-Z]{3}$/',
            invalid: $invalid,
        );

        $this->collectInvalidCode(
            field: 'numeric',
            value: $country->numeric(),
            countryName: $country->name(),
            pattern: '/^\d{3}$/',
            invalid: $invalid,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $invalid
     */
    private function collectInvalidCode(
        string $field,
        string $value,
        string $countryName,
        string $pattern,
        array &$invalid
    ): void {
        if (preg_match($pattern, $value)) {
            return;
        }

        $invalid[] = [
            'field' => $field,
            'value' => $value,
            'country' => $countryName,
        ];
    }

    /**
     * @param array<string, array<string, string>> $indexes
     * @param array<int, array<string, mixed>> $duplicates
     */
    private function collectDuplicates(Country $country, array &$indexes, array &$duplicates): void
    {
        $this->registerDuplicateValue('alpha2', $country->alpha2(), $country->name(), $indexes['alpha2'], $duplicates);
        $this->registerDuplicateValue('alpha3', $country->alpha3(), $country->name(), $indexes['alpha3'], $duplicates);
        $this->registerDuplicateValue('numeric', $country->numeric(), $country->name(), $indexes['numeric'], $duplicates);
    }

    /**
     * @param array<string, string> $bucket
     * @param array<int, array<string, mixed>> $duplicates
     */
    private function registerDuplicateValue(
        string $field,
        string $value,
        string $countryName,
        array &$bucket,
        array &$duplicates
    ): void {
        if ($value === '') {
            return;
        }

        if (array_key_exists($value, $bucket)) {
            $duplicates[] = [
                'field' => $field,
                'value' => $value,
                'countries' => [$bucket[$value], $countryName],
            ];
            return;
        }

        $bucket[$value] = $countryName;
    }

    /**
     * @param array<int, array<string, mixed>> $warnings
     */
    private function collectWarnings(Country $country, array &$warnings): void
    {
        if ($country->currency()->code() !== '' && $country->currency()->name() === '') {
            $warnings[] = [
                'field' => 'currency_name',
                'country' => $country->name(),
                'message' => 'Currency code present without currency name',
            ];
        }

        if ($country->region()->name() !== '' && $country->region()->subRegion()->name() === '') {
            $warnings[] = [
                'field' => 'sub_region',
                'country' => $country->name(),
                'message' => 'Region present without sub-region',
            ];
        }

        if ($this->hasInvalidPhonePattern($country)) {
            $warnings[] = [
                'field' => 'phone_pattern',
                'country' => $country->name(),
                'message' => 'Potentially invalid phone regex',
            ];
        }
    }

    private function hasInvalidPhonePattern(Country $country): bool
    {
        $pattern = $country->phone()->subscriberPattern();
        if ($pattern === '') {
            return false;
        }

        set_error_handler(static fn() => true);
        $ok = @preg_match('/' . str_replace('/', '\/', $pattern) . '/', '123') !== false;
        restore_error_handler();

        return !$ok;
    }

    private function assertStrictValidity(DatasetValidationReport $report, bool $strict): void
    {
        if (!$strict || $report->isValid()) {
            return;
        }

        throw new DatasetValidationException('Dataset validation failed in strict mode.');
    }
}
