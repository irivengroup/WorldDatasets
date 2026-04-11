<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\Arrayable;
use Iriven\Exporter\CsvExporter;
use Iriven\Exporter\JsonExporter;

final class RegionsCollection implements Arrayable, \JsonSerializable
{
    public function __construct(
        private readonly array $countries,
    ) {
    }

    public function values(): array
    {
        $result = [];
        foreach ($this->countries as $country) {
            $region = $country->region();
            if ($region->numericCode() === '' && $region->name() === '') {
                continue;
            }
            $result[$region->numericCode() . '|' . $region->name()] = $region;
        }
        ksort($result);
        return array_values($result);
    }

    public function list(): array
    {
        $result = [];
        foreach ($this->countries as $country) {
            $region = $country->region();
            if ($region->numericCode() === '') {
                continue;
            }
            $result[$region->numericCode()] = $region->name();
        }
        asort($result);
        return $result;
    }

    public function countries(): array
    {
        $result = [];
        foreach ($this->countries as $country) {
            $region = $country->region();
            if ($region->name() === '') {
                continue;
            }
            $result[$region->name()][$country->alpha2()] = $country->name();
        }
        ksort($result);
        return $result;
    }

    public function exportArray(): array
    {
        return array_map(static fn(RegionInfo $region): array => $region->toArray(), $this->values());
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

    public function toArray(): array { return $this->exportArray(); }
    public function jsonSerialize(): array { return $this->exportArray(); }
}
