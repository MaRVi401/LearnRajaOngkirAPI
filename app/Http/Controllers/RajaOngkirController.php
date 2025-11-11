<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RajaOngkirController extends Controller
{
    protected $apiKey;
    protected $baseUrl;
    protected $originCityId;

    /**
     * Konstruktor untuk mengambil konfigurasi
     */
    public function __construct()
    {
        $this->apiKey = config('rajaongkir.api_key');
        $this->baseUrl = config('rajaongkir.base_url');
        $this->originCityId = config('rajaongkir.origin_city_id');
    }

    /**
     * Menampilkan halaman utama kalkulator ongkir.
     */
    public function index()
    {
        // Mengambil nama kota asal untuk ditampilkan di view
        $originCityName = $this->getCityNameById($this->originCityId);
        
        return view('ongkir', [
            'originCityName' => $originCityName ?? 'Indramayu (ID: ' . $this->originCityId . ')'
        ]);
    }

    /**
     * Mengambil data provinsi dari Komerce API.
     */
    public function getProvinces()
    {
        $response = Http::withHeaders([
            'key' => $this->apiKey
        ])->get($this->baseUrl . '/destination/province');

        if ($response->successful()) {
            // API V2 membungkus hasil dalam 'data'
            return response()->json($response->json()['data'] ?? []);
        }

        return response()->json(['error' => 'Gagal mengambil data provinsi'], $response->status());
    }

    /**
     * Mengambil data kota berdasarkan ID provinsi.
     */
    public function getCities($province_id)
    {
        $response = Http::withHeaders([
            'key' => $this->apiKey
        ])->get($this->baseUrl . '/destination/city', [
            'province_id' => $province_id
        ]);

        if ($response->successful()) {
            return response()->json($response->json()['data'] ?? []);
        }

        return response()->json(['error' => 'Gagal mengambil data kota'], $response->status());
    }

    /**
     * Menghitung ongkos kirim.
     */
    public function checkOngkir(Request $request)
    {
        try {
            $validated = $request->validate([
                'destination' => 'required|integer', // Hanya butuh destinasi
                'weight' => 'required|integer|min:1',
                'courier' => 'required|string',
            ]);

            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded' // API V2 Komerce menggunakan form-urlencoded
            ])->asForm()->post($this->baseUrl . '/calculate/domestic-cost', [
                'origin' => $this->originCityId, // <-- Diambil dari config
                'destination' => $validated['destination'],
                'weight' => $validated['weight'],
                'courier' => $validated['courier'],
            ]);

            if ($response->successful()) {
                // 'data' berisi detail asal, tujuan, dan 'results'
                return response()->json($response->json()['data'] ?? []);
            }
            
            // Tangani jika API Komerce mengembalikan error (misal: 'data' not found)
            return response()->json(['error' => $response->json()['message'] ?? 'Gagal menghitung ongkir'], $response->status());

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }
    }

    /**
     * Helper function untuk mengambil nama kota asal (opsional, untuk tampilan)
     */
    private function getCityNameById($id)
    {
        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey
            ])->get($this->baseUrl . '/destination/city', ['city_id' => $id]);
            
            if ($response->successful() && isset($response->json()['data'][0])) {
                $city = $response->json()['data'][0];
                return $city['type'] . ' ' . $city['city_name'];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}