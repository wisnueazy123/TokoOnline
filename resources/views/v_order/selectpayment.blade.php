@extends('v_layouts.app')

@section('content')
<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PEMBAYARAN</p>
            <h3 class="title">{{ $judul }}</h3>
        </div>

        @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>{{ session('success') }}</strong>
        </div>
        @endif

        @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>{{ session('error') }}</strong>
        </div>
        @endif

        <div class="row">
            <div class="col-md-7">
                <h4>Produk Pesanan</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->orderItems as $item)
                            <tr>
                                <td>
                                    {{ $item->produk->nama_produk }}
                                    <br>
                                    <small class="text-muted">{{ $item->produk->berat }} gram / item</small>
                                </td>
                                <td class="text-center">Rp. {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">
                                    Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4>Alamat Pengiriman</h4>
                <address>
                    <strong>{{ $order->customer->user->nama ?? '' }}</strong><br>
                    {{ $order->alamat }}<br>
                    Kode Pos: {{ $order->pos }}<br>
                    Kurir: {{ $order->kurir }} - {{ $order->layanan_ongkir }}<br>
                    Estimasi: {{ $order->estimasi_ongkir ?: '-' }} hari
                </address>
            </div>

            <div class="col-md-5">
                <h4>Ringkasan Pembayaran</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td>Subtotal Produk</td>
                                <td class="text-right">Rp. {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Ongkos Kirim</td>
                                <td class="text-right">Rp. {{ number_format($biayaOngkir, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Total Bayar</th>
                                <th class="text-right">Rp. {{ number_format($totalBayar, 0, ',', '.') }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info">
                    Payment gateway akan diintegrasikan pada pertemuan berikutnya. Untuk saat ini tombol
                    <strong>Bayar</strong> akan mengonfirmasi pesanan dan menyimpannya ke history pesanan.
                </div>

                <form action="{{ route('order.checkout') }}" method="post"
                    onsubmit="return confirm('Konfirmasi pembayaran pesanan ini?');">
                    @csrf
                    <button type="submit" class="primary-btn" style="width: 100%;">Bayar</button>
                </form>

                <div style="margin-top: 10px;">
                    <a href="{{ route('order.selectShipping') }}" class="primary-btn">Kembali ke Pengiriman</a>
                    <a href="{{ route('order.cart') }}" class="primary-btn">Kembali ke Keranjang</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
