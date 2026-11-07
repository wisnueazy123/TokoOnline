@extends('v_layouts.app')

@section('content')
<style>
    .shipping-actions {
        display: flex;
        flex-wrap: wrap;
        margin: 20px -5px 0;
    }

    .shipping-actions .shipping-action-btn {
        flex: 1 1 180px;
        margin: 5px;
        text-align: center;
    }

    .shipping-back-btn {
        display: block;
        width: 100%;
        margin-top: 10px;
        text-align: center;
    }
</style>

<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PENGIRIMAN</p>
            <h3 class="title">{{ $judul }}</h3>
        </div>

        @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>{{ session('success') }}</strong>
        </div>
        @endif

        @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>{{ session('error') }}</strong>
        </div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin-bottom: 0;">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="shippingForm" action="{{ route('order.updateongkir') }}" method="post">
            @csrf
            <input type="hidden" name="destination_city_id" id="destinationCityId"
                value="{{ old('destination_city_id') }}">
            <input type="hidden" name="city_name" id="cityName" value="{{ old('city_name') }}">
            <input type="hidden" name="province_name" id="provinceName" value="{{ old('province_name') }}">
            <input type="hidden" name="layanan_ongkir" id="serviceCode" value="{{ old('layanan_ongkir') }}">

            <div class="row">
                <div class="col-md-7">
                    <h4>Alamat Pengiriman</h4>

                    <div class="form-group">
                        <label for="destinationProvince">Provinsi Tujuan</label>
                        <select id="destinationProvince" class="form-control">
                            <option value="">Pilih Provinsi</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="destinationCity">Kota/Kabupaten Tujuan</label>
                        <select id="destinationCity" class="form-control">
                            <option value="">Pilih Kota</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alamat">Alamat Lengkap</label>
                        <textarea name="alamat" id="alamat" rows="3"
                            class="form-control @error('alamat') is-invalid @enderror"
                            placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan">{{ old('alamat') }}</textarea>
                        @error('alamat')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pos">Kode Pos</label>
                                <input type="text" name="pos" id="pos"
                                    class="form-control @error('pos') is-invalid @enderror"
                                    value="{{ old('pos') }}" placeholder="Contoh: 10110">
                                @error('pos')
                                <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Berat Total</label>
                                <input type="text" class="form-control"
                                    value="{{ number_format($totalBerat, 0, ',', '.') }} gram" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="courierSelect">Kurir</label>
                        <select name="kurir" id="courierSelect" class="form-control">
                            <option value="">Pilih Kurir</option>
                            <option value="jne" {{ old('kurir') === 'jne' ? 'selected' : '' }}>JNE</option>
                            <option value="tiki" {{ old('kurir') === 'tiki' ? 'selected' : '' }}>TIKI</option>
                            <option value="pos" {{ old('kurir') === 'pos' ? 'selected' : '' }}>POS Indonesia</option>
                        </select>
                        @error('kurir')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <p class="text-muted">
                        Kota asal pengiriman: <strong>{{ $originCityName }}</strong>
                    </p>

                    <div id="shippingAlert"></div>

                    <div class="shipping-actions">
                        <button type="button" id="checkCostButton" class="primary-btn shipping-action-btn">
                            Cek Ongkir
                        </button>
                        <button type="submit" id="submitShipping" class="primary-btn shipping-action-btn">
                            Lanjut ke Pembayaran
                        </button>
                    </div>
                    <a href="{{ route('order.cart') }}" class="primary-btn shipping-back-btn">
                        Kembali ke Keranjang
                    </a>
                </div>

                <div class="col-md-5">
                    <h4>Ringkasan Belanja</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderItems as $item)
                                <tr>
                                    <td>{{ $item->produk->nama_produk }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-right">
                                        Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-right">Subtotal</th>
                                    <th class="text-right">Rp. {{ number_format($totalHarga, 0, ',', '.') }}</th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-right">Berat</th>
                                    <th class="text-right">{{ number_format($totalBerat, 0, ',', '.') }} gram</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h4>Layanan Pengiriman</h4>
                    <div id="shippingOptions">
                        <p class="text-muted">Pilih provinsi, kota, dan kurir lalu klik Cek Ongkir.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const originCityId = @json($originCityId);
        const orderWeight = {{ (int) $totalBerat }};
        const csrfToken = document.querySelector('input[name="_token"]').value;

        const form = document.getElementById('shippingForm');
        const alertBox = document.getElementById('shippingAlert');
        const destinationProvince = document.getElementById('destinationProvince');
        const destinationCity = document.getElementById('destinationCity');
        const destinationCityId = document.getElementById('destinationCityId');
        const cityName = document.getElementById('cityName');
        const provinceName = document.getElementById('provinceName');
        const courierSelect = document.getElementById('courierSelect');
        const serviceCode = document.getElementById('serviceCode');
        const pos = document.getElementById('pos');
        const checkCostButton = document.getElementById('checkCostButton');
        const shippingOptions = document.getElementById('shippingOptions');

        let cityData = [];
        let selectedShipping = null;

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
            div.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        function resetShippingOptions() {
            selectedShipping = null;
            serviceCode.value = '';
            shippingOptions.textContent = '';

            const info = document.createElement('p');
            info.className = 'text-muted';
            info.textContent = 'Klik Cek Ongkir untuk melihat layanan yang tersedia.';
            shippingOptions.appendChild(info);
        }

        async function loadProvinces() {
            try {
                const response = await fetch('/provinces', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal memuat data provinsi.');
                }

                fillSelect(destinationProvince, data.data, 'province_id', 'province', 'Pilih Provinsi');
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        async function loadCities(provinceId) {
            cityData = [];
            destinationCityId.value = '';
            cityName.value = '';
            provinceName.value = '';
            resetShippingOptions();

            if (!provinceId) {
                fillSelect(destinationCity, [], 'city_id', 'city_name', 'Pilih Kota');
                return;
            }

            try {
                const response = await fetch('/cities?province_id=' + encodeURIComponent(provinceId), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal memuat data kota.');
                }

                cityData = data.data;
                fillSelect(destinationCity, cityData, 'city_id', 'city_name', 'Pilih Kota');
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        destinationProvince.addEventListener('change', function () {
            loadCities(this.value);
        });

        destinationCity.addEventListener('change', function () {
            const selectedCityId = this.value;
            const selected = cityData.find(function (city) {
                return String(city.city_id) === String(selectedCityId);
            });

            destinationCityId.value = selected ? selected.city_id : '';
            cityName.value = selected ? selected.city_name : '';
            provinceName.value = destinationProvince.options[destinationProvince.selectedIndex]
                ? destinationProvince.options[destinationProvince.selectedIndex].text
                : '';

            if (selected && selected.postal_code && !pos.value) {
                pos.value = selected.postal_code;
            }

            resetShippingOptions();
        });

        courierSelect.addEventListener('change', resetShippingOptions);

        function formatRupiah(value) {
            return 'Rp. ' + Number(value || 0).toLocaleString('id-ID');
        }

        function renderShippingOptions(items) {
            shippingOptions.textContent = '';
            selectedShipping = null;
            serviceCode.value = '';

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'alert alert-info';
                empty.textContent = 'Tidak ada layanan pengiriman untuk pilihan tersebut.';
                shippingOptions.appendChild(empty);
                return;
            }

            const table = document.createElement('table');
            table.className = 'table table-bordered table-hover';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');

            ['Pilih', 'Layanan', 'Biaya', 'Estimasi'].forEach(function (label) {
                const th = document.createElement('th');
                th.textContent = label;
                headRow.appendChild(th);
            });

            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            items.forEach(function (item, index) {
                const row = document.createElement('tr');

                const radioCell = document.createElement('td');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'shipping_option';
                radio.value = String(index);
                radio.addEventListener('change', function () {
                    selectedShipping = item;
                    serviceCode.value = item.service;
                    courierSelect.value = item.courier;
                });
                radioCell.appendChild(radio);
                row.appendChild(radioCell);

                row.style.cursor = 'pointer';
                row.addEventListener('click', function (event) {
                    if (event.target === radio) {
                        return;
                    }

                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                });

                [item.service, formatRupiah(item.cost), item.etd ? item.etd + ' hari' : '-']
                    .forEach(function (value) {
                        const cell = document.createElement('td');
                        cell.textContent = value;
                        row.appendChild(cell);
                    });

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            shippingOptions.appendChild(table);
        }

        checkCostButton.addEventListener('click', async function () {
            showAlert('', '');

            if (!destinationCityId.value) {
                showAlert('warning', 'Pilih provinsi dan kota tujuan terlebih dahulu.');
                return;
            }

            if (!courierSelect.value) {
                showAlert('warning', 'Pilih kurir terlebih dahulu.');
                return;
            }

            if (orderWeight < 1) {
                showAlert('warning', 'Berat total pesanan tidak valid. Periksa berat produk.');
                return;
            }

            checkCostButton.disabled = true;
            checkCostButton.textContent = 'Menghitung...';

            try {
                const response = await fetch('/cost', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        origin: originCityId,
                        destination: destinationCityId.value,
                        weight: orderWeight,
                        courier: courierSelect.value
                    })
                });

                const data = await response.json();

                if (!response.ok || data.status.code !== 200) {
                    throw new Error(data.message || 'Gagal menghitung ongkos kirim.');
                }

                renderShippingOptions(data.data);
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                checkCostButton.disabled = false;
                checkCostButton.textContent = 'Cek Ongkir';
            }
        });

        form.addEventListener('submit', function (event) {
            if (!serviceCode.value || !selectedShipping) {
                event.preventDefault();
                showAlert('warning', 'Pilih salah satu layanan pengiriman terlebih dahulu. Klik "Cek Ongkir", lalu pilih layanan pada kolom Pilih.');

                if (shippingOptions.querySelector('input[name="shipping_option"]')) {
                    shippingOptions.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        resetShippingOptions();
        loadProvinces();
    });
</script>
@endsection
