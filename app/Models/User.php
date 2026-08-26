<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;
    public $timestamps = false;

    /**
     * Filter user yang aktif berdasarkan masa keanggotaan:
     * status = 1, sejak < $tanggal, dan hingga >= $tanggal
     * (atau hingga NULL = tanpa batas waktu, tetap dianggap aktif).
     */
    public function scopeAktif($query, ?string $tanggal = null)
    {
        $tanggal = $tanggal ?: date('Y-m-d');

        return $query
            ->where('status', '1')
            ->where('sejak', '<', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->where('hingga', '>=', $tanggal)
                    ->orWhereNull('hingga');
            });
    }

    public function j()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan');
    }

    public function l()
    {
        return $this->belongsTo(Level::class, 'level');
    }

    public function p()
    {
        return $this->belongsTo(Pendidikan::class, 'pendidikan', 'id');
    }

    public function kec()
    {
        return $this->belongsTo(Kecamatan::class, 'lokasi', 'id');
    }
}
