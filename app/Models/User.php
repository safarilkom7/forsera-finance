<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin_keuangan';

    public const ROLE_STAF = 'staf_unit';

    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'aktif',
        'last_unit_usaha_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'aktif' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function unitUsaha(): BelongsToMany
    {
        return $this->belongsToMany(UnitUsaha::class, 'user_unit_usaha')->withTimestamps();
    }

    public function lastUnit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class, 'last_unit_usaha_id');
    }

    public function pemasukanDibuat(): HasMany
    {
        return $this->hasMany(Pemasukan::class, 'dibuat_oleh');
    }

    public function pengeluaranDibuat(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'dibuat_oleh');
    }

    public function isOwner(): bool
    {
        return $this->hasRole(self::ROLE_OWNER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isStaf(): bool
    {
        return $this->hasRole(self::ROLE_STAF);
    }

    /** Semua peran selain Owner tunduk pada aturan maker-checker. */
    public function tundukMakerChecker(): bool
    {
        return ! $this->isOwner();
    }

    public function bolehAksesUnit(int $unitId): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }

        return $this->unitUsaha()->whereKey($unitId)->exists();
    }

    public function unitIds(): array
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return UnitUsaha::query()->pluck('id')->all();
        }

        return $this->unitUsaha()->pluck('unit_usaha.id')->all();
    }
}
