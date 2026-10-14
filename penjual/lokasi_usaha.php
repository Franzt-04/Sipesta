<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("penjual");

$userId = userId();

$pesan = "";
$tipePesan = "";

/*
|--------------------------------------------------------------------------
| AMBIL DATA PENJUAL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        nama_usaha,
        alamat,
        latitude,
        longitude
    FROM penjual
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$penjual = $result->fetch_assoc();

$stmt->close();


if (!$penjual) {
    die("Data penjual tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| SIMPAN LOKASI
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if ($latitude === '' || $longitude === '') {

        $pesan = "Latitude dan longitude wajib diisi.";
        $tipePesan = "danger";

    } elseif (
        !is_numeric($latitude) ||
        !is_numeric($longitude) ||
        (float)$latitude < -90 ||
        (float)$latitude > 90 ||
        (float)$longitude < -180 ||
        (float)$longitude > 180
    ) {

        $pesan = "Koordinat lokasi tidak valid.";
        $tipePesan = "danger";

    } else {

        $lat = (float)$latitude;
        $lng = (float)$longitude;

        $stmt = $conn->prepare("
            UPDATE penjual
            SET
                latitude = ?,
                longitude = ?
            WHERE id = ?
              AND user_id = ?
        ");

        $stmt->bind_param(
            "ddii",
            $lat,
            $lng,
            $penjual['id'],
            $userId
        );

        if ($stmt->execute()) {

            $pesan = "Lokasi usaha berhasil disimpan.";
            $tipePesan = "success";

            // Update data yang tampil
            $penjual['latitude'] = $lat;
            $penjual['longitude'] = $lng;

        } else {

            $pesan = "Gagal menyimpan lokasi usaha.";
            $tipePesan = "danger";
        }

        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| STATUS LOKASI
|--------------------------------------------------------------------------
*/

$lokasiLengkap =
    $penjual['latitude'] !== null &&
    $penjual['longitude'] !== null &&
    $penjual['latitude'] !== '' &&
    $penjual['longitude'] !== '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Lokasi Usaha - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }

        .location-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .location-success {
            background: #d1e7dd;
            color: #198754;
        }

        .location-warning {
            background: #fff3cd;
            color: #997404;
        }

        .coordinate-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }

        .coordinate-value {
            font-family: monospace;
            font-size: 15px;
        }

        .gps-button {
            min-height: 48px;
        }

        .info-card {
            border-left: 4px solid #198754;
        }

        .form-control {
            min-height: 45px;
        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-white">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold text-success"
        >

            <i class="bi bi-shop"></i>

            SIPESTA

        </a>


        <div class="d-flex align-items-center gap-2">

            <a
                href="index.php"
                class="btn btn-outline-secondary btn-sm"
            >

                <i class="bi bi-arrow-left"></i>

                Dashboard

            </a>

        </div>

    </div>

</nav>


<div class="container py-4">


    <!-- HEADER -->

    <div class="mb-4">

        <h3 class="fw-bold mb-1">

            <i class="bi bi-geo-alt-fill text-success"></i>

            Lokasi Usaha

        </h3>

        <p class="text-muted mb-0">

            Atur lokasi usaha Anda untuk mendukung perhitungan
            jarak dan ongkos kirim.

        </p>

    </div>


    <!-- PESAN -->

    <?php if ($pesan !== ""): ?>

        <div
            class="alert alert-<?= e($tipePesan) ?> alert-dismissible fade show"
        >

            <?php if ($tipePesan === 'success'): ?>

                <i class="bi bi-check-circle-fill"></i>

            <?php else: ?>

                <i class="bi bi-exclamation-triangle-fill"></i>

            <?php endif; ?>

            <?= e($pesan) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <div class="row g-4">


        <!-- INFORMASI USAHA -->

        <div class="col-lg-5">

            <div class="card p-4 h-100">

                <div class="text-center">

                    <div
                        class="location-icon
                        <?= $lokasiLengkap
                            ? 'location-success'
                            : 'location-warning'
                        ?>"
                    >

                        <i class="bi bi-geo-alt-fill"></i>

                    </div>


                    <h4 class="fw-bold">

                        <?= e($penjual['nama_usaha']) ?>

                    </h4>


                    <?php if ($lokasiLengkap): ?>

                        <span class="badge bg-success">

                            <i class="bi bi-check-circle"></i>

                            Lokasi Sudah Diatur

                        </span>

                    <?php else: ?>

                        <span class="badge bg-warning text-dark">

                            <i class="bi bi-exclamation-circle"></i>

                            Lokasi Belum Diatur

                        </span>

                    <?php endif; ?>

                </div>


                <hr>


                <div class="mb-3">

                    <label class="text-muted small">

                        Alamat Usaha

                    </label>

                    <div class="mt-1">

                        <i class="bi bi-house-door text-success"></i>

                        <?= e($penjual['alamat'] ?: 'Belum diisi') ?>

                    </div>

                </div>


                <?php if ($lokasiLengkap): ?>

                    <div class="coordinate-box">

                        <div class="text-muted small mb-2">

                            <i class="bi bi-crosshair"></i>

                            Koordinat Saat Ini

                        </div>


                        <div class="coordinate-value">

                            <div>

                                <strong>Latitude:</strong>

                                <?= e($penjual['latitude']) ?>

                            </div>

                            <div>

                                <strong>Longitude:</strong>

                                <?= e($penjual['longitude']) ?>

                            </div>

                        </div>

                    </div>

                <?php endif; ?>


                <div class="alert alert-info mt-4 mb-0">

                    <i class="bi bi-info-circle-fill"></i>

                    <strong>Mengapa lokasi diperlukan?</strong>

                    <ul class="mb-0 mt-2">

                        <li>
                            Menghitung jarak ke pembeli.
                        </li>

                        <li>
                            Menghitung ongkos kirim.
                        </li>

                        <li>
                            Menentukan jangkauan pengiriman.
                        </li>

                        <li>
                            Mendukung fitur peta SIPESTA.
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- FORM LOKASI -->

        <div class="col-lg-7">

            <div class="card p-4">

                <h5 class="fw-bold mb-1">

                    <i class="bi bi-crosshair text-success"></i>

                    Atur Koordinat GPS

                </h5>

                <p class="text-muted">

                    Gunakan GPS perangkat Anda atau masukkan koordinat
                    secara manual.

                </p>


                <div
                    id="statusLokasi"
                    class="alert alert-secondary"
                >

                    <i class="bi bi-info-circle"></i>

                    Klik tombol <strong>Gunakan Lokasi Saya</strong>
                    untuk mendapatkan koordinat GPS.

                </div>


                <button
                    type="button"
                    id="btnLokasi"
                    class="btn btn-success w-100 gps-button mb-4"
                >

                    <i class="bi bi-geo-alt-fill"></i>

                    Gunakan Lokasi Saya

                </button>


                <form method="POST">


                    <div class="row g-3">


                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Latitude

                            </label>

                            <input
                                type="number"
                                step="any"
                                min="-90"
                                max="90"
                                name="latitude"
                                id="latitude"
                                class="form-control"
                                value="<?= e($penjual['latitude'] ?? '') ?>"
                                placeholder="-5.147665"
                                required
                            >

                            <div class="form-text">

                                Nilai antara -90 sampai 90.

                            </div>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Longitude

                            </label>

                            <input
                                type="number"
                                step="any"
                                min="-180"
                                max="180"
                                name="longitude"
                                id="longitude"
                                class="form-control"
                                value="<?= e($penjual['longitude'] ?? '') ?>"
                                placeholder="119.432732"
                                required
                            >

                            <div class="form-text">

                                Nilai antara -180 sampai 180.

                            </div>

                        </div>

                    </div>


                    <hr class="my-4">


                    <div class="d-flex justify-content-end gap-2">

                        <a
                            href="index.php"
                            class="btn btn-outline-secondary"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Kembali

                        </a>


                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="bi bi-save"></i>

                            Simpan Lokasi

                        </button>

                    </div>

                </form>

            </div>


            <!-- TIPS -->

            <div class="card info-card mt-4 p-4">

                <h6 class="fw-bold">

                    <i class="bi bi-lightbulb text-warning"></i>

                    Tips mendapatkan lokasi yang akurat

                </h6>

                <ol class="mb-0">

                    <li>
                        Pastikan GPS perangkat aktif.
                    </li>

                    <li>
                        Izinkan browser mengakses lokasi.
                    </li>

                    <li>
                        Berdiri atau berada di sekitar lokasi usaha.
                    </li>

                    <li>
                        Tunggu sampai GPS mendapatkan koordinat.
                    </li>

                    <li>
                        Periksa kembali koordinat sebelum menyimpan.
                    </li>

                </ol>

            </div>

        </div>

    </div>

</div>


<script>

const btnLokasi =
    document.getElementById('btnLokasi');

const statusLokasi =
    document.getElementById('statusLokasi');

const latitude =
    document.getElementById('latitude');

const longitude =
    document.getElementById('longitude');


btnLokasi.addEventListener('click', function () {

    if (!navigator.geolocation) {

        statusLokasi.className =
            'alert alert-danger';

        statusLokasi.innerHTML =
            '<i class="bi bi-x-circle-fill"></i> ' +
            'Browser Anda tidak mendukung GPS.';

        return;
    }


    btnLokasi.disabled = true;

    btnLokasi.innerHTML =
        '<span class="spinner-border spinner-border-sm"></span> ' +
        'Mengambil lokasi...';


    statusLokasi.className =
        'alert alert-primary';

    statusLokasi.innerHTML =
        '<i class="bi bi-hourglass-split"></i> ' +
        'Sedang mengambil lokasi GPS. Mohon tunggu...';


    navigator.geolocation.getCurrentPosition(

        function (position) {

            const lat =
                position.coords.latitude;

            const lng =
                position.coords.longitude;


            latitude.value =
                lat.toFixed(8);

            longitude.value =
                lng.toFixed(8);


            statusLokasi.className =
                'alert alert-success';

            statusLokasi.innerHTML =
                '<i class="bi bi-check-circle-fill"></i> ' +
                '<strong>Lokasi berhasil ditemukan.</strong><br>' +
                'Koordinat GPS telah dimasukkan ke formulir.';


            btnLokasi.disabled = false;

            btnLokasi.innerHTML =
                '<i class="bi bi-arrow-clockwise"></i> ' +
                'Ambil Ulang Lokasi';


        },

        function (error) {

            let pesan =
                'Gagal mendapatkan lokasi.';


            if (error.code === 1) {

                pesan =
                    'Akses lokasi ditolak. ' +
                    'Silakan izinkan lokasi pada browser.';

            } else if (error.code === 2) {

                pesan =
                    'Lokasi tidak tersedia. ' +
                    'Pastikan GPS/perangkat lokasi aktif.';

            } else if (error.code === 3) {

                pesan =
                    'Waktu pengambilan lokasi habis. ' +
                    'Silakan coba lagi.';

            }


            statusLokasi.className =
                'alert alert-danger';

            statusLokasi.innerHTML =
                '<i class="bi bi-exclamation-triangle-fill"></i> ' +
                pesan;


            btnLokasi.disabled = false;

            btnLokasi.innerHTML =
                '<i class="bi bi-geo-alt-fill"></i> ' +
                'Gunakan Lokasi Saya';

        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }

    );

});

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>