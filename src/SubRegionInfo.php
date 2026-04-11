<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Contract\Arrayable;

final class SubRegionInfo implements Arrayable, \JsonSerializable
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
