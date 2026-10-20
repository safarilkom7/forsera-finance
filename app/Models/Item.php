<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Item extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'item';

    protected $fillable = ['nama', 'satuan_default_id', 'jenis_pengeluaran_id', 'harga_terakhir', 'aktif'];

    protected function casts(): array
    {
        return [
            'harga_terakhir' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function getLabel(): string
    {
        return $this->nama;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('master');
    }

    public function satuanDefault(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_default_id');
    }

    public function jenisPengeluaran(): BelongsTo
    {
        return $this->belongsTo(JenisPengeluaran::class);
    }

    public function detailPengeluaran(): HasMany
    {
        return $this->hasMany(DetailPengeluaran::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /** Rata-rata harga 5 pembelian terakhir (hanya pengeluaran disetujui). */
    public function rataRataLimaTerakhir(): ?int
    {
        $harga = DetailPengeluaran::query()
            ->where('item_id', $this->id)
            ->whereHas('pengeluaran', fn (Builder $q) => $q
                ->where('status_persetujuan', \App\Enums\StatusPersetujuanPengeluaran::DISETUJUI->value)
                ->whereNull('deleted_at'))
            ->latest('id')
            ->limit(5)
            ->pluck('harga_satuan');

        return $harga->isEmpty() ? null : (int) round($harga->avg());
    }
}
