<?php

namespace App\Models;

use App\Enums\TipeUnitUsaha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UnitUsaha extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'unit_usaha';

    protected $fillable = ['kode', 'nama', 'tipe', 'aktif'];

    protected function casts(): array
    {
        return [
            'tipe' => TipeUnitUsaha::class,
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

    public function kategoriPemasukan(): HasMany
    {
        return $this->hasMany(KategoriPemasukan::class);
    }

    public function pemasukan(): HasMany
    {
        return $this->hasMany(Pemasukan::class);
    }

    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_unit_usaha')->withTimestamps();
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
