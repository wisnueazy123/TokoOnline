<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderController extends Controller
{
    public function addToCart($id)
    {
        $customer = $this->currentCustomer();

        if (! $customer) {
            return redirect()->route('order.cart')
                ->with('error', 'Data customer tidak ditemukan. Silakan login ulang.');
        }

        $produk = Produk::find($id);

        if (! $produk) {
            return redirect()->route('order.cart')->with('error', 'Produk tidak ditemukan.');
        }

        if ($produk->status !== 'aktif') {
            return redirect()->route('order.cart')->with('error', 'Produk tidak tersedia.');
        }

        if ((int) $produk->stok < 1) {
            return redirect()->route('order.cart')->with('error', 'Stok produk habis.');
        }

        try {
            DB::transaction(function () use ($customer, $produk) {
                $order = Order::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'user_id' => Auth::id(),
                        'status' => 'pending',
                    ],
                    [
                        'total_harga' => 0,
                        'total_berat' => 0,
                    ]
                );

                $orderItem = OrderItem::where('order_id', $order->id)
                    ->where('produk_id', $produk->id)
                    ->lockForUpdate()
                    ->first();

                $quantity = $orderItem ? $orderItem->quantity + 1 : 1;

                if ($quantity > $produk->stok) {
                    throw new RuntimeException('Jumlah produk melebihi stok yang tersedia.');
                }

                if ($orderItem) {
                    $orderItem->update([
                        'quantity' => $quantity,
                        'harga' => $produk->harga,
                    ]);
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'produk_id' => $produk->id,
                        'quantity' => 1,
                        'harga' => $produk->harga,
                    ]);
                }

                $this->recalculateOrder($order);
            });
        } catch (RuntimeException $e) {
            return redirect()->route('order.cart')->with('error', $e->getMessage());
        }

        return redirect()->route('order.cart')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function viewCart()
    {
        $order = null;
        $totalHarga = 0;
        $totalBerat = 0;
        $totalQuantity = 0;

        $customer = $this->currentCustomer();

        if ($customer) {
            $order = Order::with(['orderItems.produk'])
                ->where('customer_id', $customer->id)
                ->where('user_id', Auth::id())
                ->where('status', 'pending')
                ->latest('updated_at')
                ->first();

            if ($order) {
                foreach ($order->orderItems as $item) {
                    $totalHarga += $item->harga * $item->quantity;
                    $totalBerat += ($item->produk->berat ?? 0) * $item->quantity;
                    $totalQuantity += $item->quantity;
                }
            }
        }

        return view('v_order.cart', [
            'judul' => 'Keranjang Belanja',
            'order' => $order,
            'totalHarga' => $totalHarga,
            'totalBerat' => $totalBerat,
            'totalQuantity' => $totalQuantity,
        ]);
    }

    public function updateCart(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ], [
            'quantity.required' => 'Jumlah produk wajib diisi.',
            'quantity.integer' => 'Jumlah produk harus berupa angka.',
            'quantity.min' => 'Jumlah produk minimal 1.',
        ]);

        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order) {
            return redirect()->route('order.cart')
                ->with('error', 'Keranjang tidak ditemukan.');
        }

        $item = $order->orderItems()->whereKey($id)->first();

        if (! $item) {
            return redirect()->route('order.cart')
                ->with('error', 'Item keranjang tidak ditemukan atau bukan milik Anda.');
        }

        try {
            DB::transaction(function () use ($order, $id, $validated) {
                $item = OrderItem::where('order_id', $order->id)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (! $item) {
                    throw new RuntimeException('Item keranjang tidak ditemukan.');
                }

                $produk = Produk::whereKey($item->produk_id)->lockForUpdate()->first();

                if (! $produk) {
                    throw new RuntimeException('Produk tidak ditemukan.');
                }

                if ((int) $validated['quantity'] > $produk->stok) {
                    throw new RuntimeException('Jumlah produk melebihi stok yang tersedia.');
                }

                $item->update([
                    'quantity' => $validated['quantity'],
                    'harga' => $produk->harga,
                ]);

                $this->recalculateOrder($order);
            });
        } catch (RuntimeException $e) {
            return redirect()->route('order.cart')->with('error', $e->getMessage());
        }

        return redirect()->route('order.cart')
            ->with('success', 'Jumlah produk berhasil diperbarui.');
    }

    public function removeFromCart($id)
    {
        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order) {
            return redirect()->route('order.cart')
                ->with('error', 'Keranjang tidak ditemukan.');
        }

        $item = $order->orderItems()->whereKey($id)->first();

        if (! $item) {
            return redirect()->route('order.cart')
                ->with('error', 'Item keranjang tidak ditemukan atau bukan milik Anda.');
        }

        DB::transaction(function () use ($order, $id) {
            $item = OrderItem::where('order_id', $order->id)
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($item) {
                $item->delete();
            }

            $this->recalculateOrder($order);

            if ($order->orderItems()->count() === 0) {
                $order->delete();
            }
        });

        return redirect()->route('order.cart')
            ->with('success', 'Produk berhasil dihapus dari keranjang.');
    }

    public function selectShipping(Request $request)
    {
        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order || $order->orderItems()->count() === 0) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        $order->load('orderItems.produk');
        [$totalHarga, $totalBerat, $totalQuantity] = $this->orderTotals($order);

        return view('v_order.select_shipping', [
            'judul' => 'Pilih Pengiriman',
            'order' => $order,
            'totalHarga' => $totalHarga,
            'totalBerat' => $totalBerat,
            'totalQuantity' => $totalQuantity,
            'originCityId' => (string) config('services.rajaongkir.origin_city_id', '137'),
            'originCityName' => (string) config('services.rajaongkir.origin_city_name', 'Jakarta Pusat'),
        ]);
    }

    public function updateOngkir(Request $request)
    {
        $validated = $request->validate([
            'destination_city_id' => ['required', 'integer', 'min:1'],
            'city_name' => ['required', 'string', 'max:100'],
            'province_name' => ['required', 'string', 'max:100'],
            'kurir' => ['required', 'string', 'max:20', 'regex:/^[a-z]+$/i'],
            'layanan_ongkir' => ['required', 'string', 'max:50'],
            'alamat' => ['required', 'string', 'max:500'],
            'pos' => ['required', 'string', 'max:10'],
        ], [
            'destination_city_id.required' => 'Kota tujuan wajib dipilih.',
            'destination_city_id.integer' => 'Kota tujuan tidak valid.',
            'city_name.required' => 'Nama kota wajib diisi.',
            'province_name.required' => 'Nama provinsi wajib diisi.',
            'kurir.required' => 'Kurir wajib dipilih.',
            'kurir.regex' => 'Kode kurir tidak valid.',
            'layanan_ongkir.required' => 'Layanan pengiriman wajib dipilih.',
            'alamat.required' => 'Alamat lengkap wajib diisi.',
            'pos.required' => 'Kode pos wajib diisi.',
        ]);

        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order || $order->orderItems()->count() === 0) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        if ((int) $order->total_berat < 1) {
            return back()->withInput()->with(
                'error',
                'Berat total pesanan tidak valid. Periksa berat produk terlebih dahulu.'
            );
        }

        $costRequest = Request::create('/cost', 'POST', [
            'origin' => (string) config('services.rajaongkir.origin_city_id', '137'),
            'destination' => (string) $validated['destination_city_id'],
            'weight' => (int) $order->total_berat,
            'courier' => strtolower($validated['kurir']),
        ]);
        $costRequest->headers->set('Accept', 'application/json');

        $costResponse = app(RajaOngkirController::class)->getCost($costRequest);

        $costPayload = json_decode($costResponse->getContent(), true);

        if ($costResponse->getStatusCode() !== 200 || ($costPayload['status']['code'] ?? null) !== 200) {
            return back()->withInput()->with(
                'error',
                $costPayload['message'] ?? 'Gagal menghitung ongkos kirim. Silakan coba lagi.'
            );
        }

        $selected = collect($costPayload['data'] ?? [])->first(function (array $item) use ($validated): bool {
            return strcasecmp((string) ($item['service'] ?? ''), (string) $validated['layanan_ongkir']) === 0
                && strcasecmp((string) ($item['courier'] ?? ''), (string) $validated['kurir']) === 0;
        });

        if (! $selected) {
            return back()->withInput()->with(
                'error',
                'Layanan pengiriman yang dipilih tidak tersedia. Silakan cek ongkir ulang.'
            );
        }

        $order->update([
            'kurir' => strtoupper((string) $selected['courier']),
            'layanan_ongkir' => (string) $selected['service'],
            'biaya_ongkir' => (int) $selected['cost'],
            'estimasi_ongkir' => (string) $selected['etd'],
            'total_berat' => (int) $order->total_berat,
            'alamat' => trim($validated['alamat']).', '.$validated['city_name'].', '.$validated['province_name'],
            'pos' => $validated['pos'],
        ]);

        return redirect()->route('order.selectPayment')
            ->with('success', 'Data pengiriman berhasil disimpan.');
    }

    public function selectPayment()
    {
        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order || $order->orderItems()->count() === 0) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        if (! $order->kurir || $order->biaya_ongkir === null) {
            return redirect()->route('order.cart')
                ->with('error', 'Pilih pengiriman terlebih dahulu sebelum melakukan pembayaran.');
        }

        $order->load('orderItems.produk');
        [$subtotal] = $this->orderTotals($order);
        $biayaOngkir = (int) $order->biaya_ongkir;

        return view('v_order.selectpayment', [
            'judul' => 'Pembayaran',
            'order' => $order,
            'subtotal' => $subtotal,
            'biayaOngkir' => $biayaOngkir,
            'totalBayar' => $subtotal + $biayaOngkir,
        ]);
    }

    public function checkout(Request $request)
    {
        $customer = $this->currentCustomer();
        $order = $customer ? $this->pendingOrder($customer) : null;

        if (! $order || $order->orderItems()->count() === 0) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        if (! $order->kurir || $order->biaya_ongkir === null) {
            return redirect()->route('order.cart')
                ->with('error', 'Pilih pengiriman terlebih dahulu sebelum melakukan pembayaran.');
        }

        try {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->first();

                if (! $lockedOrder || $lockedOrder->status !== 'pending') {
                    throw new RuntimeException('Pesanan tidak ditemukan atau sudah diproses.');
                }

                $items = $lockedOrder->orderItems()->with('produk')->get();

                if ($items->isEmpty()) {
                    throw new RuntimeException('Keranjang belanja kosong.');
                }

                foreach ($items as $item) {
                    $produk = Produk::whereKey($item->produk_id)->lockForUpdate()->first();

                    if (! $produk || $produk->status !== 'aktif') {
                        throw new RuntimeException('Produk '.($item->produk->nama_produk ?? '').' tidak tersedia.');
                    }

                    if ((int) $produk->stok < (int) $item->quantity) {
                        throw new RuntimeException('Stok produk '.$produk->nama_produk.' tidak mencukupi.');
                    }

                    $produk->update([
                        'stok' => (int) $produk->stok - (int) $item->quantity,
                    ]);
                }

                $this->recalculateOrder($lockedOrder);
                $lockedOrder->update(['status' => 'Paid']);
            });
        } catch (RuntimeException $e) {
            return redirect()->route('order.cart')->with('error', $e->getMessage());
        }

        return redirect()->route('order.history')
            ->with('success', 'Pembayaran berhasil. Pesanan Anda sedang diproses.');
    }

    public function orderHistory()
    {
        $customer = $this->currentCustomer();

        if (! $customer) {
            return redirect()->route('beranda')->with('error', 'Data customer tidak ditemukan.');
        }

        $orders = Order::with(['orderItems.produk'])
            ->where('customer_id', $customer->id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['Paid', 'Kirim', 'Selesai'])
            ->orderByDesc('id')
            ->get();

        return view('v_order.history', [
            'judul' => 'History Pesanan',
            'orders' => $orders,
        ]);
    }

    private function orderTotals(Order $order): array
    {
        $totalHarga = 0;
        $totalBerat = 0;
        $totalQuantity = 0;

        foreach ($order->orderItems as $item) {
            $totalHarga += $item->harga * $item->quantity;
            $totalBerat += ($item->produk->berat ?? 0) * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        return [$totalHarga, $totalBerat, $totalQuantity];
    }

    private function currentCustomer(): ?Customer
    {
        return Customer::where('user_id', Auth::id())->first();
    }

    private function pendingOrder(Customer $customer): ?Order
    {
        return Order::where('customer_id', $customer->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest('updated_at')
            ->first();
    }

    private function recalculateOrder(Order $order): void
    {
        $totalHarga = 0;
        $totalBerat = 0;

        $items = $order->orderItems()->with('produk')->get();

        foreach ($items as $item) {
            $totalHarga += $item->harga * $item->quantity;
            $totalBerat += ($item->produk->berat ?? 0) * $item->quantity;
        }

        $order->update([
            'total_harga' => $totalHarga,
            'total_berat' => $totalBerat,
        ]);
    }
}
