<?php

namespace App\Models;

use App\Enums\TipeMetodePembayaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
