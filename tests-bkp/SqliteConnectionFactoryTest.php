<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Exception\RepositoryException;
use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteConnectionFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class SqliteConnectionFactoryTest extends TestCase
{
    public function testCreateThrowsWhenFileDoesNotExist(): void
    {
        $factory = new SqliteConnectionFactory();

        $this->expectException(RepositoryException::class);
        $factory->create(__DIR__ . '/missing.sqlite');
    }

    public function testCreateReturnsPdoForExistingFile(): void
    {
        $factory = new SqliteConnectionFactory();
        $path = tempnam(sys_get_temp_dir(), 'wd_sqlite_');
        if ($path === false) {
            self::fail('Unable to create temp file.');
        }

        try {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY)');

            $created = $factory->create($path);
            self::assertInstanceOf(PDO::class, $created);
        } finally {
            if (is_file($path)) {
                self::assertTrue(unlink($path));
            }
        }
    }
}