<?php

declare(strict_types=1);

namespace Iriven;

final class CountriesRuntimeConfig
{
    public function __construct(
        private readonly ?string $sourcePath = null,
        private readonly bool $verifyChecksum = false,
        private readonly bool $strictValidation = false,
        private readonly bool $usePsr16Cache = false,
    ) {
    }

    public function sourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function verifyChecksum(): bool
    {
        return $this->verifyChecksum;
    }

    public function strictValidation(): bool
    {
        return $this->strictValidation;
    }

    public function usePsr16Cache(): bool
    {
        return $this->usePsr16Cache;
    }
}
