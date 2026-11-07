<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            'Makanan Tradisional',
            'Jajanan Tradisional',
            'Kue Tradisional',
            'Minuman',
        ];

        foreach ($kategoriList as $nama) {
            Kategori::firstOrCreate(['nama_kategori' => $nama]);
        }

        $produkList = [
            ['Gudeg', 'Makanan Tradisional', 15000, 40, 250, 'produk01.jpg', 'aktif'],
            ['Ayam Betutu', 'Makanan Tradisional', 55000, 15, 800, 'produk02.jpg', 'aktif'],
            ['Roti Buaya', 'Makanan Tradisional', 25000, 20, 600, 'produk03.jpg', 'aktif'],
            ['Kerak Telor', 'Jajanan Tradisional', 12000, 35, 200, 'produk04.jpg', 'aktif'],
            ['Rujak Cingur', 'Jajanan Tradisional', 18000, 30, 300, 'produk05.jpg', 'aktif'],
            ['Klepon Ketan', 'Jajanan Tradisional', 10000, 50, 250, 'produk05.jpg', 'aktif'],
            ['Kue Lapis Legit', 'Kue Tradisional', 45000, 12, 700, 'produk03.jpg', 'aktif'],
            ['Putu Ayu', 'Kue Tradisional', 20000, 0, 350, 'produk03.jpg', 'blok'],
            ['Es Kelapa Muda', 'Minuman', 14000, 60, 400, 'produk01.jpg', 'aktif'],
        ];

        foreach ($produkList as $item) {
            [$nama, $kategori, $harga, $stok, $berat, $foto, $status] = $item;

            Produk::updateOrCreate(
                ['nama_produk' => $nama],
                [
                    'kategori_id' => Kategori::where('nama_kategori', $kategori)->value('id'),
                    'detail' => '<p>' . $nama . ' dari kategori ' . $kategori . '.</p>',
                    'harga' => $harga,
                    'stok' => $stok,
                    'berat' => $berat,
                    'foto' => $foto,
                    'status' => $status,
                ]
            );
        }
    }
}
