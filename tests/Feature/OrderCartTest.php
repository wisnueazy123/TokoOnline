<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Kategori;
use App\Models\Order;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderCartTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $suffix): User
    {
        return User::create([
            'nama' => 'Customer '.$suffix,
            'email' => 'cart.'.$suffix.'.'.uniqid().'@example.com',
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
            'nama_kategori' => 'Kategori Test '.uniqid(),
        ]);

        return Produk::create(array_merge([
            'kategori_id' => $kategori->id,
            'nama_produk' => 'Produk Test '.uniqid(),
            'detail' => 'Deskripsi test',
            'harga' => 15000,
            'stok' => 10,
            'berat' => 250,
            'status' => 'aktif',
        ], $attributes));
    }

    private function cartOrder(User $user): ?Order
    {
        return Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    public function test_guest_is_redirected_to_google_login_when_adding_to_cart(): void
    {
        $produk = $this->makeProduct();

        $this->post(route('order.addToCart', $produk->id))
            ->assertRedirect(route('auth.redirect'));

        $this->assertDatabaseMissing('order_item', ['produk_id' => $produk->id]);
    }

    public function test_customer_can_add_same_product_twice_and_total_is_calculated(): void
    {
        $user = $this->makeUser('add');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['harga' => 15000, 'berat' => 250, 'stok' => 5]);

        $this->actingAs($user)
            ->post(route('order.addToCart', $produk->id), ['harga' => 1, 'quantity' => 99])
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->post(route('order.addToCart', $produk->id))
            ->assertRedirect(route('order.cart'));

        $order = $this->cartOrder($user);

        $this->assertNotNull($order);
        $this->assertSame(30000.0, (float) $order->total_harga);
        $this->assertSame(500, (int) $order->total_berat);
        $this->assertSame(1, $order->orderItems()->count());

        $item = $order->orderItems()->first();
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(15000.0, (float) $item->harga);

        $this->actingAs($user)
            ->get(route('order.cart'))
            ->assertOk()
            ->assertSee($produk->nama_produk)
            ->assertSee('Rp. 30.000')
            ->assertSee('2 item dalam keranjang');
    }

    public function test_customer_can_update_quantity_and_invalid_quantity_is_rejected(): void
    {
        $user = $this->makeUser('update');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['harga' => 12000, 'berat' => 200, 'stok' => 10]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));

        $order = $this->cartOrder($user);
        $item = $order->orderItems()->first();

        $this->actingAs($user)
            ->put(route('order.updateCart', $item->id), ['quantity' => 3])
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('success');

        $this->assertSame(3, (int) $item->fresh()->quantity);
        $this->assertSame(36000.0, (float) $order->fresh()->total_harga);
        $this->assertSame(600, (int) $order->fresh()->total_berat);

        $this->actingAs($user)
            ->put(route('order.updateCart', $item->id), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(3, (int) $item->fresh()->quantity);
    }

    public function test_customer_cannot_update_quantity_beyond_stock(): void
    {
        $user = $this->makeUser('stock');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['stok' => 2]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));

        $order = $this->cartOrder($user);
        $item = $order->orderItems()->first();

        $this->actingAs($user)
            ->put(route('order.updateCart', $item->id), ['quantity' => 3])
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->assertSame(1, (int) $item->fresh()->quantity);
        $this->assertSame(15000.0, (float) $order->fresh()->total_harga);
    }

    public function test_customer_can_remove_product_and_empty_cart_is_displayed(): void
    {
        $user = $this->makeUser('remove');
        $this->makeCustomer($user);
        $produk = $this->makeProduct();

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));

        $order = $this->cartOrder($user);
        $item = $order->orderItems()->first();

        $this->actingAs($user)
            ->delete(route('order.removeFromCart', $item->id))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('order_item', ['id' => $item->id]);
        $this->assertDatabaseMissing('order', ['id' => $order->id]);

        $this->actingAs($user)
            ->get(route('order.cart'))
            ->assertOk()
            ->assertSee('Keranjang belanja kosong.');
    }

    public function test_cart_page_shows_empty_state_without_order(): void
    {
        $user = $this->makeUser('empty');
        $this->makeCustomer($user);

        $this->actingAs($user)
            ->get(route('order.cart'))
            ->assertOk()
            ->assertSee('Keranjang belanja kosong.');
    }

    public function test_cart_data_is_isolated_between_customers(): void
    {
        $userA = $this->makeUser('a');
        $this->makeCustomer($userA);
        $userB = $this->makeUser('b');
        $this->makeCustomer($userB);

        $produk = $this->makeProduct(['nama_produk' => 'Produk Isolasi '.uniqid()]);

        $this->actingAs($userA)->post(route('order.addToCart', $produk->id));

        $orderA = $this->cartOrder($userA);
        $itemA = $orderA->orderItems()->first();

        $this->actingAs($userB)
            ->get(route('order.cart'))
            ->assertOk()
            ->assertDontSee($produk->nama_produk);

        $this->actingAs($userB)
            ->put(route('order.updateCart', $itemA->id), ['quantity' => 5])
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->actingAs($userB)
            ->delete(route('order.removeFromCart', $itemA->id))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('order_item', [
            'id' => $itemA->id,
            'order_id' => $orderA->id,
            'quantity' => 1,
        ]);

        $this->actingAs($userB)->post(route('order.addToCart', $produk->id));

        $orderB = $this->cartOrder($userB);

        $this->assertNotNull($orderB);
        $this->assertNotSame($orderA->id, $orderB->id);
        $this->assertSame(1, $orderB->orderItems()->count());
        $this->assertSame(1, (int) $orderB->orderItems()->first()->quantity);
        $this->assertSame(1, (int) $itemA->fresh()->quantity);
    }

    public function test_inactive_product_and_out_of_stock_product_are_rejected(): void
    {
        $user = $this->makeUser('reject');
        $this->makeCustomer($user);

        $inactive = $this->makeProduct(['status' => 'blok']);
        $outOfStock = $this->makeProduct(['stok' => 0]);

        $this->actingAs($user)
            ->post(route('order.addToCart', $inactive->id))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->get(route('order.cart'))
            ->assertSee('Produk tidak tersedia.');

        $this->actingAs($user)
            ->post(route('order.addToCart', $outOfStock->id))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->get(route('order.cart'))
            ->assertSee('Stok produk habis.');

        $this->assertDatabaseMissing('order_item', ['produk_id' => $inactive->id]);
        $this->assertDatabaseMissing('order_item', ['produk_id' => $outOfStock->id]);
    }

    public function test_navbar_shows_total_cart_quantity(): void
    {
        $user = $this->makeUser('badge');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['stok' => 5]);

        $this->actingAs($user)->post(route('order.addToCart', $produk->id));
        $this->actingAs($user)->post(route('order.addToCart', $produk->id));

        $this->actingAs($user)
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('class="qty">2</span>', false);
    }

    public function test_existing_frontend_product_pages_still_render(): void
    {
        $user = $this->makeUser('pages');
        $this->makeCustomer($user);
        $produk = $this->makeProduct(['status' => 'aktif']);

        $this->actingAs($user)->get(route('beranda'))->assertOk();
        $this->actingAs($user)->get(route('produk.all'))->assertOk();
        $this->actingAs($user)
            ->get(route('produk.kategori', $produk->kategori_id))
            ->assertOk();
        $this->actingAs($user)
            ->get(route('produk.detail', $produk->id))
            ->assertOk()
            ->assertSee($produk->nama_produk);
    }
}
