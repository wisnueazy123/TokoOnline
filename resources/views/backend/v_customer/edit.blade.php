@extends('backend.v_layouts.app')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <form action="{{ route('backend.customer.update', $edit->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <h5 class="card-title">{{ $judul }}</h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                               value="{{ old('nama', $edit->user->nama) }}">
                        @error('nama')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $edit->user->email) }}">
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="hp" class="form-control @error('hp') is-invalid @enderror"
                               value="{{ old('hp', $edit->user->hp) }}">
                        @error('hp')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="3" class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $edit->alamat) }}</textarea>
                        @error('alamat')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Kode Pos</label>
                        <input type="text" name="pos" class="form-control @error('pos') is-invalid @enderror"
                               value="{{ old('pos', $edit->pos) }}">
                        @error('pos')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Foto</label>
                        @if ($edit->user->foto)
                            <br>
                            <img src="{{ asset('storage/img-customer/' . $edit->user->foto) }}"
                                 alt="{{ $edit->user->nama }}"
                                 style="width:120px;height:160px;object-fit:cover;border-radius:4px;margin-bottom:10px;">
                            <br>
                        @endif
                        <input type="file" name="foto" class="form-control-file @error('foto') is-invalid @enderror">
                        <small class="form-text text-muted">Kosongkan bila tidak ingin mengganti foto (jpeg, jpg, png, gif, maks 1024 KB).</small>
                        @error('foto')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="border-top">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
