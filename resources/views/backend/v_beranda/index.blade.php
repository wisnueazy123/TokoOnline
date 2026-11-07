@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body border-top">

                <h5 class="card-title">{{ $judul }}</h5>

                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading">
                        Selamat Datang, {{ Auth::user()->nama }}
                    </h4>

                    Aplikasi Toko Online dengan hak akses Anda sebagai
                    <b>
                        @if(Auth::user()->role == 'superadmin')
                            Super Admin
                        @elseif(Auth::user()->role == 'admin')
                            Admin
                        @endif
                    </b>

                    <hr>
                    <p class="mb-0">Kuliah..? BSI Aja !!!</p>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
