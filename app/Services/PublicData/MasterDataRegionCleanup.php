<?php

namespace App\Services\PublicData;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Polsek;
use App\Models\School;

/**
 * Keeps the master tables scoped to Kabupaten Morowali only.
 *
 * Kecamatan outside the configured Morowali list (e.g. the Morowali Utara
 * regency) are removed together with their kelurahan, polsek and school rows.
 */
class MasterDataRegionCleanup
{
    /**
     * @return array{out_of_scope: int, kecamatan: int, kelurahan: int, polsek: int, schools: int}
     */
    public function cleanup(): array
    {
        $allowed = config('public_data.morowali.kecamatan', []);

        $kecamatans = Kecamatan::whereNotIn('name', $allowed)->get();

        $counts = [
            'out_of_scope' => $kecamatans->count(),
            'kecamatan' => 0,
            'kelurahan' => 0,
            'polsek' => 0,
            'schools' => 0,
        ];

        foreach ($kecamatans as $kecamatan) {
            $counts['kelurahan'] += Kelurahan::where('kecamatan_id', $kecamatan->id)->count();
            $counts['polsek'] += Polsek::where('kecamatan_id', $kecamatan->id)->count();
            $counts['schools'] += School::where('kecamatan_id', $kecamatan->id)->count();

            Kelurahan::where('kecamatan_id', $kecamatan->id)->forceDelete();
            Polsek::where('kecamatan_id', $kecamatan->id)->forceDelete();
            School::where('kecamatan_id', $kecamatan->id)->forceDelete();
            $kecamatan->forceDelete();

            $counts['kecamatan']++;
        }

        return $counts;
    }
}
