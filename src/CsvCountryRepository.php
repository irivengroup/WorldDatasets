<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Contract\CountryRepositoryInterface;
use RuntimeException;

final class CsvCountryRepository implements CountryRepositoryInterface
{
    private ArrayCountryRepository $inner;

    public function __construct(string $filePath)
    {
        $handle = $this->openFile($filePath);

        try {
            $headers = $this->readHeaders($handle);
            $countries = $this->readCountries($handle, $headers);
        } finally {
            fclose($handle);
        }

        $this->inner = new ArrayCountryRepository($countries);
    }

    public function count(): int
    {
        return $this->inner->count();
    }

    /** @return array<int, Country> */
    public function findAll(): array
    {
        return $this->inner->findAll();
    }

    public function findOneByAlpha2(string $alpha2): ?Country
    {
        return $this->inner->findOneByAlpha2($alpha2);
    }

    public function findOneByAlpha3(string $alpha3): ?Country
    {
        return $this->inner->findOneByAlpha3($alpha3);
    }

    public function findOneByNumeric(string $numeric): ?Country
    {
        return $this->inner->findOneByNumeric($numeric);
    }

    public function findOneByName(string $name): ?Country
    {
        return $this->inner->findOneByName($name);
    }

    /** @return array<int, Country> */
    public function findByName(string $name): array
    {
        return $this->inner->findByName($name);
    }

    /** @return array<int, Country> */
    public function search(string $term): array
    {
        return $this->inner->search($term);
    }

    /** @return array<int, Country> */
    public function findByCurrencyCode(string $currencyCode): array
    {
        return $this->inner->findByCurrencyCode($currencyCode);
    }

    /** @return array<int, Country> */
    public function findByRegion(string $region): array
    {
        return $this->inner->findByRegion($region);
    }

    /** @return array<int, Country> */
    public function findByPhoneCode(string $phoneCode): array
    {
        return $this->inner->findByPhoneCode($phoneCode);
    }

    /** @return array<int, Country> */
    public function findByTld(string $tld): array
    {
        return $this->inner->findByTld($tld);
    }

    /** @return array<string, string> */
    public function getAllCurrenciesCodeAndName(): array
    {
        return $this->inner->getAllCurrenciesCodeAndName();
    }

    /** @return array<string, string> */
    public function getAllRegionsCodeAndName(): array
    {
        return $this->inner->getAllRegionsCodeAndName();
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByRegions(): array
    {
        return $this->inner->getAllCountriesGroupedByRegions();
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByCurrencies(): array
    {
        return $this->inner->getAllCountriesGroupedByCurrencies();
    }

    /**
     * @return resource
     */
    private function openFile(string $filePath)
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('CSV file not found: %s', $filePath));
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open CSV file: %s', $filePath));
        }

        return $handle;
    }

    /**
     * @param resource $handle
     * @return array<int, string>
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle);

        if ($headers === false || $headers === []) {
            throw new RuntimeException('Invalid CSV header.');
        }

        return array_values($headers);
    }

    /**
     * @param resource $handle
     * @param array<int, string> $headers
     * @return array<int, Country>
     */
    private function readCountries($handle, array $headers): array
    {
        $countries = [];
        $headerCount = count($headers);

        while (($row = fgetcsv($handle)) !== false) {
            $assoc = array_combine($headers, array_pad($row, $headerCount, ''));

            /** @var array<string, mixed> $assoc */
            $assoc = $assoc;

            $countries[] = Country::fromDatabaseRow($assoc);
        }

        return $countries;
    }
}
