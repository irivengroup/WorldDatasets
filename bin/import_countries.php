<?php

declare(strict_types=1);

require_once __DIR__ . '/../legacy/cdata.php';

$service = new \Iriven\WorldCountriesDatas();
$rows = $service->all();

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

$records = [];
foreach ($rows as $row) {
    $records[] = array_combine($headers, array_pad($row, count($headers), ''));
}

file_put_contents(
    $targetDir . '/.countriesRepository.json',
    json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

$csv = fopen($targetDir . '/countriesRepository.csv', 'wb');
fputcsv($csv, $headers);
foreach ($records as $record) {
    fputcsv($csv, $record);
}
fclose($csv);

$sqliteFile = $targetDir . '/.countriesRepository.sqlite';
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

$seen = ['alpha2' => [], 'alpha3' => [], 'numeric' => []];
$pdo->beginTransaction();

foreach ($records as $record) {
    $alpha2 = strtoupper(trim((string)($record['alpha2'] ?? '')));
    $alpha3 = strtoupper(trim((string)($record['alpha3'] ?? '')));
    $numeric = trim((string)($record['numeric_code'] ?? ''));

    if ($alpha2 === '' || $alpha3 === '' || $numeric === '') {
        continue;
    }

    if (isset($seen['alpha2'][$alpha2]) || isset($seen['alpha3'][$alpha3]) || isset($seen['numeric'][$numeric])) {
        continue;
    }

    $seen['alpha2'][$alpha2] = true;
    $seen['alpha3'][$alpha3] = true;
    $seen['numeric'][$numeric] = true;

    $insert->execute([
        ':alpha2' => $alpha2,
        ':alpha3' => $alpha3,
        ':numeric_code' => $numeric,
        ':country_name' => trim((string)($record['country_name'] ?? '')),
        ':capital' => trim((string)($record['capital'] ?? '')),
        ':tld' => trim((string)($record['tld'] ?? '')),
        ':region_alpha_code' => trim((string)($record['region_alpha_code'] ?? '')),
        ':region_num_code' => trim((string)($record['region_num_code'] ?? '')),
        ':region_name' => trim((string)($record['region_name'] ?? '')),
        ':sub_region_code' => trim((string)($record['sub_region_code'] ?? '')),
        ':sub_region_name' => trim((string)($record['sub_region_name'] ?? '')),
        ':language' => trim((string)($record['language'] ?? '')),
        ':currency_code' => trim((string)($record['currency_code'] ?? '')),
        ':currency_name' => trim((string)($record['currency_name'] ?? '')),
        ':postal_code_pattern' => trim((string)($record['postal_code_pattern'] ?? '')),
        ':phone_code' => trim((string)($record['phone_code'] ?? '')),
        ':intl_dialing_prefix' => trim((string)($record['intl_dialing_prefix'] ?? '')),
        ':natl_dialing_prefix' => trim((string)($record['natl_dialing_prefix'] ?? '')),
        ':subscriber_phone_pattern' => trim((string)($record['subscriber_phone_pattern'] ?? '')),
    ]);
}

$pdo->commit();

echo 'Data files generated in ' . $targetDir . PHP_EOL;
