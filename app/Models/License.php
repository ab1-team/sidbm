<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $table = 'licenses';

    protected $fillable = [
        'kecamatan_id',
        'api_secret',
        'is_active',
        'expired_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expired_at' => 'datetime',
    ];

    /**
     * Tenant = Kecamatan. License terikat ke satu kecamatan.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }
}
