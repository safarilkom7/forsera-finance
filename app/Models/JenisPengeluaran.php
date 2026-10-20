<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JenisPengeluaran extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'jenis_pengeluaran';

    protected $fillable = ['nama', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
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

    /** Jenis pengeluaran yang lazim dipakai untuk royalti franchise. */
    public function isRoyalti(): bool
    {
        return str_contains(mb_strtolower($this->nama), 'royalti');
    }
}
