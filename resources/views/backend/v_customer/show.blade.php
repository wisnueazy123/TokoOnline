@extends('backend.v_layouts.app')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ $judul }}</h5>
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        @php
                            $foto = $customer->user->foto ?? null;
                            $fotoPath = $foto ? asset('storage/img-customer/' . $foto) : asset('assets/images/users/default.jpg');
                        @endphp
                        <img src="{{ $fotoPath }}" alt="{{ $customer->user->nama }}" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                    <div class="col-md-9">
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Nama</th>
                                <td>{{ $customer->user->nama }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $customer->user->email }}</td>
                            </tr>
                            <tr>
                                <th>No. HP</th>
                                <td>{{ $customer->user->hp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>{{ $customer->alamat ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kode Pos</th>
                                <td>{{ $customer->pos ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status User</th>
                                <td>
                                    @if ($customer->user->status == 1)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">NonAktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tanggal Daftar</th>
                                <td>{{ $customer->created_at ? $customer->created_at->format('d-m-Y H:i') : '-' }}</td>
                            </tr>
                        </table>
                        <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="{{ route('backend.customer.edit', $customer->id) }}" class="btn btn-cyan btn-sm">
                            <i class="far fa-edit"></i> Ubah
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
