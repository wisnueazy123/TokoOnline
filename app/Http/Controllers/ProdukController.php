<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\FotoProduk;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::with('kategori')->orderBy('updated_at', 'desc')->get();

        return view('backend.v_produk.index', [
            'judul' => 'Data Produk',
            'index' => $produk
        ]);
    }

    public function create()
    {
        $kategori = Kategori::all();

        return view('backend.v_produk.create', [
            'judul' => 'Tambah Produk',
            'kategori' => $kategori
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required',
            'nama_produk' => 'required',
            'harga' => 'required',
            'stok' => 'required',
            'berat' => 'nullable',
            'detail' => 'nullable',
            'foto' => 'nullable|image',
            'status' => 'required'
        ]);

        $foto = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto')->getClientOriginalName();
            $this->simpanFoto($request->file('foto'), $foto);
        }

        Produk::create([
            'kategori_id' => $request->kategori_id,
            'nama_produk' => $request->nama_produk,
            'detail' => $request->detail,
            'harga' => $request->harga,
            'stok' => $request->stok,
            'berat' => $request->berat ?? 0,
            'foto' => $foto,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('backend.produk.index')
            ->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit($id)
    {
        $kategori = Kategori::orderBy('nama_kategori', 'asc')->get();
        $row = Produk::findOrFail($id);

        return view('backend.v_produk.edit', [
            'judul' => 'Ubah Produk',
            'kategori' => $kategori,
            'row' => $row
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required',
            'nama_produk' => 'required',
            'harga' => 'required',
            'stok' => 'required',
            'berat' => 'nullable',
            'detail' => 'nullable',
            'foto' => 'nullable|image',
            'status' => 'required'
        ]);

        $row = Produk::findOrFail($id);
        $fotoLama = $row->foto;

        $data = [
            'kategori_id' => $request->kategori_id,
            'nama_produk' => $request->nama_produk,
            'detail' => $request->detail,
            'harga' => $request->harga,
            'stok' => $request->stok,
            'berat' => $request->berat ?? 0,
            'status' => $request->status,
        ];

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->getClientOriginalName();
            $this->simpanFoto($request->file('foto'), $data['foto']);

            if ($fotoLama && $fotoLama !== $data['foto']) {
                $this->hapusFoto($fotoLama);
            }
        }

        $row->update($data);

        return redirect()
            ->route('backend.produk.index')
            ->with('success', 'Produk berhasil diubah');
    }

    public function destroy($id)
    {
        $row = Produk::findOrFail($id);
        $foto = $row->foto;

        $row->fotoProduk()->delete();
        $row->delete();

        if ($foto) {
            $this->hapusFoto($foto);
        }

        return redirect()
            ->route('backend.produk.index')
            ->with('success', 'Produk berhasil dihapus');
    }

    private function hapusFoto($namaFile)
    {
        $dipakaiLain = Produk::where('foto', $namaFile)->exists();

        if ($dipakaiLain) {
            return;
        }

        $folder = storage_path('app/public/img-produk');

        $file = [
            $namaFile,
            'thumb_lg_' . $namaFile,
            'thumb_md_' . $namaFile,
            'thumb_sm_' . $namaFile,
        ];

        foreach ($file as $satu) {
            if (is_file($folder . DIRECTORY_SEPARATOR . $satu)) {
                unlink($folder . DIRECTORY_SEPARATOR . $satu);
            }
        }
    }

    public function foto($id)
    {
        $row = Produk::findOrFail($id);

        return view('backend.v_produk.foto', [
            'judul' => 'Kelola Foto Produk',
            'row' => $row,
            'fotoProdukTambahan' => FotoProduk::where('produk_id', $id)->get()
        ]);
    }

    public function storeFoto(Request $request)
    {
        $request->validate([
            'produk_id' => 'required',
            'foto' => 'required|image'
        ]);

        $namaFile = $request->file('foto')->getClientOriginalName();
        $this->simpanFoto($request->file('foto'), $namaFile);

        FotoProduk::create([
            'produk_id' => $request->produk_id,
            'foto' => $namaFile
        ]);

        return redirect()
            ->route('backend.produk.foto', $request->produk_id)
            ->with('success', 'Foto berhasil ditambahkan');
    }

    public function destroyFoto($id)
    {
        $row = FotoProduk::findOrFail($id);
        $produkId = $row->produk_id;
        $foto = $row->foto;

        $row->delete();

        if ($foto) {
            $this->hapusFoto($foto);
        }

        return redirect()
            ->route('backend.produk.foto', $produkId)
            ->with('success', 'Foto berhasil dihapus');
    }

    public function detail($id)
    {
        $fotoProdukTambahan = FotoProduk::where('produk_id', $id)->get();
        $detail = Produk::findOrFail($id);
        $kategori = Kategori::orderBy('nama_kategori', 'desc')->get();

        return view('v_produk.detail', [
            'judul' => 'Detail Produk',
            'kategori' => $kategori,
            'row' => $detail,
            'fotoProdukTambahan' => $fotoProdukTambahan
        ]);
    }

    public function produkKategori($id)
    {
        $kategori = Kategori::orderBy('nama_kategori', 'desc')->get();
        $produk = Produk::where('kategori_id', $id)
            ->where('status', 'aktif')
            ->orderBy('updated_at', 'desc')
            ->paginate(6);

        return view('v_produk.produkkategori', [
            'judul' => 'Filter Kategori',
            'kategori' => $kategori,
            'produk' => $produk,
        ]);
    }

    public function produkAll()
    {
        $kategori = Kategori::orderBy('nama_kategori', 'desc')->get();
        $produk = Produk::where('status', 'aktif')
            ->orderBy('updated_at', 'desc')
            ->paginate(6);

        return view('v_produk.index', [
            'judul' => 'Semua Produk',
            'kategori' => $kategori,
            'produk' => $produk,
        ]);
    }

    private function simpanFoto($file, $namaFile)
    {
        $folder = storage_path('app/public/img-produk');

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $file->move($folder, $namaFile);

        $sumber = $folder . DIRECTORY_SEPARATOR . $namaFile;
        $info = @getimagesize($sumber);

        if ($info === false) {
            return;
        }

        $this->buatThumbnail($sumber, $folder, $namaFile, 1200, 1200, '');
        $this->buatThumbnail($sumber, $folder, $namaFile, 800, 800, 'thumb_lg_');
        $this->buatThumbnail($sumber, $folder, $namaFile, 400, 533, 'thumb_md_');
        $this->buatThumbnail($sumber, $folder, $namaFile, 200, 200, 'thumb_sm_');
    }

    private function buatThumbnail($sumber, $folder, $namaFile, $lebar, $tinggi, $awalan)
    {
        $info = getimagesize($sumber);
        $lebarAsli = $info[0];
        $tinggiAsli = $info[1];

        $rasioTujuan = $lebar / $tinggi;
        $rasioAsli = $lebarAsli / $tinggiAsli;

        if ($rasioAsli > $rasioTujuan) {
            $tinggiPotong = $tinggiAsli;
            $lebarPotong = (int) round($tinggiAsli * $rasioTujuan);
            $xPotong = (int) round(($lebarAsli - $lebarPotong) / 2);
            $yPotong = 0;
        } else {
            $lebarPotong = $lebarAsli;
            $tinggiPotong = (int) round($lebarAsli / $rasioTujuan);
            $xPotong = 0;
            $yPotong = (int) round(($tinggiAsli - $tinggiPotong) / 2);
        }

        $target = $folder . DIRECTORY_SEPARATOR . $awalan . $namaFile;

        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);
        $transparan = imagecolorallocatealpha($gambar, 0, 0, 0, 127);
        imagefilledrectangle($gambar, 0, 0, $lebar, $tinggi, $transparan);

        switch ($info['mime']) {
            case 'image/jpeg':
                $sumberGambar = imagecreatefromjpeg($sumber);
                imagecopyresampled($gambar, $sumberGambar, 0, 0, $xPotong, $yPotong, $lebar, $tinggi, $lebarPotong, $tinggiPotong);
                imagejpeg($gambar, $target, 90);
                break;
            case 'image/png':
                $sumberGambar = imagecreatefrompng($sumber);
                imagecopyresampled($gambar, $sumberGambar, 0, 0, $xPotong, $yPotong, $lebar, $tinggi, $lebarPotong, $tinggiPotong);
                imagepng($gambar, $target, 9);
                break;
            case 'image/gif':
                $sumberGambar = imagecreatefromgif($sumber);
                imagecopyresampled($gambar, $sumberGambar, 0, 0, $xPotong, $yPotong, $lebar, $tinggi, $lebarPotong, $tinggiPotong);
                imagegif($gambar, $target);
                break;
            case 'image/webp':
                $sumberGambar = imagecreatefromwebp($sumber);
                imagecopyresampled($gambar, $sumberGambar, 0, 0, $xPotong, $yPotong, $lebar, $tinggi, $lebarPotong, $tinggiPotong);
                imagewebp($gambar, $target, 90);
                break;
        }

        imagedestroy($gambar);
    }
}
