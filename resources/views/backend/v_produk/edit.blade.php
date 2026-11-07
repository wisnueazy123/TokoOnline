@extends('backend.v_layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card">
                <form action="{{ route('backend.produk.update', $row->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <h4 class="card-title">{{ $judul }}</h4>

                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Nama Produk -->
                        <div class="form-group">
                            <label>Nama Produk</label>
                            <input type="text"
                                   name="nama_produk"
                                   class="form-control"
                                   value="{{ old('nama_produk', $row->nama_produk) }}">
                        </div>

                        <!-- Kategori -->
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori_id" class="form-control">
                                @foreach ($kategori as $k)
                                <option value="{{ $k->id }}" @selected(old('kategori_id', $row->kategori_id) == $k->id)>
                                    {{ $k->nama_kategori }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Harga -->
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="number"
                                   name="harga"
                                   class="form-control"
                                   value="{{ old('harga', $row->harga) }}">
                        </div>

                        <!-- Stok -->
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number"
                                   name="stok"
                                   class="form-control"
                                   value="{{ old('stok', $row->stok) }}">
                        </div>

                        <!-- Berat -->
                        <div class="form-group">
                            <label>Berat (Gram)</label>
                            <input type="number"
                                   name="berat"
                                   class="form-control"
                                   value="{{ old('berat', $row->berat) }}">
                        </div>

                        <!-- Detail -->
                        <div class="form-group">
                            <label>Detail Produk</label>
                            <textarea name="detail"
                                      rows="4"
                                      class="form-control">{{ old('detail', $row->detail) }}</textarea>
                        </div>

                        <!-- Foto -->
                        <div class="form-group">
                            <label>Foto Produk</label>
                            <br>
                            <img src="{{ asset('storage/img-produk/thumb_md_' . ($row->foto ?: 'no-image.jpg')) }}"
                                 alt="{{ $row->nama_produk }}"
                                 style="width:120px;height:160px;object-fit:cover;border-radius:4px;margin-bottom:10px;">
                            <br>
                            <input type="file" name="foto" class="form-control-file">
                            <small class="form-text text-muted">Kosongkan bila tidak ingin mengganti foto.</small>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="aktif" @selected(old('status', $row->status) == 'aktif')>aktif</option>
                                <option value="blok" @selected(old('status', $row->status) == 'blok')>blok</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            Simpan
                        </button>

                        <a href="{{ route('backend.produk.index') }}"
                           class="btn btn-secondary">
                            Kembali
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection
