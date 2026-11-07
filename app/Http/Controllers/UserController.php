<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ImageHelper;

class UserController extends Controller
{
    /**
     * Menampilkan data user
     */
    public function index()
    {
        $user = User::all();

        return view('backend.v_user.index', [
            'judul' => 'Data User',
            'index' => $user
        ]);
    }

    /**
     * Form tambah user
     */
    public function create()
    {
        return view('backend.v_user.create', [
            'judul' => 'Tambah User',
        ]);
    }

    /**
     * Simpan user
     */

    public function store(Request $request)
{
     $request->validate([
        'nama' => 'required|max:255',
        'email' => 'required|email|unique:users',
        'role' => 'required',
        'hp' => 'required',
        'password' => 'required|confirmed',
    ]);

    User::create([
        'nama' => $request->nama,
        'email' => $request->email,
        'role' => $request->role,
        'status' => 1,
        'hp' => $request->hp,
        'foto' => null,
        'password' => Hash::make($request->password),
    ]);

    return redirect()
        ->route('backend.user.index')
        ->with('success', 'Data berhasil ditambahkan');
}


    /**
     * Hapus user
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->foto) {

            $oldImagePath = public_path('storage/img-user/') . $user->foto;

            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $user->delete();

        return redirect()
            ->route('backend.user.index')
            ->with('success', 'Data berhasil dihapus');
    }
   public function edit($id)
{
    $user = User::findOrFail($id);

    return view('backend.v_user.edit', [
        'judul' => 'Ubah User',
        'edit' => $user
    ]);
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'nama' => 'required|max:255',
        'email' => 'required|email',
        'role' => 'required',
        'hp' => 'required'
    ]);

    $user->update([
        'nama' => $request->nama,
        'email' => $request->email,
        'role' => $request->role,
        'hp' => $request->hp,
    ]);

    return redirect()
        ->route('backend.user.index')
        ->with('success', 'Data berhasil diubah');
}
}
