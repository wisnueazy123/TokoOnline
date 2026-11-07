<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RajaOngkirController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('beranda');
});

// ================= LOGIN =================

Route::get('backend/login', [LoginController::class, 'loginBackend'])
    ->name('backend.login');

Route::post('backend/login', [LoginController::class, 'authenticateBackend'])
    ->name('backend.login.post');

Route::post('backend/logout', [LoginController::class, 'logoutBackend'])
    ->name('backend.logout');

// ================= API GOOGLE =================

Route::get('/auth/redirect', [CustomerController::class, 'redirect'])
    ->name('auth.redirect');

Route::get('/auth/google/callback', [CustomerController::class, 'callback'])
    ->name('auth.callback');

// Logout
Route::post('/logout', [CustomerController::class, 'logout'])
    ->name('customer.logout');

// ================= BERANDA =================

Route::get('backend/beranda', [BerandaController::class, 'berandaBackend'])
    ->middleware('auth')
    ->name('backend.beranda');

Route::resource('backend/produk', ProdukController::class, [
    'as' => 'backend',
])->middleware('auth');

// Route untuk menambahkan foto
Route::post('foto-produk/store', [ProdukController::class, 'storeFoto'])
    ->name('backend.foto_produk.store')->middleware('auth');

// Route untuk menghapus foto
Route::delete('foto-produk/{id}', [ProdukController::class, 'destroyFoto'])
    ->name('backend.foto_produk.destroy')->middleware('auth');

// Route untuk kelola foto produk
Route::get('backend/produk/{id}/foto', [ProdukController::class, 'foto'])
    ->name('backend.produk.foto')->middleware('auth');

Route::resource('backend/kategori', KategoriController::class, [
    'as' => 'backend',
])->middleware('auth');

// ================= USER =================

Route::resource('backend/user', UserController::class, [
    'as' => 'backend',
])->middleware('auth');

// ================= CUSTOMER =================

Route::resource('backend/customer', CustomerController::class, [
    'as' => 'backend',
    'except' => ['create', 'store'],
])->middleware('auth');

// Group route untuk customer (Minggu 5: Route Group & Middleware)
Route::middleware('is.customer')->group(function () {
    // Route untuk menampilkan halaman akun customer
    Route::get('/customer/akun/{id}', [CustomerController::class, 'akun'])
        ->name('customer.akun');

    // Route untuk mengupdate data akun customer
    Route::put('/customer/updateakun/{id}', [CustomerController::class, 'updateAkun'])
        ->name('customer.updateakun');

    // Route keranjang belanja (Pertemuan 6)
    Route::get('/cart', [OrderController::class, 'viewCart'])
        ->name('order.cart');

    Route::post('/add-to-cart/{id}', [OrderController::class, 'addToCart'])
        ->whereNumber('id')
        ->name('order.addToCart');

    Route::put('/cart/update/{id}', [OrderController::class, 'updateCart'])
        ->whereNumber('id')
        ->name('order.updateCart');

    Route::delete('/cart/remove/{id}', [OrderController::class, 'removeFromCart'])
        ->whereNumber('id')
        ->name('order.removeFromCart');

    // Checkout Pertemuan 9
    Route::match(['get', 'post'], '/select-shipping', [OrderController::class, 'selectShipping'])
        ->name('order.selectShipping');

    Route::post('/updateongkir', [OrderController::class, 'updateOngkir'])
        ->name('order.updateongkir');

    Route::get('/selectpayment', [OrderController::class, 'selectPayment'])
        ->name('order.selectPayment');

    Route::post('/checkout', [OrderController::class, 'checkout'])
        ->name('order.checkout');

    Route::get('/history', [OrderController::class, 'orderHistory'])
        ->name('order.history');
});

// Frontend
Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');

Route::get('/produk/all', [ProdukController::class, 'produkAll'])->name('produk.all');

Route::get('/produk/kategori/{id}', [ProdukController::class, 'produkKategori'])->name('produk.kategori');

Route::get('/produk/detail/{id}', [ProdukController::class, 'detail'])->name('produk.detail');

// ================= CEK ONGKIR (RAJAONGKIR) =================

Route::get('/cek-ongkir', function () {
    return view('ongkir', [
        'judul' => 'Cek Ongkos Kirim',
    ]);
})->name('ongkir.index');

Route::get('/provinces', [RajaOngkirController::class, 'getProvinces'])
    ->name('ongkir.provinces');

Route::get('/cities', [RajaOngkirController::class, 'getCities'])
    ->name('ongkir.cities');

Route::post('/cost', [RajaOngkirController::class, 'getCost'])
    ->name('ongkir.cost');
