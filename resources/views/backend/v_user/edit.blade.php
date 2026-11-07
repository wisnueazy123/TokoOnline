@extends('backend.v_layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">

                <form action="{{ route('backend.user.update', $edit->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <h4 class="card-title">{{ $judul }}</h4>

                        <div class="form-group">
                            <label>Hak Akses</label>
                            <select name="role" class="form-control">
                                <option value="superadmin" {{ $edit->role == 'superadmin' ? 'selected' : '' }}>
                                    Super Admin
                                </option>
                                <option value="admin" {{ $edit->role == 'admin' ? 'selected' : '' }}>
                                    Admin
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control"
                                value="{{ $edit->nama }}">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="email" class="form-control"
                                value="{{ $edit->email }}">
                        </div>

                        <div class="form-group">
                            <label>HP</label>
                            <input type="text" name="hp" class="form-control"
                                value="{{ $edit->hp }}">
                        </div>
                    </div>

                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            Update
                        </button>

                        <a href="{{ route('backend.user.index') }}" class="btn btn-secondary">
                            Kembali
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
