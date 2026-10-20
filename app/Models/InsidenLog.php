<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsidenLog extends Model
{
    protected $table = 'insiden_log';

    public $timestamps = false;

    protected $fillable = [
        'insiden_id',
        'user_id',
        'aksi',
        'catatan',
        'nominal',
        'lampiran',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'lampiran' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function insiden(): BelongsTo
    {
        return $this->belongsTo(Insiden::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
