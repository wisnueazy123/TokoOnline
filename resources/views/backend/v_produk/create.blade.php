@extends('backend.v_layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card">
                <form action="{{ route('backend.produk.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">
                        <h4 class="card-title">{{ $judul }}</h4>

                        <!-- Nama Produk -->
                        <div class="form-group">
                            <label>Nama Produk</label>
                            <input type="text"
                                   name="nama_produk"
                                   class="form-control"
                                   placeholder="Masukkan Nama Produk">
                        </div>

                        <!-- Kategori -->
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori_id" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategori as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Harga -->
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="number"
                                   name="harga"
                                   class="form-control"
                                   placeholder="Masukkan Harga">
                        </div>

                        <!-- Stok -->
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number"
                                   name="stok"
                                   class="form-control"
                                   placeholder="Masukkan Stok">
                        </div>

                        <!-- Berat -->
                        <div class="form-group">
                            <label>Berat (Gram)</label>
                            <input type="number"
                                   name="berat"
                                   class="form-control"
                                   placeholder="Masukkan Berat">
                        </div>

                        <!-- Detail -->
                        <div class="form-group">
                            <label>Detail Produk</label>
                            <textarea name="detail"
                                      rows="4"
                                      class="form-control"
                                      placeholder="Masukkan Detail Produk"></textarea>
                        </div>

                        <!-- Foto -->
                        <div class="form-group">
                            <label>Foto Produk</label>
                            <input type="file" name="foto" class="form-control-file">
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="aktif">aktif</option>
                                <option value="blok">blok</option>
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
