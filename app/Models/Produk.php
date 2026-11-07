<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kategori;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'nama_produk',
        'detail',
        'harga',
        'stok',
        'berat',
        'foto',
        'status'
    ];

public function kategori()
{
    return $this->belongsTo(Kategori::class);
}

public function fotoProduk()
{
    return $this->hasMany(FotoProduk::class);
}
}
