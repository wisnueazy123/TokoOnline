@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        <div class="card">
            <div class="card-body">

                <h5 class="card-title">{{ $judul }}</h5>
                <p class="mb-3">
                    Produk: <strong>{{ $row->nama_produk }}</strong>
                </p>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <label>Foto Utama</label>
                                <br>
                                <img src="{{ asset('storage/img-produk/thumb_md_' . ($row->foto ?: 'no-image.jpg')) }}"
                                     alt="{{ $row->nama_produk }}"
                                     style="width:100%;max-width:220px;height:280px;object-fit:cover;border-radius:4px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <form action="{{ route('backend.foto_produk.store') }}"
                              method="POST"
                              enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="produk_id" value="{{ $row->id }}">

                            <div class="form-group">
                                <label>Tambah Foto</label>
                                <input type="file" name="foto" class="form-control-file" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-plus"></i> Simpan Foto
                            </button>

                            <a href="{{ route('backend.produk.index') }}" class="btn btn-secondary">
                                Kembali
                            </a>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <h5 class="card-title">Foto Tambahan ({{ $fotoProdukTambahan->count() }})</h5>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Nama File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fotoProdukTambahan as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <img src="{{ asset('storage/img-produk/' . $item->foto) }}"
                                         alt="{{ $row->nama_produk }}"
                                         style="width:80px;height:80px;object-fit:cover;border-radius:4px;">
                                </td>
                                <td>{{ $item->foto }}</td>
                                <td>
                                    <form action="{{ route('backend.foto_produk.destroy', $item->id) }}"
                                          method="POST"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-danger btn-sm show_confirm"
                                                data-konf-delete="{{ $item->foto }}">
                                            <i class="mdi mdi-delete"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">Belum ada foto tambahan.</td>
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
