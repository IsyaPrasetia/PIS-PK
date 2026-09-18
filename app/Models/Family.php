<?php

namespace App\Models;

use App\Support\Indikator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'no_kk',
        'kepala_keluarga',
        'jalan',
        'rt',
        'rw',
        'desa',
        'kecamatan',
        'surveyor',
        'tanggal',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * Kolom indikator (ind_*) ikut mass-assignable tanpa perlu didaftar manual.
     */
    public function getFillable(): array
    {
        return array_merge(
            $this->fillable,
            array_map(fn (string $id) => 'ind_'.$id, Indikator::ids()),
        );
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Nilai 12 indikator keluarga ini, dipetakan sebagai [id => Y|T|N].
     */
    public function indicatorValues(): array
    {
        $values = [];

        foreach (Indikator::ids() as $id) {
            $values[$id] = $this->{'ind_'.$id} ?: 'N';
        }

        return $values;
    }

    /**
     * Hitung Indeks Keluarga Sehat: jumlah "Ya" dibagi (Ya + Tidak).
     * Indikator "Tidak berlaku" dikeluarkan dari perhitungan.
     *
     * @return array{score: float|null, label: string, class: string, ya: int, tidak: int, belum: int, answered: int}
     */
    public function iks(): array
    {
        $ya = 0;
        $tidak = 0;

        foreach (Indikator::ids() as $id) {
            $value = $this->{'ind_'.$id};

            if ($value === 'Y') {
                $ya++;
            } elseif ($value === 'T') {
                $tidak++;
            }
        }

        $answered = $ya + $tidak;

        if ($answered === 0) {
            return [
                'score' => null,
                'label' => 'Belum Lengkap',
                'class' => 'na',
                'ya' => 0,
                'tidak' => 0,
                'belum' => count(Indikator::ids()),
                'answered' => 0,
            ];
        }

        $score = (float) ($ya / $answered);

        if ($score > 0.8) {
            $label = 'Keluarga Sehat';
            $class = 'sehat';
        } elseif ($score >= 0.5) {
            $label = 'Pra-Sehat';
            $class = 'pra';
        } else {
            $label = 'Tidak Sehat';
            $class = 'tidak';
        }

        return [
            'score' => $score,
            'label' => $label,
            'class' => $class,
            'ya' => $ya,
            'tidak' => $tidak,
            'belum' => count(Indikator::ids()) - $answered,
            'answered' => $answered,
        ];
    }

    /**
     * Ringkasan IKS sekumpulan keluarga (untuk dasbor & rekap wilayah).
     *
     * @param  iterable<Family>  $families
     * @return array{n: int, sehat: int, pra: int, tidak: int, belum: int, avg: float|null}
     */
    public static function iksStatsFor(iterable $families): array
    {
        $n = $sehat = $pra = $tidak = $belum = $scoreCount = 0;
        $scoreSum = 0.0;

        foreach ($families as $family) {
            $n++;
            $result = $family->iks();

            if ($result['class'] === 'sehat') {
                $sehat++;
            } elseif ($result['class'] === 'pra') {
                $pra++;
            } elseif ($result['class'] === 'tidak') {
                $tidak++;
            } else {
                $belum++;
            }

            if ($result['score'] !== null) {
                $scoreSum += $result['score'];
                $scoreCount++;
            }
        }

        return [
            'n' => $n,
            'sehat' => $sehat,
            'pra' => $pra,
            'tidak' => $tidak,
            'belum' => $belum,
            'avg' => $scoreCount > 0 ? $scoreSum / $scoreCount : null,
        ];
    }

    /**
     * Batasi kueri ke wilayah yang boleh diakses pengguna.
     */
    public function scopeScopedFor(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            if ($user->kecamatan) {
                $query->where('kecamatan', $user->kecamatan);
            }

            if ($user->desa) {
                $query->where('desa', $user->desa);
            }

            if ($user->rw) {
                $query->where('rw', $user->rw);
            }

            if ($user->rt) {
                $query->where('rt', $user->rt);
            }
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('kepala_keluarga', 'like', "%{$term}%")
                ->orWhere('no_kk', 'like', "%{$term}%")
                ->orWhere('jalan', 'like', "%{$term}%")
                ->orWhere('desa', 'like', "%{$term}%")
                ->orWhere('kecamatan', 'like', "%{$term}%");
        });
    }

    public function alamatLengkap(): string
    {
        return collect([
            $this->jalan,
            $this->rt ? 'RT '.$this->rt : null,
            $this->rw ? 'RW '.$this->rw : null,
            $this->desa,
            $this->kecamatan,
        ])->filter()->implode(', ');
    }
}
