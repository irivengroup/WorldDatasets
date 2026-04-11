<?php

declare(strict_types=1);

namespace Iriven\Infrastructure\Persistence;

use Iriven\Contract\CountryRepositoryInterface;
use Iriven\Country;
use Iriven\Exception\RepositoryException;
use Iriven\Infrastructure\Cache\CacheInterface;
use Iriven\CountryCodeNormalizer;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

final class SqliteCountryRepository implements CountryRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CountryCodeNormalizer $normalizer = null,
    ) {
        $this->configurePdo($this->pdo);
    }

    public static function fromSqliteFile(
        string $sqliteFilePath,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?CountryCodeNormalizer $normalizer = null,
    ): self {
        if ($sqliteFilePath === '' || !is_file($sqliteFilePath)) {
            throw new RepositoryException(sprintf('SQLite file not found: %s', $sqliteFilePath));
        }

        try {
            $pdo = new PDO('sqlite:' . $sqliteFilePath);
        } catch (PDOException $e) {
            throw new RepositoryException('Unable to open SQLite database.', 0, $e);
        }

        return new self($pdo, $cache, $logger, $normalizer);
    }

    public function count(): int
    {
        return (int) $this->remember('countries.count', function (): int {
            try {
                $stmt = $this->pdo->query('SELECT COUNT(*) FROM countries');
                return (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                $this->logger?->error('Failed to count countries.', ['exception' => $e]);
                throw new RepositoryException('Failed to count countries.', 0, $e);
            }
        });
    }

    public function findAll(): array
    {
        /** @var list<Country> */
        return $this->remember('countries.all', function (): array {
            return $this->fetchCountries(
                'SELECT
                    alpha2,
                    alpha3,
                    numeric_code,
                    country_name,
                    capital,
                    tld,
                    region_alpha_code,
                    region_num_code,
                    region_name,
                    sub_region_code,
                    sub_region_name,
                    language,
                    currency_code,
                    currency_name,
                    postal_code_pattern,
                    phone_code,
                    intl_dialing_prefix,
                    natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 ORDER BY country_name ASC'
            );
        });
    }

    public function findOneByAlpha2(string $alpha2): ?Country
    {
        return $this->findOneByColumn('alpha2', $this->normalizer()->normalizeAlpha($alpha2));
    }

    public function findOneByAlpha3(string $alpha3): ?Country
    {
        return $this->findOneByColumn('alpha3', $this->normalizer()->normalizeAlpha($alpha3));
    }

    public function findOneByNumeric(string $numeric): ?Country
    {
        return $this->findOneByColumn('numeric_code', $this->normalizer()->normalizeNumeric($numeric));
    }

    public function findOneByName(string $name): ?Country
    {
        $results = $this->findByName($name);
        return $results[0] ?? null;
    }

    public function findByName(string $name): array
    {
        $normalized = trim($name);
        $cacheKey = 'countries.find_by_name.' . strtolower($normalized);

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 WHERE LOWER(country_name) = LOWER(:name)
                 ORDER BY country_name ASC',
                [':name' => $normalized]
            );
        });
    }

    public function search(string $term): array
    {
        $normalized = '%' . strtolower(trim($term)) . '%';
        $cacheKey = 'countries.search.' . md5($normalized);

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 WHERE LOWER(country_name) LIKE :term
                    OR LOWER(alpha2) LIKE :term
                    OR LOWER(alpha3) LIKE :term
                    OR LOWER(numeric_code) LIKE :term
                    OR LOWER(currency_code) LIKE :term
                    OR LOWER(region_name) LIKE :term
                    OR LOWER(tld) LIKE :term
                 ORDER BY country_name ASC',
                [':term' => $normalized]
            );
        });
    }

    public function findByCurrencyCode(string $currencyCode): array
    {
        $value = strtoupper(trim($currencyCode));
        return $this->fetchByExactColumn('currency_code', $value);
    }

    public function findByRegion(string $region): array
    {
        $value = trim($region);
        return $this->fetchByExactColumn('region_name', $value, false);
    }

    public function findByPhoneCode(string $phoneCode): array
    {
        $value = trim($phoneCode);
        return $this->fetchByExactColumn('phone_code', $value, false);
    }

    public function findByTld(string $tld): array
    {
        $value = $this->normalizer()->normalizeTld($tld);
        return $this->fetchByExactColumn('tld', $value, false);
    }

    public function getAllCurrenciesCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember('countries.currencies', function (): array {
            return $this->queryMap(
                'SELECT currency_code, currency_name
                 FROM countries
                 WHERE currency_code IS NOT NULL
                   AND TRIM(currency_code) <> ""
                 GROUP BY currency_code, currency_name
                 ORDER BY currency_name ASC',
                'currency_code',
                'currency_name'
            );
        });
    }

    public function getAllRegionsCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember('countries.regions', function (): array {
            return $this->queryMap(
                'SELECT region_num_code, region_name
                 FROM countries
                 WHERE region_num_code IS NOT NULL
                   AND TRIM(region_num_code) <> ""
                 GROUP BY region_num_code, region_name
                 ORDER BY region_name ASC',
                'region_num_code',
                'region_name'
            );
        });
    }

    public function getAllCountriesGroupedByRegions(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember('countries.grouped_by_regions', function (): array {
            return $this->queryGrouped(
                'SELECT region_name, alpha2, country_name
                 FROM countries
                 WHERE region_name IS NOT NULL
                   AND TRIM(region_name) <> ""
                 ORDER BY region_name ASC, country_name ASC',
                'region_name',
                'alpha2',
                'country_name'
            );
        });
    }

    public function getAllCountriesGroupedByCurrencies(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember('countries.grouped_by_currencies', function (): array {
            return $this->queryGrouped(
                'SELECT currency_code, alpha2, country_name
                 FROM countries
                 WHERE currency_code IS NOT NULL
                   AND TRIM(currency_code) <> ""
                 ORDER BY currency_code ASC, country_name ASC',
                'currency_code',
                'alpha2',
                'country_name'
            );
        });
    }

    private function fetchByExactColumn(string $column, string $value, bool $normalizeAlpha = true): array
    {
        $allowedColumns = ['currency_code', 'region_name', 'phone_code', 'tld'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported filter column: %s', $column));
        }

        $cacheKey = sprintf('countries.filter.%s.%s', $column, strtolower($value));

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($column, $value, $normalizeAlpha): array {
            $sqlValue = $normalizeAlpha ? strtoupper($value) : $value;
            return $this->fetchCountriesPrepared(
                sprintf(
                    'SELECT
                        alpha2, alpha3, numeric_code, country_name, capital, tld,
                        region_alpha_code, region_num_code, region_name,
                        sub_region_code, sub_region_name, language,
                        currency_code, currency_name, postal_code_pattern,
                        phone_code, intl_dialing_prefix, natl_dialing_prefix,
                        subscriber_phone_pattern
                     FROM countries
                     WHERE %s = :value
                     ORDER BY country_name ASC',
                    $column
                ),
                [':value' => $sqlValue]
            );
        });
    }

    private function findOneByColumn(string $column, string $value): ?Country
    {
        $allowedColumns = ['alpha2', 'alpha3', 'numeric_code'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported lookup column: %s', $column));
        }

        $cacheKey = sprintf('countries.lookup.%s.%s', $column, $value);

        /** @var ?Country */
        return $this->remember($cacheKey, function () use ($column, $value): ?Country {
            try {
                $stmt = $this->pdo->prepare(
                    sprintf(
                        'SELECT
                            alpha2, alpha3, numeric_code, country_name, capital, tld,
                            region_alpha_code, region_num_code, region_name,
                            sub_region_code, sub_region_name, language,
                            currency_code, currency_name, postal_code_pattern,
                            phone_code, intl_dialing_prefix, natl_dialing_prefix,
                            subscriber_phone_pattern
                         FROM countries
                         WHERE %s = :value
                         LIMIT 1',
                        $column
                    )
                );

                $stmt->bindValue(':value', $value);
                $stmt->execute();

                $row = $stmt->fetch();
                return $row === false ? null : Country::fromDatabaseRow($row);
            } catch (PDOException $e) {
                $this->logger?->error('Failed to lookup country.', [
                    'column' => $column,
                    'value' => $value,
                    'exception' => $e,
                ]);

                throw new RepositoryException('Failed to lookup country.', 0, $e);
            }
        });
    }

    /** @return list<Country> */
    private function fetchCountries(string $sql): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();
            return array_map(static fn(array $row): Country => Country::fromDatabaseRow($row), $rows);
        } catch (PDOException $e) {
            $this->logger?->error('Failed to fetch countries.', ['exception' => $e, 'sql' => $sql]);
            throw new RepositoryException('Failed to fetch countries.', 0, $e);
        }
    }

    /** @return list<Country> */
    private function fetchCountriesPrepared(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue((string) $key, $value);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();

            return array_map(static fn(array $row): Country => Country::fromDatabaseRow($row), $rows);
        } catch (PDOException $e) {
            $this->logger?->error('Failed to fetch countries with prepared query.', [
                'exception' => $e,
                'sql' => $sql,
                'params' => $params,
            ]);
            throw new RepositoryException('Failed to fetch countries.', 0, $e);
        }
    }

    /** @return array<string, string> */
    private function queryMap(string $sql, string $key, string $value): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[(string) $row[$key]] = (string) $row[$value];
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to execute map query.', ['exception' => $e]);
            throw new RepositoryException('Failed to execute map query.', 0, $e);
        }
    }

    /** @return array<string, array<string, string>> */
    private function queryGrouped(string $sql, string $groupKey, string $itemKey, string $itemValue): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[(string) $row[$groupKey]][(string) $row[$itemKey]] = (string) $row[$itemValue];
            }

            ksort($result);
            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to execute grouped query.', ['exception' => $e]);
            throw new RepositoryException('Failed to execute grouped query.', 0, $e);
        }
    }

    private function configurePdo(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    private function normalizer(): CountryCodeNormalizer
    {
        return $this->normalizer ?? new CountryCodeNormalizer();
    }

    private function remember(string $cacheKey, callable $resolver): mixed
    {
        if ($this->cache?->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $value = $resolver();
        $this->cache?->set($cacheKey, $value);

        return $value;
    }
}
