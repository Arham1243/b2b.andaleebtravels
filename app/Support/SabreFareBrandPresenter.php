<?php

namespace App\Support;

final class SabreFareBrandPresenter
{
    /**
     * @param array<string, mixed> $pricingBlock
     * @param array<string, mixed> $grouped
     */
    public static function fromPricingBlock(array $pricingBlock, array $grouped = []): ?string
    {
        $brands = self::collectBrandNames($pricingBlock, $grouped);

        if ($brands === []) {
            return null;
        }

        $carrier = self::resolveCarrierCode($pricingBlock);

        return self::formatDisplayLabel($brands, $carrier);
    }

    /**
     * @param array<string, mixed> $pricingBlock
     * @param array<string, mixed> $grouped
     *
     * @return list<string>
     */
    public static function collectBrandNames(array $pricingBlock, array $grouped = []): array
    {
        $brandById = collect($grouped['brandDescs'] ?? [])->keyBy('id');
        $fareComponentById = collect($grouped['fareComponentDescs'] ?? [])->keyBy('id');
        $fareComponents = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo.fareComponents');

        if (!is_array($fareComponents)) {
            return [];
        }

        $names = [];

        foreach ($fareComponents as $component) {
            if (!is_array($component)) {
                continue;
            }

            $name = self::resolveComponentBrandName($component, $brandById, $fareComponentById);

            if ($name !== null) {
                $names[] = $name;
            }

            foreach (($component['segments'] ?? []) as $segWrap) {
                $segmentName = self::normalizeBrandToken(
                    (string) data_get($segWrap, 'segment.brandName', ''),
                );

                if ($segmentName !== '') {
                    $names[] = $segmentName;
                }
            }
        }

        $unique = [];

        foreach ($names as $name) {
            $key = strtolower($name);

            if (!isset($unique[$key])) {
                $unique[$key] = $name;
            }
        }

        return array_values($unique);
    }

    /**
     * @param array<string, mixed> $pricingBlock
     */
    private static function resolveCarrierCode(array $pricingBlock): string
    {
        $carrier = strtoupper(trim((string) data_get($pricingBlock, 'fare.validatingCarrierCode', '')));

        if ($carrier !== '') {
            return $carrier;
        }

        $governing = strtoupper(trim((string) data_get($pricingBlock, 'fare.governingCarriers', '')));

        if ($governing === '') {
            return '';
        }

        return strtok($governing, ' ') ?: '';
    }

    /**
     * @param list<string> $brands
     */
    private static function formatDisplayLabel(array $brands, string $carrierCode): string
    {
        $airlineName = AirlineCatalog::name($carrierCode);
        $labels = [];

        foreach ($brands as $brand) {
            $labels[] = self::formatSingleBrand($brand, $carrierCode, $airlineName);
        }

        return implode(' / ', array_values(array_unique(array_filter($labels))));
    }

    private static function formatSingleBrand(string $brand, string $carrierCode, ?string $airlineName): string
    {
        $normalized = self::normalizeBrandToken($brand);

        if ($normalized === '') {
            return '';
        }

        $titleBrand = self::titleBrand($normalized);

        if ($airlineName !== null) {
            $airlinePattern = preg_quote($airlineName, '/');
            $titleBrand = (string) preg_replace('/^' . $airlinePattern . '\s+/i', '', $titleBrand);
        }

        if ($carrierCode !== '') {
            $codePattern = preg_quote($carrierCode, '/');
            $titleBrand = (string) preg_replace('/^' . $codePattern . '\s+/i', '', $titleBrand);
        }

        $titleBrand = trim((string) preg_replace('/^(Eco|Economy)\s+/i', '', $titleBrand));

        if ($airlineName !== null && !self::containsAirlineReference($titleBrand, $airlineName, $carrierCode)) {
            return trim($airlineName . ' ' . $titleBrand);
        }

        return $titleBrand;
    }

    private static function containsAirlineReference(string $brand, string $airlineName, string $carrierCode): bool
    {
        $haystack = strtolower($brand);

        if ($carrierCode !== '' && str_contains($haystack, strtolower($carrierCode))) {
            return true;
        }

        return str_contains($haystack, strtolower($airlineName));
    }

    /**
     * @param \Illuminate\Support\Collection<int|string, mixed> $brandById
     * @param \Illuminate\Support\Collection<int|string, mixed> $fareComponentById
     * @param array<string, mixed> $component
     */
    private static function resolveComponentBrandName(array $component, $brandById, $fareComponentById): ?string
    {
        foreach (['brandName', 'brandText'] as $key) {
            $direct = self::normalizeBrandToken((string) ($component[$key] ?? ''));

            if ($direct !== '') {
                return $direct;
            }
        }

        $nestedBrand = $component['brand'] ?? null;

        if (is_array($nestedBrand)) {
            $fromNested = self::brandFromDescriptor($nestedBrand);

            if ($fromNested !== null) {
                return $fromNested;
            }

            $ref = $nestedBrand['ref'] ?? null;

            if ($ref !== null) {
                $fromDesc = self::brandFromDescriptor($brandById->get($ref));

                if ($fromDesc !== null) {
                    return $fromDesc;
                }
            }
        }

        $componentRef = $component['ref'] ?? null;

        if ($componentRef !== null) {
            $fareComponent = $fareComponentById->get($componentRef);

            if (is_array($fareComponent)) {
                $fromFareComponent = self::brandFromDescriptor($fareComponent['brand'] ?? null);

                if ($fromFareComponent !== null) {
                    return $fromFareComponent;
                }
            }

            $fromDesc = self::brandFromDescriptor($brandById->get($componentRef));

            if ($fromDesc !== null) {
                return $fromDesc;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $descriptor
     */
    private static function brandFromDescriptor(?array $descriptor): ?string
    {
        if (!is_array($descriptor)) {
            return null;
        }

        foreach (['brandName', 'brandText', 'name'] as $key) {
            $value = self::normalizeBrandToken((string) ($descriptor[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function normalizeBrandToken(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return '';
        }

        return $value;
    }

    private static function titleBrand(string $brand): string
    {
        $lower = strtolower($brand);

        if (str_contains($lower, 'flexplus') || str_contains($lower, 'flex plus')) {
            return (string) preg_replace('/flex\s*plus/i', 'Flex Plus', ucwords($lower));
        }

        return ucwords($lower);
    }
}
