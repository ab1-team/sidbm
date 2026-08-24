<?php

namespace App\Support;

use App\Models\Kabupaten;
use App\Models\Kecamatan;

class TenantResolver
{
    public const CONNECTION_A = 'mysql';
    public const CONNECTION_B = 'mysql_b';

    public static function domainVariants(?string $host): array
    {
        $domain = strtolower(trim((string) $host));

        if ($domain === '') {
            return [];
        }

        $variants = [$domain];
        $domainId = str_replace('sidbm.net', 'sidbm.id', $domain);
        $domainNet = str_replace('sidbm.id', 'sidbm.net', $domain);

        foreach ([$domainId, $domainNet] as $variant) {
            if ($variant !== $domain && ! in_array($variant, $variants, true)) {
                $variants[] = $variant;
            }
        }

        return $variants;
    }

    public static function resolveByDomain(?string $host): ?array
    {
        $domains = self::domainVariants($host);

        if ($domains === []) {
            return null;
        }

        $kecamatan = Kecamatan::on(self::CONNECTION_B)
            ->where(function ($query) use ($domains) {
                $query->whereIn('web_kec', $domains)
                    ->orWhereIn('web_alternatif', $domains);
            })
            ->first();

        if ($kecamatan) {
            return [
                'type' => 'kecamatan',
                'connection' => self::CONNECTION_B,
                'tenant' => $kecamatan,
            ];
        }

        $kabupaten = Kabupaten::on(self::CONNECTION_B)
            ->where(function ($query) use ($domains) {
                $query->whereIn('web_kab', $domains)
                    ->orWhereIn('web_kab_alternatif', $domains);
            })
            ->first();

        if ($kabupaten) {
            return [
                'type' => 'kabupaten',
                'connection' => self::CONNECTION_B,
                'tenant' => $kabupaten,
            ];
        }

        $kecamatan = Kecamatan::on(self::CONNECTION_A)
            ->where(function ($query) use ($domains) {
                $query->whereIn('web_kec', $domains)
                    ->orWhereIn('web_alternatif', $domains);
            })
            ->first();

        if ($kecamatan) {
            return [
                'type' => 'kecamatan',
                'connection' => self::CONNECTION_A,
                'tenant' => $kecamatan,
            ];
        }

        $kabupaten = Kabupaten::on(self::CONNECTION_A)
            ->where(function ($query) use ($domains) {
                $query->whereIn('web_kab', $domains)
                    ->orWhereIn('web_kab_alternatif', $domains);
            })
            ->first();

        if ($kabupaten) {
            return [
                'type' => 'kabupaten',
                'connection' => self::CONNECTION_A,
                'tenant' => $kabupaten,
            ];
        }

        return null;
    }

    public static function kabupatenForDomain(?string $host): ?Kabupaten
    {
        $resolved = self::resolveByDomain($host);

        return $resolved && $resolved['type'] === 'kabupaten'
            ? $resolved['tenant']
            : null;
    }

    public static function kecamatanIdForDomain(?string $host): ?int
    {
        $resolved = self::resolveByDomain($host);

        return $resolved && $resolved['type'] === 'kecamatan'
            ? (int) $resolved['tenant']->id
            : null;
    }

    public static function applyResolvedConnection(array $resolved, string $configKey = 'database.default'): void
    {
        $config = config('database');
        $config['default'] = $resolved['connection'];

        config([
            'database' => $config,
            $configKey => $resolved['connection'],
        ]);
    }

    public static function applySuffixFromKecamatan(int|string|null $id): void
    {
        config(['tenant.suffix' => '_'.$id]);
    }

    public static function markAsKabupaten(bool $value = true): void
    {
        config(['tenant.is_kab' => $value]);
    }
}
