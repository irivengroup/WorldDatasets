<?php

declare(strict_types=1);

namespace Iriven;

final class MetaInfo implements \JsonSerializable
{
    public function __construct(
        private readonly int $count,
        private readonly string $source,
        private readonly string $version,
        private readonly ?string $lastUpdatedAt = null,
        private readonly string $packageVersion = '1.0.0',
        private readonly string $datasetVersion = '1.0.0',
        private readonly ?string $checksum = null,
        private readonly ?string $builtAt = null,
    ) {
    }

    public function count(): int { return $this->count; }
    public function source(): string { return $this->source; }
    public function version(): string { return $this->version; }
    public function lastUpdatedAt(): ?string { return $this->lastUpdatedAt; }
    public function packageVersion(): string { return $this->packageVersion; }
    public function datasetVersion(): string { return $this->datasetVersion; }
    public function checksum(): ?string { return $this->checksum; }
    public function builtAt(): ?string { return $this->builtAt; }

    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'source' => $this->source,
            'version' => $this->version,
            'last_updated_at' => $this->lastUpdatedAt,
            'package_version' => $this->packageVersion,
            'dataset_version' => $this->datasetVersion,
            'checksum' => $this->checksum,
            'built_at' => $this->builtAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
