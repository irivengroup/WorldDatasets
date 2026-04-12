<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Exporter;

use Iriven\WorldDatasets\Exception\ExportException;

final class CsvExporter
{
    /**
     * @param array<array<string, mixed>> $rows
     */
    public function export(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new ExportException('Unable to open in-memory CSV stream.');
        }

        fputcsv($stream, array_keys($rows[0]));
        foreach ($rows as $row) {
            $flat = [];
            foreach ($row as $key => $value) {
                $flat[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
            fputcsv($stream, $flat);
        }
        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    public function exportFile(string $path, array $rows): void
    {
        if (file_put_contents($path, $this->export($rows)) === false) {
            throw new ExportException(sprintf('Unable to write CSV file: %s', $path));
        }
    }
}
