<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionCache
{
    /** @var array<string, string>|null */
    public ?array $list = null;
    /** @var array<int, array<string, mixed>>|null */
    public ?array $exportArray = null;
    /** @var array<int, array<int, string>>|null */
    public ?array $storageArray = null;
    /** @var array<int, array<string, mixed>>|null */
    public ?array $apiArray = null;
    /** @var array<int, string>|null */
    public ?array $codes = null;
    /** @var array<string, string>|null */
    public ?array $names = null;
    public ?WorldDatasetsStats $stats = null;
    /** @var array<string, array<string, string>>|null */
    public ?array $groupByRegion = null;
    /** @var array<string, array<string, string>>|null */
    public ?array $groupByCurrency = null;
}
