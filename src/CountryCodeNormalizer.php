<?php

declare(strict_types=1);

namespace Iriven;

final class CountryCodeNormalizer
{
    public function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function normalizeAlpha(string $code): string
    {
        return $this->normalize($code);
    }

    public function normalizeNumeric(string $code): string
    {
        return trim($code);
    }

    public function normalizePreservingNumeric(string $code): string
    {
        $value = trim($code);

        return ctype_digit($value) ? $value : strtoupper($value);
    }

    public function normalizeTld(string $tld): string
    {
        $value = trim(strtolower($tld));

        if ($value === '') {
            return '';
        }

        return str_starts_with($value, '.') ? $value : '.' . $value;
    }
}
