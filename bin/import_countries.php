<?php

declare(strict_types=1);

use Iriven\WorldDatasets\WorldDatasetsFactory;
use Iriven\WorldDatasets\DataSource;

require_once __DIR__ . '/../vendor/autoload.php';

$sourceFile = WorldDatasetsFactory::defaultSqlitePath();
if (!is_file($sourceFile)) {
    throw new RuntimeException(sprintf('Source file not found: %s', $sourceFile));
}

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

$targetDir = __DIR__ . '/../src/data';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    throw new RuntimeException(sprintf('Unable to create directory: %s', $targetDir));
}

$normalizedRecords = array_map(
    static function (array $row): array {
        return [
            'alpha2' => (string) ($row['alpha2'] ?? ''),
            'alpha3' => (string) ($row['alpha3'] ?? ''),
            'numeric_code' => (string) ($row['numeric'] ?? ''),
            'country_name' => (string) ($row['country'] ?? ''),
            'capital' => (string) ($row['capital'] ?? ''),
            'tld' => (string) ($row['tld'] ?? ''),
            'region_alpha_code' => (string) (($row['region']['alpha_code'] ?? '')),
            'region_num_code' => (string) (($row['region']['numeric_code'] ?? '')),
            'region_name' => (string) (($row['region']['name'] ?? '')),
            'sub_region_code' => (string) (($row['region']['sub_region']['code'] ?? '')),
            'sub_region_name' => (string) (($row['region']['sub_region']['name'] ?? '')),
            'language' => (string) ($row['language'] ?? ''),
            'currency_code' => (string) (($row['currency']['code'] ?? '')),
            'currency_name' => (string) (($row['currency']['name'] ?? '')),
            'postal_code_pattern' => (string) ($row['postal_code_pattern'] ?? ''),
            'phone_code' => (string) (($row['phone']['code'] ?? '')),
            'intl_dialing_prefix' => (string) (($row['phone']['international_prefix'] ?? '')),
            'natl_dialing_prefix' => (string) (($row['phone']['national_prefix'] ?? '')),
            'subscriber_phone_pattern' => (string) (($row['phone']['subscriber_pattern'] ?? '')),
        ];
    },
    $records
);

file_put_contents(
    $targetDir . '/' . DataSource::JSON,
    json_encode($normalizedRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

$csv = fopen($targetDir . '/' . DataSource::CSV, 'wb');
if ($csv === false) {
    throw new RuntimeException('Unable to open CSV output file.');
}
fputcsv($csv, $headers);
foreach ($normalizedRecords as $record) {
    fputcsv($csv, $record);
}
fclose($csv);

$sqliteFile = $targetDir . '/' . DataSource::SQLITE;
if (is_file($sqliteFile)) {
    unlink($sqliteFile);
}

$pdo = new PDO('sqlite:' . $sqliteFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    'CREATE TABLE countries (
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
    )'
);

$pdo->exec('CREATE INDEX idx_countries_alpha3 ON countries(alpha3)');
$pdo->exec('CREATE INDEX idx_countries_numeric_code ON countries(numeric_code)');
$pdo->exec('CREATE INDEX idx_countries_region_name ON countries(region_name)');
$pdo->exec('CREATE INDEX idx_countries_currency_code ON countries(currency_code)');

$insert = $pdo->prepare(
    'INSERT INTO countries (
        alpha2, alpha3, numeric_code, country_name, capital, tld, region_alpha_code, region_num_code,
        region_name, sub_region_code, sub_region_name, language, currency_code, currency_name,
        postal_code_pattern, phone_code, intl_dialing_prefix, natl_dialing_prefix, subscriber_phone_pattern
    ) VALUES (
        :alpha2, :alpha3, :numeric_code, :country_name, :capital, :tld, :region_alpha_code, :region_num_code,
        :region_name, :sub_region_code, :sub_region_name, :language, :currency_code, :currency_name,
        :postal_code_pattern, :phone_code, :intl_dialing_prefix, :natl_dialing_prefix, :subscriber_phone_pattern
    )'
);

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

echo 'Data files regenerated in ' . $targetDir . PHP_EOL;
