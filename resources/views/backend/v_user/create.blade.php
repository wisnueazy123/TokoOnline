@Extends('Backend.V_Layouts.App')
@section('content')
<!-- contentAwal -->
<div class="container-fluid">
<div class="row">
<div class="col-12">
<div class="card">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form class="form-horizontal" action="{{ route('backend.user.store') }}"
method="post" enctype="multipart/form-data">
@csrf
<div class="card-body">
<h4 class="card-title"> {{$judul}} </h4>
<div class="row">
<div class="col-md-4">
<div class="form-group">
<label>Foto</label>
<img src="{{ asset('storage/app/img-user/burung.jpg') }}"
class="foto-preview img-thumbnail mb-2"
width="150">
<input type="file" name="foto" class="form-control
@error('foto') is-invalid @enderror" onchange="previewFoto()">
@error('foto')
<div class="invalid-feedback alert-danger">{{ $message
}}</div>
@enderror
</div>
</div>
<div class="col-md-8">
<div class="form-group">
<div class="form-group">
    <label>Hak Akses</label>

    <select name="role"
        class="form-control @error('role') is-invalid @enderror">

        <option value="">
            - Pilih Hak Akses -
        </option>

        <option value="superadmin"
            {{ old('role') == 'superadmin' ? 'selected' : '' }}>
            Super Admin
        </option>

        <option value="admin"
            {{ old('role') == 'admin' ? 'selected' : '' }}>
            Admin
        </option>

    </select>

    @error('role')
        <span class="invalid-feedback alert-danger" role="alert">
            {{ $message }}
        </span>
    @enderror
</div>
<div class="form-group">
<label>Nama</label>
<input type="text" name="nama" value="{{ old('nama')
}}" class="form-control @error('nama') is-invalid @enderror" placeholder="Masukkan Nama">
@error('nama')
<span class="invalid-feedback alert-danger"
role="alert">
{{ $message }}
</span>
@enderror
</div>
<div class="form-group">
<label>Email</label>
<input type="text" name="email" value="{{ old('email')
}}" class="form-control @error('email') is-invalid @enderror" placeholder="Masukkan Email">
@error('email')
<span class="invalid-feedback alert-danger"
role="alert">
{{ $message }}
</span>
@enderror
</div>
<div class="form-group">
<label>HP</label>
<input type="text" onkeypress="return
hanyaAngka(event)" name="hp" value="{{ old('hp') }}" class="form-control @error('hp') isinvalid @enderror" placeholder="Masukkan Nomor HP">
@error('hp')
<span class="invalid-feedback alert-danger"
role="alert">
{{ $message }}
</span>
@enderror
</div>
<div class="form-group">
<label>Password</label>
<input type="password" name="password" class="formcontrol @error('password') is-invalid @enderror" placeholder="Masukkan Password">
@error('password')
<span class="invalid-feedback alert-danger"
role="alert">
{{ $message }}
</span>
@enderror
</div>
<div class="form-group">
    <label>Konfirmasi Password</label>
<input type="password" name="password_confirmation"
class="form-control" placeholder="Konfirmasi Password">
</div>
</div>
</div>
</div>
<div class="border-top">
    <div class="card-body">

        <button type="submit" class="btn btn-primary">
            Simpan
        </button>

        <a href="{{ route('backend.user.index') }}" class="btn btn-secondary">
            Kembali
        </a>

    </div>
</div>
</form>
</div>
</div>
</div>
</div>
<!-- contentAkhir -->
@endsection
