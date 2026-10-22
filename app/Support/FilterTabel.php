<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filter kolom untuk tabel master data.
 *
 * Nilai diambil dari query string (`f_*`) dan hanya kolom yang terdaftar pada
 * peta yang boleh disaring, sehingga parameter liar tidak bisa menyentuh kolom
 * lain. Semua nilai diikat sebagai parameter query (bukan disisipkan ke SQL).
 */
final class FilterTabel
{
    /**
     * @param  array<string, array{kolom: string, jenis?: string, relasi?: string, null?: string}>  $peta
     * @return array<string, string>
     */
    public static function nilai(Request $request, array $peta): array
    {
        $nilai = [];

        foreach (array_keys($peta) as $kunci) {
            $isi = $request->query($kunci);

            if (! is_string($isi)) {
                continue;
            }

            $isi = trim(mb_substr($isi, 0, 100));

            if ($isi !== '') {
                $nilai[$kunci] = $isi;
            }
        }

        return $nilai;
    }

    /**
     * @param  array<string, array{kolom: string, jenis?: string, relasi?: string, null?: string}>  $peta
     * @param  array<string, string>  $nilai
     */
    public static function terapkan(Builder $query, array $nilai, array $peta): void
    {
        foreach ($peta as $kunci => $aturan) {
            $isi = $nilai[$kunci] ?? null;

            if ($isi === null) {
                continue;
            }

            match ($aturan['jenis'] ?? 'teks') {
                'teks' => $query->where($aturan['kolom'], 'like', '%'.self::tanpaWildcard($isi).'%'),
                'pilih' => isset($aturan['null']) && $isi === $aturan['null']
                    ? $query->whereNull($aturan['kolom'])
                    : $query->where($aturan['kolom'], $isi),
                'aktif' => $query->where($aturan['kolom'], $isi === 'aktif'),
                'isi' => $isi === 'ada'
                    ? $query->whereNotNull($aturan['kolom'])
                    : $query->whereNull($aturan['kolom']),
                'relasi' => $query->whereHas(
                    $aturan['relasi'],
                    fn (Builder $sub) => $sub->where($aturan['kolom'], 'like', '%'.self::tanpaWildcard($isi).'%'),
                ),
                default => null,
            };
        }
    }

    /**
     * @param  array<string, string>  $nilai
     */
    public static function aktif(array $nilai): bool
    {
        return $nilai !== [];
    }

    /** Karakter wildcard LIKE dibuang agar pencarian tidak berubah menjadi "semua". */
    private static function tanpaWildcard(string $isi): string
    {
        return str_replace(['%', '_'], '', $isi);
    }
}
