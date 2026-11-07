@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">

            <form action="{{ route('backend.kategori.store') }}"
                  method="POST">

                @csrf

                <div class="card-body">
                    <h4 class="card-title">{{ $judul }}</h4>

                    <div class="form-group">
                        <label>Nama Kategori</label>

                        <input type="text"
                               name="nama_kategori"
                               value="{{ old('nama_kategori') }}"
                               class="form-control @error('nama_kategori') is-invalid @enderror"
                               placeholder="Masukkan Nama Kategori">

                        @error('nama_kategori')
                            <span class="invalid-feedback d-block">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="border-top">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            Simpan
                        </button>

                        <a href="{{ route('backend.kategori.index') }}"
                           class="btn btn-secondary">
                            Kembali
                        </a>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
