#!/usr/bin/env php
<?php

declare(strict_types=1);

use Iriven\WorldDatasets\WorldDatasetsFactory;
use Iriven\WorldDatasets\DataSource;
use Iriven\WorldDatasets\DatasetValidator;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @param non-empty-string $path
 */
function write_if_changed(string $path, string $content): void
{
    $current = is_file($path) ? (string) file_get_contents($path) : null;
    if ($current === $content) {
        return;
    }

    file_put_contents($path, $content);
}

/**
 * @param array<int, array<string, string>> $normalizedRecords
 */
function rebuild_sqlite_from_records(string $sqliteFile, array $normalizedRecords): void
{
    if (is_file($sqliteFile)) {
        unlink($sqliteFile);
    }

    $pdo = new PDO('sqlite:' . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE countries (
        alpha2 TEXT PRIMARY KEY,
        alpha3 TEXT NOT NULL UNIQUE,
        numeric_code TEXT NOT NULL UNIQUE,
        country_name TEXT NOT NULL,
        capital TEXT,
        tld TEXT,
        region_alpha_code TEXT,
        region_num_code TEXT,
        region_name TEXT,
        sub_region_code TEXT,
        sub_region_name TEXT,
        language TEXT,
        currency_code TEXT,
        currency_name TEXT,
        postal_code_pattern TEXT,
        phone_code TEXT,
        intl_dialing_prefix TEXT,
        natl_dialing_prefix TEXT,
        subscriber_phone_pattern TEXT
    )');
    $pdo->exec('CREATE INDEX idx_countries_alpha3 ON countries(alpha3)');
    $pdo->exec('CREATE INDEX idx_countries_numeric_code ON countries(numeric_code)');
    $pdo->exec('CREATE INDEX idx_countries_region_name ON countries(region_name)');
    $pdo->exec('CREATE INDEX idx_countries_currency_code ON countries(currency_code)');
    $pdo->exec('CREATE INDEX idx_countries_country_name ON countries(country_name)');
    $pdo->exec('CREATE INDEX idx_countries_region_subregion ON countries(region_name, sub_region_name)');
    $pdo->exec('CREATE INDEX idx_countries_tld ON countries(tld)');
    $pdo->exec('CREATE INDEX idx_countries_phone_code ON countries(phone_code)');
    $pdo->exec('CREATE INDEX idx_countries_currency_country ON countries(currency_code, country_name)');
    $pdo->exec('CREATE INDEX idx_countries_region_country ON countries(region_name, country_name)');

    $insert = $pdo->prepare('INSERT INTO countries (
        alpha2, alpha3, numeric_code, country_name, capital, tld, region_alpha_code, region_num_code,
        region_name, sub_region_code, sub_region_name, language, currency_code, currency_name,
        postal_code_pattern, phone_code, intl_dialing_prefix, natl_dialing_prefix, subscriber_phone_pattern
    ) VALUES (
        :alpha2, :alpha3, :numeric_code, :country_name, :capital, :tld, :region_alpha_code, :region_num_code,
        :region_name, :sub_region_code, :sub_region_name, :language, :currency_code, :currency_name,
        :postal_code_pattern, :phone_code, :intl_dialing_prefix, :natl_dialing_prefix, :subscriber_phone_pattern
    )');

    $pdo->beginTransaction();
    foreach ($normalizedRecords as $record) {
        $insert->execute([
            ':alpha2' => $record['alpha2'],
            ':alpha3' => $record['alpha3'],
            ':numeric_code' => $record['numeric_code'],
            ':country_name' => $record['country_name'],
            ':capital' => $record['capital'],
            ':tld' => $record['tld'],
            ':region_alpha_code' => $record['region_alpha_code'],
            ':region_num_code' => $record['region_num_code'],
            ':region_name' => $record['region_name'],
            ':sub_region_code' => $record['sub_region_code'],
            ':sub_region_name' => $record['sub_region_name'],
            ':language' => $record['language'],
            ':currency_code' => $record['currency_code'],
            ':currency_name' => $record['currency_name'],
            ':postal_code_pattern' => $record['postal_code_pattern'],
            ':phone_code' => $record['phone_code'],
            ':intl_dialing_prefix' => $record['intl_dialing_prefix'],
            ':natl_dialing_prefix' => $record['natl_dialing_prefix'],
            ':subscriber_phone_pattern' => $record['subscriber_phone_pattern'],
        ]);
    }
    $pdo->commit();
}

/**
 * @param array<string, string> $checksums
 */
function canonical_meta_payload(array $checksums, string $builtAt): string
{
    return json_encode([
        'dataset_version' => '2026.04.11',
        'built_at' => $builtAt,
        'checksums' => $checksums,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$targetDir = __DIR__ . '/../src/data';
$sqlitePath = $targetDir . '/' . DataSource::SQLITE;
$jsonPath = $targetDir . '/' . DataSource::JSON;
$csvPath = $targetDir . '/' . DataSource::CSV;
$shaPath = $targetDir . '/.countriesRepository.sha256';
$metaPath = $targetDir . '/.countriesRepository.meta.json';
$validationPath = $targetDir . '/.countriesRepository.validation.json';

$sourceFile = WorldDatasetsFactory::defaultSqlitePath();
$service = WorldDatasetsFactory::make($sourceFile);
$records = $service->countries()->sortByCode()->exportArray();

$headers = [
    'alpha2', 'alpha3', 'numeric_code', 'country_name', 'capital', 'tld',
    'region_alpha_code', 'region_num_code', 'region_name',
    'sub_region_code', 'sub_region_name', 'language',
    'currency_code', 'currency_name', 'postal_code_pattern',
    'phone_code', 'intl_dialing_prefix', 'natl_dialing_prefix',
    'subscriber_phone_pattern',
];

$previousMeta = is_file($metaPath)
    ? json_decode((string) file_get_contents($metaPath), true)
    : null;

$normalizedRecords = array_map(
    static function (array $row): array {
        return [
            'alpha2' => (string) ($row['alpha2'] ?? ''),
            'alpha3' => (string) ($row['alpha3'] ?? ''),
            'numeric_code' => (string) ($row['numeric'] ?? ''),
            'country_name' => (string) ($row['country'] ?? ''),
            'capital' => (string) ($row['capital'] ?? ''),
            'tld' => (string) ($row['tld'] ?? ''),
            'region_alpha_code' => (string) ($row['region']['alpha_code'] ?? ''),
            'region_num_code' => (string) ($row['region']['numeric_code'] ?? ''),
            'region_name' => (string) ($row['region']['name'] ?? ''),
            'sub_region_code' => (string) ($row['region']['sub_region']['code'] ?? ''),
            'sub_region_name' => (string) ($row['region']['sub_region']['name'] ?? ''),
            'language' => (string) ($row['language'] ?? ''),
            'currency_code' => (string) ($row['currency']['code'] ?? ''),
            'currency_name' => (string) ($row['currency']['name'] ?? ''),
            'postal_code_pattern' => (string) ($row['postal_code_pattern'] ?? ''),
            'phone_code' => (string) ($row['phone']['code'] ?? ''),
            'intl_dialing_prefix' => (string) ($row['phone']['international_prefix'] ?? ''),
            'natl_dialing_prefix' => (string) ($row['phone']['national_prefix'] ?? ''),
            'subscriber_phone_pattern' => (string) ($row['phone']['subscriber_pattern'] ?? ''),
        ];
    },
    $records
);

$jsonPayload = json_encode(
    $normalizedRecords,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
write_if_changed($jsonPath, $jsonPayload);

$csv = fopen('php://temp', 'w+b');
if ($csv === false) {
    throw new RuntimeException('Unable to open temporary CSV buffer.');
}
fputcsv($csv, $headers);
foreach ($normalizedRecords as $record) {
    fputcsv($csv, $record);
}
rewind($csv);
$csvPayload = stream_get_contents($csv);
fclose($csv);
if ($csvPayload === false) {
    throw new RuntimeException('Unable to read temporary CSV buffer.');
}
write_if_changed($csvPath, $csvPayload);

if (realpath($sourceFile) !== realpath($sqlitePath)) {
    rebuild_sqlite_from_records($sqlitePath, $normalizedRecords);
}

$validator = new DatasetValidator();
$report = $validator->validate($service->countries()->values(), false);
$validationPayload = json_encode(
    $report->toArray(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
write_if_changed($validationPath, $validationPayload);

$checksums = [];
foreach ([DataSource::SQLITE, DataSource::JSON, DataSource::CSV] as $fileName) {
    $fullPath = $targetDir . '/' . $fileName;
    $checksums[$fileName] = hash_file('sha256', $fullPath);
}

$shaPayload = implode(PHP_EOL, array_map(
    static fn(string $name, string $hash): string => $hash . '  ' . $name,
    array_keys($checksums),
    $checksums
)) . PHP_EOL;
write_if_changed($shaPath, $shaPayload);

// If checksums are unchanged, do not rewrite meta at all.
if (
    is_array($previousMeta)
    && isset($previousMeta['checksums'])
    && is_array($previousMeta['checksums'])
    && $previousMeta['checksums'] === $checksums
) {
    echo 'Dataset build completed.' . PHP_EOL;
    exit(0);
}

$builtAt = gmdate('Y-m-d\TH:i:s\Z');
$metaPayload = canonical_meta_payload($checksums, $builtAt);
write_if_changed($metaPath, $metaPayload);

echo 'Dataset build completed.' . PHP_EOL;
