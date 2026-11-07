@extends('v_layouts.app')

@section('content')
<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>ONGKOS KIRIM</p>
            <h3 class="title">{{ $judul }}</h3>
        </div>

        <div id="ongkirAlert"></div>

        <form id="ongkirForm">
            @csrf
            <input type="hidden" id="csrf-token" value="{{ csrf_token() }}">

            <div class="row">
                <div class="col-md-6">
                    <h4>Kota Asal</h4>
                    <div class="form-group">
                        <label for="originProvince">Provinsi</label>
                        <select id="originProvince" class="form-control">
                            <option value="">Pilih Provinsi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="originCity">Kota/Kabupaten</label>
                        <select id="originCity" class="form-control">
                            <option value="">Pilih Kota</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <h4>Kota Tujuan</h4>
                    <div class="form-group">
                        <label for="destinationProvince">Provinsi</label>
                        <select id="destinationProvince" class="form-control">
                            <option value="">Pilih Provinsi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="destinationCity">Kota/Kabupaten</label>
                        <select id="destinationCity" class="form-control">
                            <option value="">Pilih Kota</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weight">Berat Barang (gram)</label>
                        <input type="number" id="weight" class="form-control" min="1" max="1000000"
                            value="{{ request('weight') }}" placeholder="Contoh: 1000">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="courier">Kurir</label>
                        <select id="courier" class="form-control">
                            <option value="">Pilih Kurir</option>
                            <option value="jne">JNE</option>
                            <option value="tiki">TIKI</option>
                            <option value="pos">POS Indonesia</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" id="submitOngkir" class="primary-btn" style="width: 100%;">
                            Cek Ongkir
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div id="result" class="table-responsive" style="margin-top: 20px;"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.getElementById('csrf-token').value;
        const form = document.getElementById('ongkirForm');
        const submitButton = document.getElementById('submitOngkir');
        const result = document.getElementById('result');
        const alertBox = document.getElementById('ongkirAlert');

        const originProvince = document.getElementById('originProvince');
        const originCity = document.getElementById('originCity');
        const destinationProvince = document.getElementById('destinationProvince');
        const destinationCity = document.getElementById('destinationCity');
        const weight = document.getElementById('weight');
        const courier = document.getElementById('courier');

        function showAlert(type, message) {
            alertBox.textContent = '';

            if (!message) {
                return;
            }

            const div = document.createElement('div');
            div.className = 'alert alert-' + type;
            div.setAttribute('role', 'alert');
            div.textContent = message;
            alertBox.appendChild(div);
        }

        function fillSelect(select, items, valueKey, textKey, placeholder) {
            select.textContent = '';

            const first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);

            items.forEach(function (item) {
                const option = document.createElement('option');
                option.value = item[valueKey];
                option.textContent = item[textKey];
                select.appendChild(option);
            });
        }

        async function loadProvinces(select) {
            try {
                const response = await fetch('/provinces', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal memuat data provinsi.');
                }

                fillSelect(select, data.data, 'province_id', 'province', 'Pilih Provinsi');
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        async function loadCities(provinceId, select) {
            if (!provinceId) {
                fillSelect(select, [], 'city_id', 'city_name', 'Pilih Kota');
                return;
            }

            try {
                const response = await fetch('/cities?province_id=' + encodeURIComponent(provinceId), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal memuat data kota.');
                }

                fillSelect(select, data.data, 'city_id', 'city_name', 'Pilih Kota');
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        originProvince.addEventListener('change', function () {
            loadCities(this.value, originCity);
        });

        destinationProvince.addEventListener('change', function () {
            loadCities(this.value, destinationCity);
        });

        function formatRupiah(value) {
            return 'Rp. ' + Number(value || 0).toLocaleString('id-ID');
        }

        function renderCosts(items) {
            result.textContent = '';

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'alert alert-info';
                empty.textContent = 'Tidak ada layanan pengiriman yang tersedia untuk pilihan tersebut.';
                result.appendChild(empty);
                return;
            }

            const table = document.createElement('table');
            table.className = 'table table-bordered table-striped';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['Kurir', 'Layanan', 'Deskripsi', 'Biaya', 'Estimasi'].forEach(function (label) {
                const th = document.createElement('th');
                th.textContent = label;
                headRow.appendChild(th);
            });

            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            items.forEach(function (item) {
                const row = document.createElement('tr');

                [
                    item.name || item.courier,
                    item.service,
                    item.description,
                    formatRupiah(item.cost),
                    item.etd ? item.etd + ' hari' : '-'
                ].forEach(function (value) {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.appendChild(cell);
                });

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            result.appendChild(table);
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            showAlert('', '');
            result.textContent = '';

            if (!originCity.value || !destinationCity.value || !weight.value || !courier.value) {
                showAlert('warning', 'Lengkapi kota asal, kota tujuan, berat barang, dan kurir terlebih dahulu.');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';

            try {
                const response = await fetch('/cost', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        origin: originCity.value,
                        destination: destinationCity.value,
                        weight: weight.value,
                        courier: courier.value
                    })
                });

                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal menghitung ongkos kirim.');
                }

                renderCosts(data.data);
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Cek Ongkir';
            }
        });

        loadProvinces(originProvince);
        loadProvinces(destinationProvince);
    });
</script>
@endsection
