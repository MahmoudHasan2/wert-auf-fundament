<?php
declare(strict_types=1);

namespace App\Services;

/** Builds page metadata and verified-only structured data. */
final class Seo
{
    public static function meta(array $in): array
    {
        $suffix = Settings::get('seo_title_suffix', ' | ' . Settings::get('company_name', 'Wert auf Fundament GmbH'));
        $title = trim((string) ($in['title'] ?? ''));
        $fullTitle = !empty($in['raw_title']) ? $title : ($title !== '' ? $title . $suffix : ltrim($suffix, ' |'));
        $description = trim((string) ($in['description'] ?? '')) ?: Settings::get('seo_default_description');
        $ogMedia = Media::find((int) ($in['og_media_id'] ?? 0)) ?? Media::find((int) Settings::get('seo_og_media_id'));
        $indexable = Settings::get('seo_indexing') === '1' && empty($in['noindex']);
        return [
            'title' => $fullTitle,
            'og_title' => $title !== '' ? $title : $fullTitle,
            'description' => str_excerpt($description, 300),
            'canonical' => isset($in['path']) ? abs_url((string) $in['path']) : '',
            'og_image' => $ogMedia ? Media::absUrl($ogMedia, 'lg') : abs_url('/assets/img/mark-512.png'),
            'robots' => $indexable ? 'index, follow' : 'noindex, nofollow',
            'breadcrumbs' => $in['breadcrumbs'] ?? [],
            'schema' => $in['schema'] ?? [],
            'type' => $in['type'] ?? 'website',
        ];
    }

    /** Organisation data – only fields that are actually filled in the settings. */
    public static function organization(): array
    {
        $s = fn (string $k) => Settings::get($k);
        $org = [
            '@context' => 'https://schema.org',
            '@type' => 'HomeAndConstructionBusiness',
            'name' => $s('company_name'),
            'url' => abs_url('/'),
            'logo' => abs_url('/assets/img/logo.png'),
        ];
        if ($s('email') !== '') {
            $org['email'] = $s('email');
        }
        if ($s('phone') !== '') {
            $org['telephone'] = $s('phone');
        }
        if ($s('vat_id') !== '') {
            $org['vatID'] = $s('vat_id');
        }
        if ($s('registered_street') !== '' && $s('registered_city') !== '') {
            $org['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $s('registered_street'),
                'postalCode' => $s('registered_zip'),
                'addressLocality' => $s('registered_city'),
                'addressCountry' => 'DE',
            ];
        }
        $same = array_values(array_filter([$s('social_facebook'), $s('social_instagram'), $s('social_linkedin')]));
        if ($same) {
            $org['sameAs'] = $same;
        }
        return $org;
    }

    public static function breadcrumbSchema(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => $c) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => abs_url($c[1])];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
