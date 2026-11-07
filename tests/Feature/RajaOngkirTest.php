<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RajaOngkirTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.rajaongkir.key' => 'test-key',
            'services.rajaongkir.base_url' => 'https://rajaongkir.komerce.id/api/v1',
        ]);

        Cache::flush();
    }

    public function test_provinces_komerce_dinormalkan(): void
    {
        Http::fake([
            '*destination/province' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['id' => 6, 'name' => 'DKI Jakarta'],
                    ['id' => 9, 'name' => 'Jawa Barat'],
                ],
            ]),
        ]);

        $response = $this->getJson('/provinces');

        $response->assertOk()
            ->assertJsonPath('status.code', 200)
            ->assertJsonPath('data.0.province_id', '6')
            ->assertJsonPath('data.0.province', 'DKI Jakarta')
            ->assertJsonPath('data.1.province_id', '9');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/destination/province')
            && $request->hasHeader('key', 'test-key'));
    }

    public function test_cities_komerce_dinormalkan(): void
    {
        Http::fake([
            '*destination/city/6' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['id' => 152, 'name' => 'Jakarta Pusat', 'zip_code' => '10110'],
                ],
            ]),
        ]);

        $response = $this->getJson('/cities?province_id=6');

        $response->assertOk()
            ->assertJsonPath('status.code', 200)
            ->assertJsonPath('data.0.city_id', '152')
            ->assertJsonPath('data.0.province_id', '6')
            ->assertJsonPath('data.0.city_name', 'Jakarta Pusat')
            ->assertJsonPath('data.0.postal_code', '10110');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/destination/city/6'));
    }

    public function test_cost_komerce_dinormalkan(): void
    {
        Http::fake([
            '*calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'name' => 'JNE',
                        'code' => 'jne',
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 18000,
                        'etd' => '2-3',
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/cost', [
            'origin' => 152,
            'destination' => 22,
            'weight' => 1000,
            'courier' => 'jne',
        ]);

        $response->assertOk()
            ->assertJsonPath('status.code', 200)
            ->assertJsonPath('data.0.courier', 'jne')
            ->assertJsonPath('data.0.service', 'REG')
            ->assertJsonPath('data.0.cost', 18000)
            ->assertJsonPath('data.0.etd', '2-3');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/calculate/domestic-cost')
            && $request['origin'] === '152'
            && $request['destination'] === '22'
            && (int) $request['weight'] === 1000
            && $request['courier'] === 'jne');
    }

    public function test_starter_rajaongkir_tetap_didukung(): void
    {
        config(['services.rajaongkir.base_url' => 'https://api.rajaongkir.com/starter']);

        Http::fake([
            '*api.rajaongkir.com/starter/province' => Http::response([
                'rajaongkir' => [
                    'status' => ['code' => 200, 'description' => 'OK'],
                    'results' => [
                        ['province_id' => '1', 'province' => 'Bali'],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/provinces');

        $response->assertOk()
            ->assertJsonPath('status.code', 200)
            ->assertJsonPath('data.0.province_id', '1')
            ->assertJsonPath('data.0.province', 'Bali');
    }

    public function test_api_key_kosong_menghasilkan_503(): void
    {
        config(['services.rajaongkir.key' => '']);

        $response = $this->getJson('/provinces');

        $response->assertStatus(503)
            ->assertJsonPath('status.code', 503)
            ->assertJsonPath('message', 'Kunci API RajaOngkir belum diatur. Isi RAJAONGKIR_API_KEY pada berkas .env.');

        Http::assertNothingSent();
    }

    public function test_api_key_tidak_valid_menghasilkan_pesan_jelas(): void
    {
        Http::fake([
            '*destination/province' => Http::response([
                'meta' => ['code' => 401, 'status' => 'error', 'message' => 'Invalid API key'],
            ], 401),
        ]);

        $response = $this->getJson('/provinces');

        $response->assertStatus(502)
            ->assertJsonPath('status.code', 502)
            ->assertJsonPath('message', 'API Key RajaOngkir tidak valid atau tidak memiliki akses. Periksa RAJAONGKIR_API_KEY.');
    }

    public function test_validasi_input_kota_dan_ongkir(): void
    {
        $this->getJson('/cities')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['province_id']);

        $this->postJson('/cost', [
            'origin' => 0,
            'destination' => 0,
            'weight' => 0,
            'courier' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['origin', 'destination', 'weight', 'courier']);

        Http::assertNothingSent();
    }
}
