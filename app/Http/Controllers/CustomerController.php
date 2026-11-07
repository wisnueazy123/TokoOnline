<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ImageHelper;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class CustomerController extends Controller
{
    public function index()
    {
        $customer = Customer::with('user')->orderBy('id', 'desc')->get();
        return view('backend.v_customer.index', [
            'judul' => 'Customer',
            'sub' => 'Halaman Customer',
            'index' => $customer
        ]);
    }

    public function show($id)
    {
        $customer = Customer::with('user')->findOrFail($id);
        return view('backend.v_customer.show', [
            'judul' => 'Detail Customer',
            'customer' => $customer
        ]);
    }

    public function edit($id)
    {
        $customer = Customer::with('user')->findOrFail($id);
        return view('backend.v_customer.edit', [
            'judul' => 'Ubah Data Customer',
            'edit' => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::with('user')->findOrFail($id);

        $rules = [
            'nama' => 'required|max:255',
            'hp' => 'required|min:10|max:15',
            'foto' => 'image|mimes:jpeg,jpg,png,gif|file|max:1024',
            'alamat' => 'required',
            'pos' => 'required',
        ];

        $messages = [
            'foto.image' => 'Format gambar gunakan file dengan ekstensi jpeg, jpg, png, atau gif.',
            'foto.max' => 'Ukuran file gambar maksimal adalah 1024 KB.',
            'hp.required' => 'Nomor HP wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'pos.required' => 'Kode pos wajib diisi.',
        ];

        if ($request->email != $customer->user->email) {
            $rules['email'] = 'required|max:255|email|unique:users,email,' . $customer->user->id;
        } else {
            $rules['email'] = 'required|max:255|email';
        }

        $validatedData = $request->validate($rules, $messages);

        if ($request->file('foto')) {
            if ($customer->user->foto) {
                $oldImagePath = public_path('storage/img-customer/') . $customer->user->foto;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';
            if (class_exists(ImageHelper::class)) {
                ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            } else {
                $file->move(public_path($directory), $originalFileName);
            }
            $validatedData['foto'] = $originalFileName;
        }

        $customer->user->update($validatedData);
        $customer->update([
            'alamat' => $request->input('alamat'),
            'pos' => $request->input('pos'),
        ]);

        return redirect()->route('backend.customer.index')
            ->with('success', 'Data customer berhasil diubah');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $nama = $customer->user->nama ?? 'Customer';

        if ($customer->user && $customer->user->foto) {
            $oldImagePath = public_path('storage/img-customer/') . $customer->user->foto;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $customer->delete();

        return redirect()->route('backend.customer.index')
            ->with('success', 'Data ' . $nama . ' berhasil dihapus');
    }

    // Redirect ke Google
    public function redirect()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->with(['prompt' => 'select_account'])->redirect();
    }

    // Callback dari Google
    public function callback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth gagal: ' . $e->getMessage());

            return redirect('/')->with('error', 'Terjadi kesalahan saat login dengan Google.');
        }

        try {
            $user = DB::transaction(function () use ($socialUser) {
                // Cek apakah email sudah terdaftar
                $user = User::where('email', $socialUser->email)->first();

                if (!$user) {
                    // Buat user baru sebagai customer aktif
                    $user = User::create([
                        'nama' => $socialUser->name,
                        'email' => $socialUser->email,
                        'role' => 'customer', // Role customer
                        'status' => 1, // Status aktif
                        'password' => Hash::make('default_password'), // Password default (opsional)
                    ]);
                }

                // Pastikan data customer tersedia (tidak duplikat, tanpa mengubah role admin/superadmin)
                $this->syncCustomerData($user, $socialUser);

                return $user;
            });

            // Login pengguna
            Auth::login($user);

            // Redirect ke halaman utama
            return redirect()->intended('beranda');
        } catch (\Exception $e) {
            Log::error('Login Google gagal: ' . $e->getMessage());

            // Redirect ke halaman utama jika terjadi kesalahan
            return redirect('/')->with('error', 'Terjadi kesalahan saat login dengan Google.');
        }
    }

    /**
     * Pastikan user dengan role customer memiliki tepat satu baris data customer.
     * Akun admin/superadmin tidak dihubungkan ke data customer dan role-nya tidak diubah.
     */
    protected function syncCustomerData(User $user, $socialUser): void
    {
        if ($user->role !== 'customer') {
            return;
        }

        $customer = Customer::firstOrNew(['user_id' => $user->id]);

        $customer->google_id = $socialUser->id;
        $customer->google_token = $socialUser->token;
        $customer->save();
    }

    public function akun($id)
    {
        $loggedInCustomerId = Auth::user()->id;
        // Cek apakah ID yang diberikan sama dengan ID customer yang sedang login
        if ($id != $loggedInCustomerId) {
            // Redirect atau tampilkan pesan error
            return redirect()->route('customer.akun', ['id' => $loggedInCustomerId])
                ->with('msgError', 'Anda tidak berhak mengakses akun ini.');
        }
        $customer = Customer::where('user_id', $id)->firstOrFail();
        return view('v_customer.edit', [
            'judul' => 'Akun Saya',
            'subJudul' => 'Akun Customer',
            'edit' => $customer
        ]);
    }

    public function updateAkun(Request $request, $id)
    {
        $customer = Customer::where('user_id', $id)->firstOrFail();

        // Pastikan hanya pemilik akun yang sedang login yang boleh mengubah
        if (Auth::user()->id != $id) {
            return redirect()->route('customer.akun', ['id' => Auth::user()->id])
                ->with('msgError', 'Anda tidak berhak mengubah akun ini.');
        }

        $rules = [
            'nama' => 'required|max:255',
            'hp' => 'required|min:10|max:13',
            'foto' => 'image|mimes:jpeg,jpg,png,gif|file|max:1024',
        ];
        $messages = [
            'foto.image' => 'Format gambar gunakan file dengan ekstensi jpeg, jpg, png, atau gif.',
            'foto.max' => 'Ukuran file gambar Maksimal adalah 1024 KB.'
        ];

        if ($request->email != $customer->user->email) {
            // Modul menulis unique:customer, tetapi kolom email berada di tabel users
            $rules['email'] = 'required|max:255|email|unique:users';
        }
        if ($request->alamat != $customer->alamat) {
            $rules['alamat'] = 'required';
        }
        if ($request->pos != $customer->pos) {
            $rules['pos'] = 'required';
        }

        $validatedData = $request->validate($rules, $messages);

        // menggunakan ImageHelper
        if ($request->file('foto')) {
            //hapus gambar lama
            if ($customer->user->foto) {
                $oldImagePath = public_path('storage/img-customer/') . $customer->user->foto;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';
            // Simpan gambar dengan ukuran yang ditentukan
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            // Simpan nama file asli di database
            $validatedData['foto'] = $originalFileName;
        }

        $customer->user->update($validatedData);

        $customer->update([
            'alamat' => $request->input('alamat'),
            'pos' => $request->input('pos'),
        ]);

        return redirect()->route('customer.akun', $id)->with('success', 'Data berhasil diperbarui');
    }

    public function logout(Request $request)
    {
        Auth::logout(); // Logout pengguna
        $request->session()->invalidate(); // Hapus session
        $request->session()->regenerateToken(); // Regenerate token CSRF

        return redirect('/')->with('success', 'Anda telah berhasil logout.');
    }
}
