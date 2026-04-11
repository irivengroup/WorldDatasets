<?php

declare(strict_types=1);

namespace Iriven;

final class DataSource
{
    public const SQLITE = '.countriesRepository.sqlite';
    public const JSON = '.countriesRepository.json';
    public const CSV = 'countriesRepository.csv';

    private function __construct()
    {
    }
}
