<?php

declare(strict_types=1);

namespace Iriven;

final class CountryArrayTransformer
{
    public function toApiArray(Country $country): array
    {
        return [
            'alpha2' => $country->alpha2(),
            'alpha3' => $country->alpha3(),
            'numeric' => $country->numeric(),
            'country' => $country->name(),
            'capital' => $country->capital(),
            'tld' => $country->tld(),
            'language' => $country->language(),
            'postal_code_pattern' => $country->postalCodePattern(),
            'currency' => $country->currency()->toArray(),
            'region' => $country->region()->toArray(),
            'phone' => $country->phone()->toArray(),
        ];
    }

    public function toStorageArray(Country $country): array
    {
        return [
            $country->alpha2(),
            $country->alpha3(),
            $country->numeric(),
            $country->name(),
            $country->capital(),
            $country->tld(),
            $country->region()->alphaCode(),
            $country->region()->numericCode(),
            $country->region()->name(),
            $country->region()->subRegion()->code(),
            $country->region()->subRegion()->name(),
            $country->language(),
            $country->currency()->code(),
            $country->currency()->name(),
            $country->postalCodePattern(),
            $country->phone()->code(),
            $country->phone()->internationalPrefix(),
            $country->phone()->nationalPrefix(),
            $country->phone()->subscriberPattern(),
        ];
    }

    public function toFlatArray(Country $country): array
    {
        return [
            'alpha2' => $country->alpha2(),
            'alpha3' => $country->alpha3(),
            'numeric_code' => $country->numeric(),
            'country_name' => $country->name(),
            'capital' => $country->capital(),
            'tld' => $country->tld(),
            'region_alpha_code' => $country->region()->alphaCode(),
            'region_num_code' => $country->region()->numericCode(),
            'region_name' => $country->region()->name(),
            'sub_region_code' => $country->region()->subRegion()->code(),
            'sub_region_name' => $country->region()->subRegion()->name(),
            'language' => $country->language(),
            'currency_code' => $country->currency()->code(),
            'currency_name' => $country->currency()->name(),
            'postal_code_pattern' => $country->postalCodePattern(),
            'phone_code' => $country->phone()->code(),
            'intl_dialing_prefix' => $country->phone()->internationalPrefix(),
            'natl_dialing_prefix' => $country->phone()->nationalPrefix(),
            'subscriber_phone_pattern' => $country->phone()->subscriberPattern(),
        ];
    }
}
