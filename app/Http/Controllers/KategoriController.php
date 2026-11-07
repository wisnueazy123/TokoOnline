<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::all();

        return view('backend.v_kategori.index', [
            'judul' => 'Data Kategori',
            'index' => $kategori
        ]);
    }

    public function create()
    {
        return view('backend.v_kategori.create', [
            'judul' => 'Tambah Kategori'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required'
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori
        ]);

        return redirect()
            ->route('backend.kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    public function destroy($id)
    {
        Kategori::findOrFail($id)->delete();

        return redirect()
            ->route('backend.kategori.index')
            ->with('success', 'Kategori berhasil dihapus');
    }
}
