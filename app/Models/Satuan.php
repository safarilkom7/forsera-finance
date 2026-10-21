<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Satuan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'satuan';

    protected $fillable = ['nama', 'singkatan'];

    public function getLabel(): string
    {
        return $this->nama;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('master');
    }

    public function item(): HasMany
    {
        return $this->hasMany(Item::class, 'satuan_default_id');
    }

    public function detailPengeluaran(): HasMany
    {
        return $this->hasMany(DetailPengeluaran::class);
    }
}
