<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\Arrayable;

final class Country implements Arrayable, \JsonSerializable
{
    private ?CurrencyInfo $currency = null;
    private ?RegionInfo $regionObject = null;
    private ?PhoneInfo $phoneObject = null;
    private ?array $apiArray = null;
    private ?array $indexedArray = null;

    public function __construct(
        private readonly string $alpha2,
        private readonly string $alpha3,
        private readonly string $numeric,
        private readonly string $country,
        private readonly string $capital,
        private readonly string $tld,
        private readonly string $regionAlphaCode,
        private readonly string $regionNumCode,
        private readonly string $region,
        private readonly string $subRegionCode,
        private readonly string $subRegion,
        private readonly string $language,
        private readonly string $currencyCode,
        private readonly string $currencyName,
        private readonly string $postalCodePattern,
        private readonly string $phoneCode,
        private readonly string $intlDialingPrefix,
        private readonly string $natlDialingPrefix,
        private readonly string $subscriberPhonePattern,
    ) {
    }

    public function alpha2(): string { return $this->alpha2; }
    public function alpha3(): string { return $this->alpha3; }
    public function numeric(): string { return $this->numeric; }
    public function name(): string { return $this->country; }
    public function capital(): string { return $this->capital; }
    public function tld(): string { return $this->tld; }
    public function language(): string { return $this->language; }
    public function languages(): string { return $this->language(); }
    public function postalCodePattern(): string { return $this->postalCodePattern; }

    public function currency(): CurrencyInfo
    {
        return $this->currency ??= new CurrencyInfo($this->currencyCode, $this->currencyName);
    }

    public function region(): RegionInfo
    {
        return $this->regionObject ??= new RegionInfo(
            $this->regionAlphaCode,
            $this->regionNumCode,
            $this->region,
            new SubRegionInfo($this->subRegionCode, $this->subRegion),
        );
    }

    public function phone(): PhoneInfo
    {
        if ($this->phoneObject !== null) {
            return $this->phoneObject;
        }

        $areaCode = $this->natlDialingPrefix;
        $subscriberPattern = $this->subscriberPhonePattern;
        $pattern = $areaCode !== ''
            ? '(\+' . preg_quote($this->phoneCode, '/') . '|' . $areaCode . ')'
            : '(\+' . preg_quote($this->phoneCode, '/') . ')?';
        $pattern .= $subscriberPattern !== '' ? '(' . $subscriberPattern . ')' : '(\d+)';

        return $this->phoneObject = new PhoneInfo(
            $this->phoneCode,
            $this->intlDialingPrefix,
            $this->natlDialingPrefix,
            $this->subscriberPhonePattern,
            $pattern,
        );
    }

    public function isInRegion(string $region): bool
    {
        return mb_strtolower(trim($this->region)) === mb_strtolower(trim($region));
    }

    public function hasCurrency(string $code): bool
    {
        return strtoupper(trim($this->currencyCode)) === strtoupper(trim($code));
    }

    public function exists(): bool
    {
        return true;
    }

    public function data(): array
    {
        return $this->all();
    }

    public function toArray(): array
    {
        return $this->apiArray ??= (new CountryArrayTransformer())->toApiArray($this);
    }

    public function toIndexedArray(): array
    {
        return $this->indexedArray ??= (new CountryArrayTransformer())->toStorageArray($this);
    }

    public function all(): array
    {
        return $this->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->country;
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            alpha2: (string)($row['alpha2'] ?? ''),
            alpha3: (string)($row['alpha3'] ?? ''),
            numeric: (string)($row['numeric_code'] ?? ''),
            country: (string)($row['country_name'] ?? ''),
            capital: (string)($row['capital'] ?? ''),
            tld: (string)($row['tld'] ?? ''),
            regionAlphaCode: (string)($row['region_alpha_code'] ?? ''),
            regionNumCode: (string)($row['region_num_code'] ?? ''),
            region: (string)($row['region_name'] ?? ''),
            subRegionCode: (string)($row['sub_region_code'] ?? ''),
            subRegion: (string)($row['sub_region_name'] ?? ''),
            language: (string)($row['language'] ?? ''),
            currencyCode: (string)($row['currency_code'] ?? ''),
            currencyName: (string)($row['currency_name'] ?? ''),
            postalCodePattern: (string)($row['postal_code_pattern'] ?? ''),
            phoneCode: (string)($row['phone_code'] ?? ''),
            intlDialingPrefix: (string)($row['intl_dialing_prefix'] ?? ''),
            natlDialingPrefix: (string)($row['natl_dialing_prefix'] ?? ''),
            subscriberPhonePattern: (string)($row['subscriber_phone_pattern'] ?? ''),
        );
    }
}
