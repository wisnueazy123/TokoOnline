<?php

namespace App\Http\Controllers;

use App\Exceptions\RajaOngkirException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RajaOngkirController extends Controller
{
    public function getProvinces(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $provinces = Cache::remember(
                $this->cacheKey('provinces'),
                now()->addDay(),
                fn (): array => $this->fetchProvinces()
            );

            return $this->success($provinces);
        });
    }

    public function getCities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer', 'min:1'],
        ], [
            'province_id.required' => 'ID provinsi wajib diisi.',
            'province_id.integer' => 'ID provinsi harus berupa angka.',
            'province_id.min' => 'ID provinsi tidak valid.',
        ]);

        $provinceId = (string) $validated['province_id'];

        return $this->handle(function () use ($provinceId): JsonResponse {
            $cities = Cache::remember(
                $this->cacheKey('cities.'.$provinceId),
                now()->addDay(),
                fn (): array => $this->fetchCities($provinceId)
            );

            return $this->success($cities);
        });
    }

    public function getCost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin' => ['required', 'integer', 'min:1'],
            'destination' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'integer', 'min:1', 'max:1000000'],
            'courier' => ['required', 'string', 'max:50', 'regex:/^[a-z]+(?::[a-z]+)*$/i'],
        ], [
            'origin.required' => 'Kota asal wajib dipilih.',
            'origin.integer' => 'Kota asal tidak valid.',
            'destination.required' => 'Kota tujuan wajib dipilih.',
            'destination.integer' => 'Kota tujuan tidak valid.',
            'weight.required' => 'Berat barang wajib diisi.',
            'weight.integer' => 'Berat barang harus berupa angka.',
            'weight.min' => 'Berat barang minimal 1 gram.',
            'weight.max' => 'Berat barang maksimal 1.000.000 gram.',
            'courier.required' => 'Kurir wajib dipilih.',
            'courier.regex' => 'Kode kurir tidak valid.',
        ]);

        return $this->handle(function () use ($validated): JsonResponse {
            $costs = $this->fetchCosts(
                (string) $validated['origin'],
                (string) $validated['destination'],
                (int) $validated['weight'],
                strtolower((string) $validated['courier'])
            );

            return $this->success($costs);
        });
    }

    private function fetchProvinces(): array
    {
        if ($this->isKomerce()) {
            $payload = $this->request(
                fn (): Response => $this->client()->get($this->baseUrl().'/destination/province')
            );

            return collect($this->dataItems($payload))
                ->map(fn (array $item): array => [
                    'province_id' => (string) ($item['id'] ?? ''),
                    'province' => (string) ($item['name'] ?? ''),
                ])
                ->filter(fn (array $item): bool => $item['province_id'] !== '' && $item['province'] !== '')
                ->values()
                ->all();
        }

        $payload = $this->request(
            fn (): Response => $this->client()->get($this->baseUrl().'/province')
        );

        $items = $payload['rajaongkir']['results'] ?? null;

        if (! is_array($items)) {
            throw new RajaOngkirException('Format respons provinsi RajaOngkir tidak dikenali.');
        }

        return collect($items)
            ->map(fn (array $item): array => [
                'province_id' => (string) ($item['province_id'] ?? ''),
                'province' => (string) ($item['province'] ?? ''),
            ])
            ->filter(fn (array $item): bool => $item['province_id'] !== '' && $item['province'] !== '')
            ->values()
            ->all();
    }

    private function fetchCities(string $provinceId): array
    {
        if ($this->isKomerce()) {
            $payload = $this->request(
                fn (): Response => $this->client()->get($this->baseUrl().'/destination/city/'.$provinceId)
            );

            return collect($this->dataItems($payload))
                ->map(fn (array $item): array => [
                    'city_id' => (string) ($item['id'] ?? ''),
                    'province_id' => $provinceId,
                    'province' => '',
                    'type' => '',
                    'city_name' => (string) ($item['name'] ?? ''),
                    'postal_code' => (string) ($item['zip_code'] ?? ''),
                ])
                ->filter(fn (array $item): bool => $item['city_id'] !== '' && $item['city_name'] !== '')
                ->values()
                ->all();
        }

        $payload = $this->request(
            fn (): Response => $this->client()->get($this->baseUrl().'/city', [
                'province' => $provinceId,
            ])
        );

        $items = $payload['rajaongkir']['results'] ?? null;

        if (! is_array($items)) {
            throw new RajaOngkirException('Format respons kota RajaOngkir tidak dikenali.');
        }

        return collect($items)
            ->map(fn (array $item): array => [
                'city_id' => (string) ($item['city_id'] ?? ''),
                'province_id' => (string) ($item['province_id'] ?? $provinceId),
                'province' => (string) ($item['province'] ?? ''),
                'type' => (string) ($item['type'] ?? ''),
                'city_name' => (string) ($item['city_name'] ?? ''),
                'postal_code' => (string) ($item['postal_code'] ?? ''),
            ])
            ->filter(fn (array $item): bool => $item['city_id'] !== '' && $item['city_name'] !== '')
            ->values()
            ->all();
    }

    private function fetchCosts(string $origin, string $destination, int $weight, string $courier): array
    {
        if ($this->isKomerce()) {
            $payload = $this->request(
                fn (): Response => $this->client()->asForm()->post(
                    $this->baseUrl().'/calculate/domestic-cost',
                    [
                        'origin' => $origin,
                        'destination' => $destination,
                        'weight' => $weight,
                        'courier' => $courier,
                        'price' => 'lowest',
                    ]
                )
            );

            return collect($this->dataItems($payload))
                ->map(function (array $item) use ($courier): array {
                    $cost = $item['cost'] ?? 0;

                    if (is_array($cost)) {
                        $cost = $cost[0]['value'] ?? 0;
                    }

                    return [
                        'courier' => (string) ($item['code'] ?? $courier),
                        'name' => (string) ($item['name'] ?? strtoupper($courier)),
                        'service' => (string) ($item['service'] ?? ''),
                        'description' => (string) ($item['description'] ?? ''),
                        'cost' => (int) round((float) $cost),
                        'etd' => $this->normalizeEtd($item['etd'] ?? ''),
                        'note' => (string) ($item['note'] ?? ''),
                    ];
                })
                ->filter(fn (array $item): bool => $item['service'] !== '')
                ->values()
                ->all();
        }

        $payload = $this->request(
            fn (): Response => $this->client()->asForm()->post(
                $this->baseUrl().'/cost',
                [
                    'origin' => $origin,
                    'destination' => $destination,
                    'weight' => $weight,
                    'courier' => $courier,
                ]
            )
        );

        $results = $payload['rajaongkir']['results'] ?? null;

        if (! is_array($results)) {
            throw new RajaOngkirException('Format respons ongkos kirim RajaOngkir tidak dikenali.');
        }

        $costs = [];

        foreach ($results as $result) {
            foreach (($result['costs'] ?? []) as $service) {
                $detail = $service['cost'][0] ?? [];

                $costs[] = [
                    'courier' => (string) ($result['code'] ?? $courier),
                    'name' => (string) ($result['name'] ?? strtoupper($courier)),
                    'service' => (string) ($service['service'] ?? ''),
                    'description' => (string) ($service['description'] ?? ''),
                    'cost' => (int) round((float) ($detail['value'] ?? 0)),
                    'etd' => $this->normalizeEtd($detail['etd'] ?? ''),
                    'note' => (string) ($detail['note'] ?? ''),
                ];
            }
        }

        return $costs;
    }

    private function request(callable $callback): array
    {
        $response = $callback();

        if ($response->failed()) {
            $status = $response->status();
            $message = $response->json('meta.message')
                ?? $response->json('rajaongkir.status.description');

            Log::warning('RajaOngkir: respons error.', [
                'status' => $status,
            ]);

            if (in_array($status, [401, 403], true)) {
                throw new RajaOngkirException(
                    'API Key RajaOngkir tidak valid atau tidak memiliki akses. Periksa RAJAONGKIR_API_KEY.',
                    502
                );
            }

            throw new RajaOngkirException(
                is_string($message) && $message !== ''
                    ? 'RajaOngkir: '.$message
                    : 'Layanan RajaOngkir mengembalikan error (HTTP '.$status.').',
                502
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RajaOngkirException('Format respons RajaOngkir tidak dikenali.');
        }

        $metaCode = $payload['meta']['code'] ?? $payload['rajaongkir']['status']['code'] ?? 200;

        if ((int) $metaCode >= 400) {
            $message = $payload['meta']['message']
                ?? $payload['rajaongkir']['status']['description']
                ?? 'Permintaan ke RajaOngkir ditolak.';

            throw new RajaOngkirException('RajaOngkir: '.$message);
        }

        return $payload;
    }

    private function normalizeEtd(mixed $etd): string
    {
        $value = trim((string) $etd);

        return (string) preg_replace('/\s*days?$/i', '', $value);
    }

    private function dataItems(array $payload): array
    {
        $items = $payload['data'] ?? null;

        if (! is_array($items)) {
            throw new RajaOngkirException('Format data RajaOngkir tidak dikenali.');
        }

        return $items;
    }

    private function handle(callable $callback): JsonResponse
    {
        if ($this->apiKey() === '') {
            return $this->error(
                'Kunci API RajaOngkir belum diatur. Isi RAJAONGKIR_API_KEY pada berkas .env.',
                503
            );
        }

        try {
            return $callback();
        } catch (ConnectionException $exception) {
            Log::warning('RajaOngkir: koneksi gagal.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Tidak dapat menghubungi layanan RajaOngkir. Periksa koneksi internet lalu coba lagi.',
                503
            );
        } catch (RajaOngkirException $exception) {
            return $this->error($exception->getMessage(), $exception->httpStatus());
        } catch (Throwable $exception) {
            Log::error('RajaOngkir: kesalahan tak terduga.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error('Terjadi kesalahan saat memproses permintaan ongkir.', 500);
        }
    }

    private function success(array $data): JsonResponse
    {
        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'OK',
            ],
            'data' => $data,
        ]);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => [
                'code' => $status,
                'description' => $message,
            ],
            'message' => $message,
        ], $status);
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'key' => $this->apiKey(),
        ])
            ->acceptJson()
            ->timeout((int) config('services.rajaongkir.timeout', 15));
    }

    private function isKomerce(): bool
    {
        return str_contains(strtolower($this->baseUrl()), 'komerce');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.rajaongkir.base_url'), '/');
    }

    private function apiKey(): string
    {
        return trim((string) config('services.rajaongkir.key'));
    }

    private function cacheKey(string $suffix): string
    {
        $keyFingerprint = substr(hash('sha256', $this->apiKey()), 0, 12);

        return 'rajaongkir.'.($this->isKomerce() ? 'komerce' : 'starter').'.'.$keyFingerprint.'.'.$suffix;
    }
}
