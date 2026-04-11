<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\CountryRepositoryInterface;
use RuntimeException;

final class JsonCountryRepository implements CountryRepositoryInterface
{
    private ArrayCountryRepository $inner;

    public function __construct(string $filePath)
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('JSON file not found: %s', $filePath));
        }

        $content = file_get_contents($filePath);
        $decoded = json_decode((string) $content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON dataset.');
        }

        $countries = [];
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $countries[] = Country::fromDatabaseRow($row);
            }
        }

        $this->inner = new ArrayCountryRepository($countries);
    }

    public function count(): int { return $this->inner->count(); }
    public function findAll(): array { return $this->inner->findAll(); }
    public function findOneByAlpha2(string $alpha2): ?Country { return $this->inner->findOneByAlpha2($alpha2); }
    public function findOneByAlpha3(string $alpha3): ?Country { return $this->inner->findOneByAlpha3($alpha3); }
    public function findOneByNumeric(string $numeric): ?Country { return $this->inner->findOneByNumeric($numeric); }
    public function findOneByName(string $name): ?Country { return $this->inner->findOneByName($name); }
    public function findByName(string $name): array { return $this->inner->findByName($name); }
    public function search(string $term): array { return $this->inner->search($term); }
    public function findByCurrencyCode(string $currencyCode): array { return $this->inner->findByCurrencyCode($currencyCode); }
    public function findByRegion(string $region): array { return $this->inner->findByRegion($region); }
    public function findByPhoneCode(string $phoneCode): array { return $this->inner->findByPhoneCode($phoneCode); }
    public function findByTld(string $tld): array { return $this->inner->findByTld($tld); }
    public function getAllCurrenciesCodeAndName(): array { return $this->inner->getAllCurrenciesCodeAndName(); }
    public function getAllRegionsCodeAndName(): array { return $this->inner->getAllRegionsCodeAndName(); }
    public function getAllCountriesGroupedByRegions(): array { return $this->inner->getAllCountriesGroupedByRegions(); }
    public function getAllCountriesGroupedByCurrencies(): array { return $this->inner->getAllCountriesGroupedByCurrencies(); }
}
