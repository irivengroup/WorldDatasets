<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\Arrayable;
use Iriven\Exporter\CsvExporter;
use Iriven\Exporter\JsonExporter;

final class CountriesCollection implements Arrayable, \JsonSerializable
{
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
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => mb_strtolower($country->region()->name()) === $needle)), $this->format);
    }

    public function inSubRegion(string $name): self
    {
        $needle = mb_strtolower(trim($name));
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => mb_strtolower($country->region()->subRegion()->name()) === $needle)), $this->format);
    }

    public function withCurrency(string $code): self
    {
        $needle = strtoupper(trim($code));
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => strtoupper($country->currency()->code()) === $needle)), $this->format);
    }

    public function withPhoneCode(string $code): self
    {
        $normalizer = new PhoneCodeNormalizer();
        $needle = $normalizer->normalize($code);
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => (new PhoneCodeNormalizer())->normalize($country->phone()->code()) === $needle)), $this->format);
    }

    public function withTld(string $tld): self
    {
        $normalizer = new TldNormalizer();
        $needle = $normalizer->normalize($tld);
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => (new TldNormalizer())->normalize($country->tld()) === $needle)), $this->format);
    }

    public function named(string $name): self
    {
        $needle = mb_strtolower(trim($name));
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool => mb_strtolower($country->name()) === $needle)), $this->format);
    }

    public function matching(string $term): self
    {
        $needle = mb_strtolower(trim($term));
        return new self(array_values(array_filter($this->countries, static fn(Country $country): bool =>
            str_contains(mb_strtolower($country->name()), $needle)
            || str_contains(mb_strtolower($country->alpha2()), $needle)
            || str_contains(mb_strtolower($country->alpha3()), $needle)
            || str_contains(mb_strtolower($country->numeric()), $needle)
        )), $this->format);
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
    public function values(): array { return $this->countries; }
    public function names(): array { return $this->list(); }
    public function codes(): array { return array_keys($this->list()); }
    public function count(): int { return count($this->countries); }
    public function isEmpty(): bool { return $this->countries === []; }
    public function isNotEmpty(): bool { return !$this->isEmpty(); }

    public function contains(string $code): bool
    {
        return (new WorldDatasets(new ArrayCountryRepository($this->countries)))->findCountry($code) !== null;
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

    public function chunk(int $size): array
    {
        return array_map(
            fn(array $chunk): self => new self($chunk, $this->format),
            array_chunk($this->countries, max(1, $size))
        );
    }

    public function stats(): CountriesStats
    {
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

        return new CountriesStats(count($this->countries), count($regions), count($currencies));
    }

    public function groupByRegion(): array
    {
        $result = [];
        foreach ($this->countries as $country) {
            $result[$country->region()->name()][$country->alpha2()] = $country->name();
        }
        ksort($result);
        return $result;
    }

    public function groupByCurrency(): array
    {
        $result = [];
        foreach ($this->countries as $country) {
            $result[$country->currency()->code()][$country->alpha2()] = $country->name();
        }
        ksort($result);
        return $result;
    }

    public function pluckNames(): array
    {
        return array_map(static fn(Country $country): string => $country->name(), $this->countries);
    }

    public function pluckCodes(): array
    {
        return array_map(function (Country $country): string {
            return match ($this->format) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
        }, $this->countries);
    }

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

    public function list(): array
    {
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
        return $result;
    }

    public function exportArray(): array
    {
        return array_map(static fn(Country $country): array => $country->toArray(), $this->countries);
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

    public function toStorageArray(): array
    {
        return array_map(static fn(Country $country): array => $country->toIndexedArray(), $this->countries);
    }

    public function toApiArray(): array
    {
        return $this->exportArray();
    }

    public function toArray(): array { return $this->exportArray(); }
    public function jsonSerialize(): array { return $this->exportArray(); }
}
