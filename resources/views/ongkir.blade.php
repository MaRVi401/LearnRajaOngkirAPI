<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cek Ongkir (Origin: Indramayu)</title>
    
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input, button { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background-color: #f4645c; color: white; border: none; cursor: pointer; }
        #hasil-ongkir { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .origin-info { background: #f0f0f0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Kalkulator Ongkos Kirim</h2>

    <div class="origin-info">
        <label>Kota Asal Pengiriman:</label>
        <p><strong>{{ $originCityName }}</strong></p>
    </div>
    
    <hr>

    <div>
        <label for="prov-tujuan">Provinsi Tujuan:</label>
        <select id="prov-tujuan"></select>
    </div>
    <div>
        <label for="kota-tujuan">Kota Tujuan:</label>
        <select id="kota-tujuan"></select>
    </div>

    <div>
        <label for="berat">Berat (gram):</label>
        <input type="number" id="berat" value="1000" min="1">
    </div>
    
    <div>
        <label for="kurir">Kurir:</label>
        <select id="kurir">
            <option value="jne">JNE</option>
            <option value="tiki">TIKI</option>
            <option value="pos">POS Indonesia</option>
            </select>
    </div>

    <button id="cek-ongkir">Cek Ongkir</button>

    <div id="hasil-ongkir">
        </div>

<script>
    // URL ke API kita
    const API_URL = '/api'; // Prefix API Laravel

    // Ambil elemen-elemen DOM
    const provTujuan = document.getElementById('prov-tujuan');
    const kotaTujuan = document.getElementById('kota-tujuan');
    const berat = document.getElementById('berat');
    const kurir = document.getElementById('kurir');
    const cekOngkirBtn = document.getElementById('cek-ongkir');
    const hasilOngkir = document.getElementById('hasil-ongkir');

    // Ambil CSRF token dari meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- FUNGSI-FUNGSI ---

    // Fungsi generik untuk fetch data
    async function fetchData(endpoint, options = {}) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, options);
            if (!response.ok) {
                // Coba parse error dari body
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            return await response.json(); // Data dari controller kita
        } catch (error) {
            console.error("Fetch error:", error);
            hasilOngkir.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            return null; // Kembalikan null agar alur berhenti
        }
    }

    // Fungsi untuk mengisi <select> (API V2 Komerce)
    function populateSelect(element, data) {

        element.innerHTML = '<option value="">-- Pilih --</option>'; // Reset
        
        data.forEach(item => {
            let text = '';
            let value = '';

            // Cek apakah ini objek PROVINSI (dari /api/provinces)
            if (item.name) {
                text = item.name;
                value = item.id;
            } 
            // Cek apakah ini objek KOTA (dari /api/cities)
            else if (item.city_name) {
                text = `${item.type} ${item.city_name}`;
                value = item.city_id;
            }
            
            // Tambahkan ke <select>
            element.innerHTML += `<option value="${value}">${text}</option>`;
        });
    }

    // Mengambil data provinsi (Tujuan) saat halaman dimuat
    async function loadProvinsiTujuan() {
        const data = await fetchData('/provinces'); // Endpoint /api/provinces
        if (data) {
            populateSelect(provTujuan, data);
        }
    }

    // Mengambil data kota (Tujuan) berdasarkan provinsi
    async function getKotaTujuan(idProvinsi) {
        if (!idProvinsi) {
            kotaTujuan.innerHTML = '<option value="">-- Pilih --</option>';
            return;
        }
        
        const data = await fetchData(`/get-kota/${idProvinsi}`);
        if (data) {
            populateSelect(kotaTujuan, data);
        }
    }

    // Mengambil data ongkos kirim (Menggunakan POST)
    async function cekOngkir() {
        // Validasi hanya tujuan
        if (!kotaTujuan.value || !berat.value || !kurir.value) {
            hasilOngkir.innerHTML = '<p style="color: red;">Tujuan, Berat, dan Kurir harus diisi!</p>';
            return;
        }

        hasilOngkir.innerHTML = '<p>Mencari...</p>'; // Loading
        
        const postData = {
            destination: kotaTujuan.value, // Hanya kirim destination
            weight: berat.value,
            courier: kurir.value,
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken // Kirim CSRF token
            },
            body: JSON.stringify(postData) // Kirim data sebagai JSON
        };

        // Panggil endpoint /api/ongkir
        const resultData = await fetchData('/ongkir', options); 

        // Penanganan respons API V2 Komerce
        if (resultData) {
            hasilOngkir.innerHTML = ''; // Kosongkan hasil

            // 'results' berisi array kurir (JNE, TIKI, dll)
            if (resultData.results && resultData.results.length > 0) {
                
                resultData.results.forEach(kurirResult => {
                    hasilOngkir.innerHTML += `<h3>Kurir: ${kurirResult.name.toUpperCase()}</h3>`;

                    if (kurirResult.costs && kurirResult.costs.length > 0) {
                        kurirResult.costs.forEach(cost => {
                            const service = cost.service;
                            const description = cost.description;
                            const price = cost.cost[0].value;
                            const etd = cost.cost[0].etd;

                            hasilOngkir.innerHTML += `
                                <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                                    <p><strong>Layanan:</strong> ${service} (${description})</p>
                                    <p><strong>Harga:</strong> Rp ${price.toLocaleString('id-ID')}</p>
                                    <p><strong>Estimasi:</strong> ${etd}</p>
                                </div>
                            `;
                        });
                    } else {
                        hasilOngkir.innerHTML += '<p>Maaf, layanan tidak ditemukan untuk kurir ini.</p>';
                    }
                });

            } else {
                hasilOngkir.innerHTML = '<p>Maaf, tidak ada layanan pengiriman untuk rute ini.</p>';
            }
        }
        // Jika resultData null, pesan error sudah ditampilkan oleh fetchData
    }

    // --- EVENT LISTENERS ---
    
    // Panggil `getKotaTujuan` saat provinsi tujuan berubah
    provTujuan.addEventListener('change', () => getKotaTujuan(provTujuan.value));
    
    // Panggil `cekOngkir` saat tombol diklik
    cekOngkirBtn.addEventListener('click', cekOngkir);

    // Muat provinsi tujuan saat halaman siap
    document.addEventListener('DOMContentLoaded', loadProvinsiTujuan); 
</script>

</body>
</html>