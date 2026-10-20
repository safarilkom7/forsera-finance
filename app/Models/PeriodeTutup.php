<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PeriodeTutup extends Model
{
    use LogsActivity;

    protected $table = 'periode_tutup';

    protected $fillable = [
        'tahun',
        'bulan',
        'unit_usaha_id',
        'ditutup_oleh',
        'ditutup_at',
        'dibuka_kembali_oleh',
        'dibuka_kembali_at',
        'alasan',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'ditutup_at' => 'datetime',
            'dibuka_kembali_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('periode');
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function ditutupOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    public function dibukaKembaliOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_kembali_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('dibuka_kembali_at');
    }

    public function berlakuUntuk(?int $unitId): bool
    {
        return $this->unit_usaha_id === null || $this->unit_usaha_id === $unitId;
    }

    public static function bulanDari(Carbon|string $tanggal): array
    {
        $c = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return [(int) $c->year, (int) $c->month];
    }
}
