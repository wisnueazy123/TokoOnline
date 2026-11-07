@extends('v_layouts.app')

@section('content')
<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>RIWAYAT</p>
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

        @if ($orders->count() > 0)
        @foreach ($orders as $order)
        @php
        $status = strtolower($order->status);
        $statusLabel = match ($status) {
        'paid' => 'Diproses',
        'kirim' => 'Dikirim',
        'selesai' => 'Selesai',
        default => ucfirst($order->status),
        };
        $statusClass = match ($status) {
        'paid' => 'label-info',
        'kirim' => 'label-primary',
        'selesai' => 'label-success',
        default => 'label-default',
        };
        $subtotal = $order->orderItems->sum(fn ($item) => $item->harga * $item->quantity);
        $totalBayar = $subtotal + (int) $order->biaya_ongkir;
        @endphp

        <div class="panel panel-default" style="margin-bottom: 20px;">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Pesanan #{{ $order->id }}</strong>
                        <br>
                        <small class="text-muted">{{ $order->created_at->format('d M Y H:i') }}</small>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="label {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>
            <div class="panel-body">
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
                                <td>{{ $item->produk->nama_produk }}</td>
                                <td class="text-center">Rp. {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">
                                    Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Subtotal</th>
                                <th class="text-right">Rp. {{ number_format($subtotal, 0, ',', '.') }}</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-right">Ongkos Kirim</th>
                                <th class="text-right">
                                    Rp. {{ number_format((int) $order->biaya_ongkir, 0, ',', '.') }}
                                </th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-right">Total Bayar</th>
                                <th class="text-right">Rp. {{ number_format($totalBayar, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <strong>Pengiriman</strong>
                        <address>
                            {{ $order->alamat }}<br>
                            Kode Pos: {{ $order->pos }}<br>
                            Kurir: {{ $order->kurir }} - {{ $order->layanan_ongkir }}<br>
                            Estimasi: {{ $order->estimasi_ongkir ?: '-' }} hari<br>
                            Berat: {{ number_format((int) $order->total_berat, 0, ',', '.') }} gram
                        </address>
                    </div>
                    <div class="col-md-5">
                        <strong>Nomor Resi</strong>
                        <p>{{ $order->noresi ?: 'Belum tersedia' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @else
        <div class="alert alert-info">
            <i class="fa fa-shopping-bag"></i> Belum ada pesanan yang tersimpan.
        </div>
        <a href="{{ route('produk.all') }}" class="primary-btn">Lihat Produk</a>
        @endif
    </div>
</div>
@endsection
