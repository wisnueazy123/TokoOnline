@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        <a href="{{ route('backend.produk.create') }}">
            <button type="button" class="btn btn-primary mb-3">
                <i class="mdi mdi-plus"></i> Tambah Produk
            </button>
        </a>

        <div class="card">
            <div class="card-body">

                <h5 class="card-title">{{ $judul }}</h5>

                <div class="table-responsive">
                    <table id="zero_config" class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Berat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($index as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <img src="{{ asset('storage/img-produk/thumb_sm_' . ($row->foto ?: 'no-image.jpg')) }}"
                                         alt="{{ $row->nama_produk }}"
                                         style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                </td>
                                <td>{{ $row->nama_produk }}</td>
                                <td>{{ $row->kategori->nama_kategori ?? '-' }}</td>
                                <td>Rp {{ number_format($row->harga, 0, ',', '.') }}</td>
                                <td>{{ $row->stok }}</td>
                                <td>{{ $row->berat }} gram</td>
                                <td>
                                    @if ($row->status == 'aktif')
                                    <span class="badge bg-success">aktif</span>
                                    @else
                                    <span class="badge bg-secondary">blok</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('backend.produk.edit', $row->id) }}"
                                       class="btn btn-warning btn-sm text-white">
                                        <i class="mdi mdi-pencil"></i> Ubah
                                    </a>

                                    <a href="{{ route('backend.produk.foto', $row->id) }}"
                                       class="btn btn-info btn-sm text-white">
                                        <i class="mdi mdi-plus"></i> Gambar
                                    </a>

                                    <form action="{{ route('backend.produk.destroy', $row->id) }}"
                                          method="POST"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-danger btn-sm show_confirm"
                                                data-konf-delete="{{ $row->nama_produk }}">
                                            <i class="mdi mdi-delete"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">Belum ada data produk.</td>
                            </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

@endsection
