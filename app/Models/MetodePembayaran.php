<?php

namespace App\Models;

use App\Enums\TipeMetodePembayaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MetodePembayaran extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'metode_pembayaran';

    protected $fillable = ['nama', 'tipe', 'aktif'];

    protected function casts(): array
    {
        return [
            'tipe' => TipeMetodePembayaran::class,
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

    public function pemasukan(): HasMany
    {
        return $this->hasMany(Pemasukan::class);
    }

    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
