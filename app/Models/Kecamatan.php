<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'kecamatan';
    public $timestamps = false;

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kd_kab', 'kd_kab');
    }

    public function desa()
    {
        return $this->hasMany(Desa::class, 'kd_kec', 'kd_kec')->orderBy('kd_desa', 'ASC');
    }

    public function ttd()
    {
        return $this->belongsTo(TandaTanganLaporan::class, 'id', 'lokasi');
    }

    public function tanda_tangan()
    {
        return $this->hasOne(TandaTanganDokumen::class, 'lokasi')->where('dokumen_pinjaman_id', '0')->where('jenis_laporan', 'pelaporan');
    }

    public function saldo()
    {
        return $this->hasMany(Saldo::class, 'kode_akun', 'kd_kec');
    }

    public function personalia()
    {
        return $this->hasMany(Personalia::class, 'lokasi', 'id');
    }

    public function wa_session()
    {
        return $this->hasOne(Whatsapp::class, 'lokasi');
    }
}
