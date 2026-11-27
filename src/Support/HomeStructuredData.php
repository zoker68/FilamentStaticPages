<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Support;

use Illuminate\Support\Str;

/**
 * JSON-LD Organization + WebSite of the home page, from fsp.structured_data (filled by zoker/shop when installed).
 */
final class HomeStructuredData
{
    public static function json(string $homeUrl): ?string
    {
        $graph = self::graph($homeUrl);

        if ($graph === null) {
            return null;
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG,
        );

        return $json === false ? null : $json;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public static function graph(string $homeUrl): ?array
    {
        $name = trim((string) config('fsp.site_name'));

        if (! config('fsp.structured_data.enabled') || $name === '') {
            return null;
        }

        $config = (array) config('fsp.structured_data.organization', []);
        $organizationId = $homeUrl . '#organization';

        $organization = self::filled([
            '@type' => self::text($config['type'] ?? null) ?? 'Organization',
            '@id' => $organizationId,
            'name' => $name,
            'legalName' => self::text($config['legal_name'] ?? null),
            'alternateName' => self::text($config['alternate_name'] ?? null),
            'url' => $homeUrl,
            'logo' => self::absoluteUrl($config['logo_url'] ?? null),
            'sameAs' => array_values(array_filter(array_map(self::text(...), (array) ($config['same_as'] ?? [])))),
            'email' => self::text($config['email'] ?? null),
            'telephone' => self::text($config['telephone'] ?? null),
            'vatID' => self::text($config['vat_id'] ?? null),
            'address' => self::address((array) ($config['address'] ?? [])),
            'hasMerchantReturnPolicy' => self::returnPolicy(config('fsp.structured_data.return_policy')),
        ]);

        $website = self::filled([
            '@type' => 'WebSite',
            '@id' => $homeUrl . '#website',
            'name' => $name,
            'alternateName' => $organization['alternateName'] ?? null,
            'url' => $homeUrl,
            'publisher' => ['@id' => $organizationId],
        ]);

        return [$organization, $website];
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, string>|null
     */
    private static function address(array $address): ?array
    {
        $fields = self::filled([
            'streetAddress' => self::text($address['street'] ?? null),
            'addressLocality' => self::text($address['locality'] ?? null),
            'postalCode' => self::text($address['postal_code'] ?? null),
            'addressCountry' => self::text($address['country'] ?? null),
        ]);

        return $fields === [] ? null : ['@type' => 'PostalAddress', ...$fields];
    }

    /**
     * A finite return window; without days or countries Google rejects the policy, so none is output.
     *
     * @return array<string, mixed>|null
     */
    private static function returnPolicy(mixed $policy): ?array
    {
        if (! is_array($policy) || empty($policy['days'])) {
            return null;
        }

        $countries = array_values(array_filter(array_map(self::text(...), (array) ($policy['countries'] ?? []))));

        if ($countries === []) {
            return null;
        }

        return self::filled([
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => count($countries) === 1 ? $countries[0] : $countries,
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => (int) $policy['days'],
            'returnFees' => self::schemaUrl($policy['fees'] ?? null),
            'returnMethod' => self::schemaUrl($policy['method'] ?? null),
        ]);
    }

    private static function schemaUrl(mixed $value): ?string
    {
        $value = self::text($value);

        if ($value === null) {
            return null;
        }

        return Str::startsWith($value, ['http://', 'https://']) ? $value : 'https://schema.org/' . $value;
    }

    private static function absoluteUrl(mixed $value): ?string
    {
        $value = self::text($value);

        if ($value === null) {
            return null;
        }

        return Str::startsWith($value, ['http://', 'https://']) ? $value : url($value);
    }

    private static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function filled(array $fields): array
    {
        return array_filter($fields, fn (mixed $value) => $value !== null && $value !== []);
    }
}
