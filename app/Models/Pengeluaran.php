<?php

namespace App\Models;

use App\Enums\StatusPersetujuanPengeluaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pengeluaran extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'tanggal',
        'unit_usaha_id',
        'jenis_pengeluaran_id',
        'metode_pembayaran_id',
        'total_nominal',
        'dari_kas_harian',
        'note',
        'lampiran',
        'status_persetujuan',
        'disetujui_oleh',
        'disetujui_at',
        'rekonsiliasi_id',
        'dibuat_oleh',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_nominal' => 'integer',
            'dari_kas_harian' => 'boolean',
            'lampiran' => 'array',
            'status_persetujuan' => StatusPersetujuanPengeluaran::class,
            'disetujui_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pengeluaran');
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPengeluaran::class, 'jenis_pengeluaran_id');
    }

    public function metode(): BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPengeluaran::class);
    }

    public function rekonsiliasi(): BelongsTo
    {
        return $this->belongsTo(Rekonsiliasi::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id'));
    }

    public function scopeDihitung(Builder $query): Builder
    {
        return $query->where('status_persetujuan', StatusPersetujuanPengeluaran::DISETUJUI->value);
    }

    public function scopeKasHarian(Builder $query): Builder
    {
        return $query->where('dari_kas_harian', true);
    }

    public function butuhPersetujuan(): bool
    {
        return $this->status_persetujuan === StatusPersetujuanPengeluaran::MENUNGGU;
    }

    public function bisaDiubahOleh(User $user): bool
    {
        if ($this->rekonsiliasi_id || $this->butuhPersetujuan()) {
            return $user->isOwner();
        }

        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        return $this->dibuat_oleh === $user->id;
    }
}
