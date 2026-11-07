@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">

        <a href="{{ route('backend.kategori.create') }}">
            <button class="btn btn-primary mb-3">
                + Tambah Kategori
            </button>
        </a>

        <div class="card">
            <div class="card-body">

                <h5>{{ $judul }}</h5>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($index as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->nama_kategori }}</td>
                            <td>
                                <form action="{{ route('backend.kategori.destroy', $row->id) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>
        </div>
    </div>
</div>
@endsection
