<?php

namespace App\Models;

use App\Enums\ArahSelisih;
use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Enums\TipePenyelesaian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Insiden extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'insiden';

    protected $fillable = [
        'kode',
        'rekonsiliasi_id',
        'jenis',
        'arah',
        'nominal_selisih',
        'nominal_terselesaikan',
        'status',
        'penyebab',
        'tipe_penyelesaian',
        'catatan_penyelesaian',
        'penanggung_jawab_id',
        'lampiran',
        'dibuat_oleh',
        'ditutup_oleh',
        'ditutup_at',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisInsiden::class,
            'arah' => ArahSelisih::class,
            'status' => StatusInsiden::class,
            'tipe_penyelesaian' => TipePenyelesaian::class,
            'nominal_selisih' => 'integer',
            'nominal_terselesaikan' => 'integer',
            'lampiran' => 'array',
            'ditutup_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('insiden');
    }

    public function rekonsiliasi(): BelongsTo
    {
        return $this->belongsTo(Rekonsiliasi::class);
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function ditutupOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    public function log(): HasMany
    {
        return $this->hasMany(InsidenLog::class)->orderBy('created_at');
    }

    /** Pemasukan otomatis dari penyelesaian CATAT_PENDAPATAN_LAIN. */
    public function pemasukanLain(): HasOne
    {
        return $this->hasOne(Pemasukan::class);
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusInsiden::OPEN->value,
            StatusInsiden::INVESTIGASI->value,
        ]);
    }

    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('rekonsiliasi', fn (Builder $q) => $q
            ->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id')));
    }

    public function sisa(): int
    {
        return max(0, $this->nominal_selisih - $this->nominal_terselesaikan);
    }
}
