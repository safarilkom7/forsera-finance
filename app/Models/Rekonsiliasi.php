<?php

namespace App\Models;

use App\Enums\StatusRekonsiliasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Rekonsiliasi extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'rekonsiliasi';

    protected $fillable = [
        'kode',
        'unit_usaha_id',
        'metode_pembayaran_id',
        'periode_mulai',
        'periode_selesai',
        'tanggal_diterima',
        'total_dicatat',
        'potongan_kas',
        'biaya_admin',
        'total_diterima',
        'selisih',
        'status',
        'catatan',
        'bukti',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'dibatalkan_oleh',
        'dibatalkan_at',
        'alasan_pembatalan',
    ];

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'tanggal_diterima' => 'date',
            'total_dicatat' => 'integer',
            'potongan_kas' => 'integer',
            'biaya_admin' => 'integer',
            'total_diterima' => 'integer',
            'selisih' => 'integer',
            'status' => StatusRekonsiliasi::class,
            'bukti' => 'array',
            'diverifikasi_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('rekonsiliasi');
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function metode(): BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }

    public function pemasukan(): HasMany
    {
        return $this->hasMany(Pemasukan::class);
    }

    public function potonganPengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'rekonsiliasi_id');
    }

    public function insiden(): HasMany
    {
        return $this->hasMany(Insiden::class, 'rekonsiliasi_id');
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', '!=', StatusRekonsiliasi::DIBATALKAN->value);
    }

    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id'));
    }

    public function arah(): ?\App\Enums\ArahSelisih
    {
        return $this->selisih === 0 ? null : \App\Enums\ArahSelisih::dari($this->selisih);
    }
}
