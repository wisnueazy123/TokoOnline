@extends('v_layouts.app')

@section('content')
<!-- template -->

<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>KERANJANG</p>
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

        @if ($order && $order->orderItems->count() > 0)
        <div class="table-responsive">
            <table class="shopping-cart-table table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th></th>
                        <th class="text-center">Harga</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Total</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->orderItems as $item)
                    <tr>
                        <td class="thumb">
                            <img src="{{ asset('storage/img-produk/thumb_sm_' . ($item->produk->foto ?: 'no-image.jpg')) }}"
                                alt="{{ $item->produk->nama_produk }}">
                        </td>
                        <td class="details">
                            <a href="{{ route('produk.detail', $item->produk->id) }}">{{ $item->produk->nama_produk }}</a>
                            <ul>
                                <li><span>Berat: {{ $item->produk->berat }} Gram</span></li>
                            </ul>
                            <ul>
                                <li><span>Stok: {{ $item->produk->stok }}</span></li>
                            </ul>
                        </td>
                        <td class="price text-center">
                            <strong>Rp. {{ number_format($item->harga, 0, ',', '.') }}</strong>
                        </td>
                        <td class="qty text-center">
                            <form action="{{ route('order.updateCart', $item->id) }}" method="post">
                                @csrf
                                @method('put')
                                <input type="number" name="quantity" class="input" value="{{ $item->quantity }}"
                                    min="1" max="{{ $item->produk->stok }}" style="width: 70px; display: inline-block;">
                                <button type="submit" class="btn btn-sm btn-warning">Update</button>
                            </form>
                        </td>
                        <td class="total text-center">
                            <strong class="primary-color">Rp.
                                {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</strong>
                        </td>
                        <td class="text-right">
                            <form action="{{ route('order.removeFromCart', $item->id) }}" method="post"
                                onsubmit="return confirm('Hapus produk ini dari keranjang?')">
                                @csrf
                                @method('delete')
                                <button type="submit" class="main-btn icon-btn" title="Hapus dari keranjang">
                                    <i class="fa fa-close"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total Berat</th>
                        <th class="text-center">{{ number_format($totalBerat, 0, ',', '.') }} Gram</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">Total Harga</th>
                        <th class="total text-center">Rp. {{ number_format($totalHarga, 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pull-left">
            <a href="{{ route('produk.all') }}" class="primary-btn">Lanjutkan Belanja</a>
            @if ($totalBerat > 0)
            <a href="{{ route('ongkir.index', ['weight' => $totalBerat]) }}" class="primary-btn"
                style="margin-left: 8px;">Cek Ongkir</a>
            @endif
        </div>
        <div class="pull-right">
            <form action="{{ route('order.selectShipping') }}" method="post" style="display: inline-block;">
                @csrf
                <input type="hidden" name="total_price" value="{{ $totalHarga }}">
                <input type="hidden" name="total_weight" value="{{ $totalBerat }}">
                <button type="submit" class="primary-btn">Pilih Pengiriman</button>
            </form>
        </div>
        <div class="clearfix"></div>
        <p class="text-muted text-right" style="margin-top: 8px;">
            {{ $totalQuantity }} item dalam keranjang
        </p>
        @else
        <div class="alert alert-info">
            <i class="fa fa-shopping-cart"></i> Keranjang belanja kosong.
        </div>
        <a href="{{ route('produk.all') }}" class="primary-btn">Lihat Produk</a>
        @endif
    </div>
</div>

<!-- end template-->
@endsection
