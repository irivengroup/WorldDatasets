<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Infrastructure\Persistence;

use Iriven\WorldDatasets\Domain\CountryInfo;

final class SqliteCountryHydrator
{
    /**
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): CountryInfo
    {
        return CountryInfo::fromDatabaseRow($row);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, CountryInfo>
     */
    public function hydrateMany(array $rows): array
    {
        $countries = [];

        foreach ($rows as $row) {
            $countries[] = $this->hydrate($row);
        }

        return $countries;
    }
}
