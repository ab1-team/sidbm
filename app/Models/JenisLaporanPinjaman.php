<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisLaporanPinjaman extends Model
{
    use HasFactory;
    protected $table = 'jenis_laporan_pinjaman';
    protected $guarded = ['id'];
    public $timestamps = false;
}
