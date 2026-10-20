<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pemasukan extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pemasukan';

    protected $fillable = [
        'tanggal',
        'unit_usaha_id',
        'kategori_pemasukan_id',
        'metode_pembayaran_id',
        'nominal',
        'note',
        'lampiran',
        'status_verifikasi',
        'rekonsiliasi_id',
        'insiden_id',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'lampiran' => 'array',
            'status_verifikasi' => StatusVerifikasi::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pemasukan');
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPemasukan::class, 'kategori_pemasukan_id');
    }

    public function metode(): BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }

    public function rekonsiliasi(): BelongsTo
    {
        return $this->belongsTo(Rekonsiliasi::class);
    }

    public function insiden(): BelongsTo
    {
        return $this->belongsTo(Insiden::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    public function scopeUntukUnit(Builder $query, int $unitId): Builder
    {
        return $query->where('unit_usaha_id', $unitId);
    }

    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id'));
    }

    public function scopeBelumDicek(Builder $query): Builder
    {
        return $query->where('status_verifikasi', StatusVerifikasi::BELUM_DICEK->value);
    }

    public function scopeDapatDirekonsiliasi(Builder $query): Builder
    {
        return $query->whereIn('status_verifikasi', [
            StatusVerifikasi::BELUM_DICEK->value,
            StatusVerifikasi::MENUNGGU_SETTLEMENT->value,
        ])->whereNull('rekonsiliasi_id');
    }

    public function terkunci(): bool
    {
        return $this->status_verifikasi->terkunci();
    }

    public function bisaDiubahOleh(User $user): bool
    {
        if ($this->terkunci()) {
            return false;
        }

        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        return $this->dibuat_oleh === $user->id;
    }
}
