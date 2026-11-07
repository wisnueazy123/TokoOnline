<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Kategori;
use App\Models\Order;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.rajaongkir.key' => 'test-key',
            'services.rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
            'services.rajaongkir.origin_city_id' => '137',
            'services.rajaongkir.origin_city_name' => 'Jakarta Pusat',
        ]);
    }

    private function makeUser(string $suffix): User
    {
        return User::create([
            'nama' => 'Customer '.$suffix,
            'email' => 'checkout.'.$suffix.'.'.uniqid().'@example.com',
            'role' => 'customer',
            'status' => 1,
            'password' => bcrypt('secret'),
        ]);
    }

    private function makeCustomer(User $user): Customer
    {
        return Customer::create([
            'user_id' => $user->id,
            'alamat' => 'Jl. Testing No. 1',
            'pos' => '12345',
        ]);
    }

    private function makeProduct(array $attributes = []): Produk
    {
        $kategori = Kategori::create([
            'nama_kategori' => 'Kategori Checkout '.uniqid(),
        ]);

        return Produk::create(array_merge([
            'kategori_id' => $kategori->id,
            'nama_produk' => 'Produk Checkout '.uniqid(),
            'detail' => 'Deskripsi test',
            'harga' => 15000,
            'stok' => 10,
            'berat' => 250,
            'status' => 'aktif',
        ], $attributes));
    }

    private function cartOrder(User $user): Order
    {
        return Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->firstOrFail();
    }

    private function fakeCost(int $cost = 18000, string $service = 'REG', string $etd = '2-3'): void
    {
        Http::fake([
            '*calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'name' => 'JNE',
                        'code' => 'jne',
                        'service' => $service,
                        'description' => 'Layanan Reguler',
                        'cost' => $cost,
                        'etd' => $etd,
                    ],
                ],
            ]),
        ]);
    }

    private function shippingPayload(array $overrides = []): array
    {
        return array_merge([
            'destination_city_id' => 1,
            'city_name' => 'Mataram',
            'province_name' => 'Nusa Tenggara Barat',
            'kurir' => 'jne',
            'layanan_ongkir' => 'REG',
            'alamat' => 'Jl. Pengiriman No. 10',
            'pos' => '83111',
        ], $overrides);
    }

    private function withShipping(Order $order, array $overrides = []): Order
    {
        $order->update(array_merge([
            'kurir' => 'JNE',
            'layanan_ongkir' => 'REG',
            'biaya_ongkir' => 18000,
            'estimasi_ongkir' => '2-3',
            'alamat' => 'Jl. Pengiriman No. 10, Mataram, Nusa Tenggara Barat',
            'pos' => '83111',
        ], $overrides));

        return $order->fresh();
    }

    public function test_select_shipping_menampilkan_ringkasan_keranjang(): void
    {
        $user = $this->makeUser('shipping');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['nama_produk' => 'Produk Kirim '.uniqid(), 'berat' => 250]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));

        $this->actingAs($user)
            ->get(route('order.selectShipping'))
            ->assertOk()
            ->assertSee($produk->nama_produk)
            ->assertSee('250 gram')
            ->assertSee('Cek Ongkir')
            ->assertSee('Lanjut ke Pembayaran');
    }

    public function test_update_ongkir_menyimpan_biaya_dari_api(): void
    {
        $this->fakeCost(18000, 'REG', '2-3');

        $user = $this->makeUser('ongkir');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['berat' => 250]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $order = $this->cartOrder($user);

        $this->actingAs($user)
            ->post(route('order.updateongkir'), $this->shippingPayload())
            ->assertRedirect(route('order.selectPayment'))
            ->assertSessionHas('success');

        $order->refresh();

        $this->assertSame('JNE', $order->kurir);
        $this->assertSame('REG', $order->layanan_ongkir);
        $this->assertSame(18000, (int) $order->biaya_ongkir);
        $this->assertSame('2-3', $order->estimasi_ongkir);
        $this->assertStringContainsString('Mataram', $order->alamat);
        $this->assertSame('83111', $order->pos);
        $this->assertSame(250, (int) $order->total_berat);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/calculate/domestic-cost')
                && $request['origin'] === '137'
                && $request['destination'] === '1'
                && (int) $request['weight'] === 250
                && $request['courier'] === 'jne';
        });
    }

    public function test_update_ongkir_menolak_layanan_yang_tidak_tersedia(): void
    {
        $this->fakeCost(18000, 'REG');

        $user = $this->makeUser('service');
        $this->makeCustomer($user);
        $produk = $this->makeProduct();

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $order = $this->cartOrder($user);

        $this->actingAs($user)
            ->post(route('order.updateongkir'), $this->shippingPayload(['layanan_ongkir' => 'YES']))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->kurir);
        $this->assertNull($order->fresh()->biaya_ongkir);
    }

    public function test_select_payment_menampilkan_subtotal_ongkir_dan_total(): void
    {
        $user = $this->makeUser('payment');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['nama_produk' => 'Produk Bayar '.uniqid(), 'harga' => 15000]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $order = $this->withShipping($this->cartOrder($user));

        $this->actingAs($user)
            ->get(route('order.selectPayment'))
            ->assertOk()
            ->assertSee($produk->nama_produk)
            ->assertSee('Rp. 15.000')
            ->assertSee('Rp. 18.000')
            ->assertSee('Rp. 33.000')
            ->assertSee('JNE - REG');
    }

    public function test_checkout_mengurangi_stok_dan_menyimpan_history(): void
    {
        $user = $this->makeUser('checkout');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['nama_produk' => 'Produk Checkout '.uniqid(), 'stok' => 10]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $order = $this->withShipping($this->cartOrder($user));

        $this->actingAs($user)
            ->post(route('order.checkout'))
            ->assertRedirect(route('order.history'))
            ->assertSessionHas('success');

        $this->assertSame('Paid', $order->fresh()->status);
        $this->assertSame(9, (int) $produk->fresh()->stok);

        $this->actingAs($user)
            ->get(route('order.history'))
            ->assertOk()
            ->assertSee($produk->nama_produk)
            ->assertSee('Diproses')
            ->assertSee('Rp. 33.000');
    }

    public function test_checkout_ditolak_jika_stok_tidak_mencukupi(): void
    {
        $user = $this->makeUser('stockcheck');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['stok' => 1]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $order = $this->withShipping($this->cartOrder($user));

        $produk->update(['stok' => 0]);

        $this->actingAs($user)
            ->post(route('order.checkout'))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, (int) $produk->fresh()->stok);
    }

    public function test_history_hanya_menampilkan_pesanan_milik_customer(): void
    {
        $userA = $this->makeUser('historya');
        $this->makeCustomer($userA);
        $userB = $this->makeUser('historyb');
        $this->makeCustomer($userB);

        $produkA = $this->makeProduct(['nama_produk' => 'Produk History A '.uniqid()]);
        $produkB = $this->makeProduct(['nama_produk' => 'Produk History B '.uniqid()]);

        $this->actingAs($userA)->post(route('order.addToCart', $produkA->id));
        $orderA = $this->withShipping($this->cartOrder($userA));
        $orderA->update(['status' => 'Paid']);

        $this->actingAs($userB)->post(route('order.addToCart', $produkB->id));
        $orderB = $this->withShipping($this->cartOrder($userB));
        $orderB->update(['status' => 'Selesai']);

        $this->actingAs($userA)
            ->get(route('order.history'))
            ->assertOk()
            ->assertSee($produkA->nama_produk)
            ->assertDontSee($produkB->nama_produk);
    }

    public function test_guest_diarahkan_ke_login_untuk_checkout(): void
    {
        $this->get(route('order.selectShipping'))->assertRedirect(route('auth.redirect'));
        $this->get(route('order.history'))->assertRedirect(route('auth.redirect'));
        $this->post(route('order.checkout'))->assertRedirect(route('auth.redirect'));
    }
}
