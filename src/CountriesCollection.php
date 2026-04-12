<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Contract\Arrayable;
use Iriven\WorldDatasets\Exporter\CsvExporter;
use Iriven\WorldDatasets\Exporter\JsonExporter;

final class CountriesCollection implements Arrayable, \JsonSerializable
{
    /** @var array<string, string>|null */
    private ?array $cachedList = null;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cachedExportArray = null;
    /** @var array<int, array<int, string>>|null */
    private ?array $cachedStorageArray = null;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cachedApiArray = null;
    /** @var array<int, string>|null */
    private ?array $cachedCodes = null;
    /** @var array<string, string>|null */
    private ?array $cachedNames = null;
    private ?WorldDatasetsStats $cachedStats = null;
    /** @var array<string, array<string, string>>|null */
    private ?array $cachedGroupByRegion = null;
    /** @var array<string, array<string, string>>|null */
    private ?array $cachedGroupByCurrency = null;

    /**
     * @param array<int, Country> $countries
     */
    public function __construct(
        private readonly array $countries,
        private CountryCodeFormat $format = CountryCodeFormat::ALPHA2,
    ) {
    }

    public function alpha2(): self { return new self($this->countries, CountryCodeFormat::ALPHA2); }
    public function alpha3(): self { return new self($this->countries, CountryCodeFormat::ALPHA3); }
    public function numeric(): self { return new self($this->countries, CountryCodeFormat::NUMERIC); }

    public function inRegion(string $name): self
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($this->countries as $country) {
            if (mb_strtolower($country->region()->name()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function inSubRegion(string $name): self
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($this->countries as $country) {
            if (mb_strtolower($country->region()->subRegion()->name()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function withCurrency(string $code): self
    {
        $needle = strtoupper(trim($code));
        $result = [];
        foreach ($this->countries as $country) {
            if (strtoupper($country->currency()->code()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function withPhoneCode(string $code): self
    {
        $normalizer = new PhoneCodeNormalizer();
        $needle = $normalizer->normalize($code);
        $result = [];
        foreach ($this->countries as $country) {
            if ($normalizer->normalize($country->phone()->code()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function withTld(string $tld): self
    {
        $normalizer = new TldNormalizer();
        $needle = $normalizer->normalize($tld);
        $result = [];
        foreach ($this->countries as $country) {
            if ($normalizer->normalize($country->tld()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function named(string $name): self
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($this->countries as $country) {
            if (mb_strtolower($country->name()) === $needle) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function matching(string $term): self
    {
        $needle = mb_strtolower(trim($term));
        $result = [];
        foreach ($this->countries as $country) {
            if (
                str_contains(mb_strtolower($country->name()), $needle)
                || str_contains(mb_strtolower($country->alpha2()), $needle)
                || str_contains(mb_strtolower($country->alpha3()), $needle)
                || str_contains(mb_strtolower($country->numeric()), $needle)
            ) {
                $result[] = $country;
            }
        }
        return new self($result, $this->format);
    }

    public function sortByName(): self
    {
        $countries = $this->countries;
        usort($countries, static fn(Country $a, Country $b): int => strcmp($a->name(), $b->name()));
        return new self($countries, $this->format);
    }

    public function sortByCode(): self
    {
        $countries = $this->countries;
        $format = $this->format;
        usort($countries, static function (Country $a, Country $b) use ($format): int {
            $left = match ($format) {
                CountryCodeFormat::ALPHA2 => $a->alpha2(),
                CountryCodeFormat::ALPHA3 => $a->alpha3(),
                CountryCodeFormat::NUMERIC => $a->numeric(),
            };
            $right = match ($format) {
                CountryCodeFormat::ALPHA2 => $b->alpha2(),
                CountryCodeFormat::ALPHA3 => $b->alpha3(),
                CountryCodeFormat::NUMERIC => $b->numeric(),
            };
            return strcmp($left, $right);
        });
        return new self($countries, $this->format);
    }

    public function sortByNumeric(): self
    {
        $countries = $this->countries;
        usort($countries, static fn(Country $a, Country $b): int => strcmp($a->numeric(), $b->numeric()));
        return new self($countries, $this->format);
    }

    public function paginate(int $offset, int $limit): self
    {
        return new self(array_slice($this->countries, max(0, $offset), max(0, $limit)), $this->format);
    }

    public function first(): ?Country { return $this->countries[0] ?? null; }
    public function last(): ?Country { return $this->countries === [] ? null : $this->countries[array_key_last($this->countries)]; }
    /** @return array<int, Country> */
    public function values(): array { return array_values($this->countries); }
    /** @return array<string, string> */
    public function names(): array { return $this->cachedNames ??= $this->list(); }
    /** @return array<int, string> */
    public function codes(): array
    {
        if ($this->cachedCodes === null) {
            $this->cachedCodes = array_values(array_keys($this->list()));
        }

        /** @var array<int, string> $codes */
        $codes = $this->cachedCodes;

        return array_values($codes);
    }
    public function count(): int { return count($this->countries); }
    public function isEmpty(): bool { return $this->countries === []; }
    public function isNotEmpty(): bool { return !$this->isEmpty(); }

    public function contains(string $code): bool
    {
        $code = trim($code);
        foreach ($this->countries as $country) {
            if ($country->alpha2() === strtoupper($code) || $country->alpha3() === strtoupper($code) || $country->numeric() === $code) {
                return true;
            }
        }
        return false;
    }

    public function containsCountry(callable|Country|string $value): bool
    {
        if ($value instanceof Country) {
            return $this->contains($value->alpha2());
        }

        if (is_string($value)) {
            return $this->contains($value);
        }

        foreach ($this->countries as $country) {
            if ($value($country) === true) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, self> */
    public function chunk(int $size): array
    {
        return array_values(array_map(
            fn(array $chunk): self => new self($chunk, $this->format),
            array_chunk($this->countries, max(1, $size))
        ));
    }

    public function stats(): WorldDatasetsStats
    {
        if ($this->cachedStats !== null) {
            return $this->cachedStats;
        }

        $regions = [];
        $currencies = [];
        foreach ($this->countries as $country) {
            if ($country->region()->name() !== '') {
                $regions[$country->region()->name()] = true;
            }
            if ($country->currency()->code() !== '') {
                $currencies[$country->currency()->code()] = true;
            }
        }

        return $this->cachedStats = new WorldDatasetsStats(count($this->countries), count($regions), count($currencies));
    }

    /** @return array<string, array<string, string>> */
    public function groupByRegion(): array
    {
        if ($this->cachedGroupByRegion !== null) {
            return $this->cachedGroupByRegion;
        }

        $result = [];
        foreach ($this->countries as $country) {
            $result[$country->region()->name()][$country->alpha2()] = $country->name();
        }
        ksort($result);

        return $this->cachedGroupByRegion = $result;
    }

    /** @return array<string, array<string, string>> */
    public function groupByCurrency(): array
    {
        if ($this->cachedGroupByCurrency !== null) {
            return $this->cachedGroupByCurrency;
        }

        $result = [];
        foreach ($this->countries as $country) {
            $result[$country->currency()->code()][$country->alpha2()] = $country->name();
        }
        ksort($result);

        return $this->cachedGroupByCurrency = $result;
    }

    /** @return array<int, string> */
    public function pluckNames(): array
    {
        return array_values(array_map(static fn(Country $country): string => $country->name(), $this->countries));
    }

    /** @return array<int, string> */
    public function pluckCodes(): array
    {
        return array_values(array_map(function (Country $country): string {
            return match ($this->format) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
        }, $this->countries));
    }

    /** @return array<mixed> */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->countries);
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->countries, $callback)), $this->format);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->countries, $callback, $initial);
    }

    /** @return array<string, string> */
    public function list(): array
    {
        if ($this->cachedList !== null) {
            return $this->cachedList;
        }

        $result = [];
        foreach ($this->countries as $country) {
            $key = match ($this->format) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
            $result[$key] = $country->name();
        }
        asort($result);

        return $this->cachedList = $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function exportArray(): array
    {
        if ($this->cachedExportArray !== null) {
            return array_values($this->cachedExportArray);
        }

        $transformer = new CountryArrayTransformer();
        $result = [];
        foreach ($this->countries as $country) {
            $result[] = $transformer->toApiArray($country);
        }

        $this->cachedExportArray = array_values($result);

        /** @var array<int, array<string, mixed>> $exportArray */
        $exportArray = $this->cachedExportArray;

        return $exportArray;
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return (new JsonExporter())->export($this->exportArray(), $flags);
    }

    public function toCsv(): string
    {
        return (new CsvExporter())->export($this->exportArray());
    }

    public function exportJsonFile(string $path): void
    {
        (new JsonExporter())->exportFile($path, $this->exportArray());
    }

    public function exportCsvFile(string $path): void
    {
        (new CsvExporter())->exportFile($path, $this->exportArray());
    }

    /** @return array<int, array<int, string>> */
    public function toStorageArray(): array
    {
        if ($this->cachedStorageArray !== null) {
            return array_values($this->cachedStorageArray);
        }

        $transformer = new CountryArrayTransformer();
        $result = [];
        foreach ($this->countries as $country) {
            $result[] = $transformer->toStorageArray($country);
        }

        $this->cachedStorageArray = array_values($result);

        /** @var array<int, array<int, string>> $storageArray */
        $storageArray = $this->cachedStorageArray;

        return $storageArray;
    }

    /** @return array<int, array<string, mixed>> */
    public function toApiArray(): array
    {
        if ($this->cachedApiArray === null) {
            $this->cachedApiArray = $this->exportArray();
        }

        /** @var array<int, array<string, mixed>> $apiArray */
        $apiArray = $this->cachedApiArray;

        return array_values($apiArray);
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array { return $this->exportArray(); }
    /** @return array<int, array<string, mixed>> */
    public function jsonSerialize(): array { return $this->exportArray(); }
}
