<?php
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");

$pesan = "";
$tipePesan = "";

/*
|--------------------------------------------------------------------------
| SIMPAN LOKASI PENJUAL
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_lokasi'])) {

    $penjual_id = (int)($_POST['penjual_id'] ?? 0);
    $latitude    = trim($_POST['latitude'] ?? '');
    $longitude   = trim($_POST['longitude'] ?? '');

    if ($penjual_id <= 0) {
        $pesan = "Penjual tidak valid.";
        $tipePesan = "danger";
    } elseif ($latitude === '' || $longitude === '') {
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
        $pesan = "Koordinat tidak valid.";
        $tipePesan = "danger";
    } else {

        $stmt = $conn->prepare("
            UPDATE penjual
            SET latitude = ?, longitude = ?
            WHERE id = ?
        ");

        if ($stmt) {

            $lat = (float)$latitude;
            $lng = (float)$longitude;

            $stmt->bind_param(
                "ddi",
                $lat,
                $lng,
                $penjual_id
            );

            if ($stmt->execute()) {
                $pesan = "Lokasi usaha berhasil disimpan.";
                $tipePesan = "success";
            } else {
                $pesan = "Gagal menyimpan lokasi usaha.";
                $tipePesan = "danger";
            }

            $stmt->close();

        } else {
            $pesan = "Query database gagal.";
            $tipePesan = "danger";
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PENJUAL
|--------------------------------------------------------------------------
*/
$dataPenjual = [];

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.nama_usaha,
        p.alamat,
        p.latitude,
        p.longitude,
        u.username,
        u.status AS status_user
    FROM penjual p
    LEFT JOIN users u
        ON u.id = p.user_id
    ORDER BY p.id DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataPenjual[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| STATISTIK LOKASI
|--------------------------------------------------------------------------
*/
$totalPenjual = count($dataPenjual);
$sudahAdaLokasi = 0;
$belumAdaLokasi = 0;

foreach ($dataPenjual as $p) {

    if (
        $p['latitude'] !== null &&
        $p['longitude'] !== null &&
        $p['latitude'] !== '' &&
        $p['longitude'] !== ''
    ) {
        $sudahAdaLokasi++;
    } else {
        $belumAdaLokasi++;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Lokasi Penjual - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,.06);
        }

        .stat-card {
            border-radius: 16px;
            padding: 20px;
            height: 100%;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .location-complete {
            color: #198754;
            background: #d1e7dd;
        }

        .location-empty {
            color: #dc3545;
            background: #f8d7da;
        }

        .table th {
            white-space: nowrap;
        }

        .coordinate {
            font-family: monospace;
            font-size: 13px;
        }

        .location-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }

        .btn-location {
            min-height: 42px;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom">

    <div class="container-fluid px-4">

        <a
            href="index.php"
            class="navbar-brand text-success"
        >
            <i class="bi bi-shop"></i>
            SIPESTA Admin
        </a>

        <div class="d-flex align-items-center gap-2">

            <span class="text-muted small">
                <i class="bi bi-geo-alt"></i>
                Manajemen Lokasi Penjual
            </span>

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


<div class="container-fluid px-4 py-4">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">
                <i class="bi bi-geo-alt-fill text-success"></i>
                Lokasi Penjual
            </h3>

            <p class="text-muted mb-0">
                Kelola koordinat lokasi usaha setiap penjual.
            </p>

        </div>

    </div>


    <!-- PESAN -->

    <?php if ($pesan !== ""): ?>

        <div
            class="alert alert-<?= e($tipePesan) ?> alert-dismissible fade show"
            role="alert"
        >

            <i class="bi
                <?= $tipePesan === 'success'
                    ? 'bi-check-circle'
                    : 'bi-exclamation-triangle'
                ?>">
            </i>

            <?= e($pesan) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- STATISTIK -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon bg-primary-subtle text-primary">

                        <i class="bi bi-shop"></i>

                    </div>

                    <div>

                        <div class="text-muted small">
                            Total Penjual
                        </div>

                        <h4 class="fw-bold mb-0">
                            <?= $totalPenjual ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon location-complete">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <div>

                        <div class="text-muted small">
                            Lokasi Lengkap
                        </div>

                        <h4 class="fw-bold text-success mb-0">
                            <?= $sudahAdaLokasi ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="d-flex align-items-center gap-3">

                    <div class="stat-icon location-empty">

                        <i class="bi bi-exclamation-circle"></i>

                    </div>

                    <div>

                        <div class="text-muted small">
                            Belum Ada Lokasi
                        </div>

                        <h4 class="fw-bold text-danger mb-0">
                            <?= $belumAdaLokasi ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- INFORMASI -->

    <div class="alert alert-info">

        <div class="d-flex gap-3">

            <i class="bi bi-info-circle-fill fs-4"></i>

            <div>

                <strong>Penting:</strong>

                Lokasi penjual digunakan untuk:

                <ul class="mb-0 mt-1">

                    <li>
                        menghitung jarak penjual ke pembeli;
                    </li>

                    <li>
                        menghitung ongkos kirim;
                    </li>

                    <li>
                        menentukan jangkauan pengiriman;
                    </li>

                    <li>
                        menampilkan lokasi usaha pada fitur peta.
                    </li>

                </ul>

            </div>

        </div>

    </div>


    <!-- TABEL PENJUAL -->

    <div class="card">

        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">
                        Daftar Lokasi Usaha
                    </h5>

                    <small class="text-muted">
                        Atur lokasi GPS masing-masing penjual.
                    </small>

                </div>

                <span class="badge bg-success">
                    <?= $totalPenjual ?> Penjual
                </span>

            </div>

        </div>


        <div class="card-body pt-0">

            <?php if (empty($dataPenjual)): ?>

                <div class="text-center py-5">

                    <i class="bi bi-shop display-4 text-muted"></i>

                    <h5 class="mt-3">
                        Belum ada penjual
                    </h5>

                    <p class="text-muted">
                        Data penjual belum tersedia.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>#</th>

                                <th>Penjual</th>

                                <th>Username</th>

                                <th>Alamat</th>

                                <th>Koordinat</th>

                                <th>Status Lokasi</th>

                                <th>Aksi</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($dataPenjual as $index => $penjual): ?>

                            <?php

                            $adaLokasi =
                                $penjual['latitude'] !== null &&
                                $penjual['longitude'] !== null &&
                                $penjual['latitude'] !== '' &&
                                $penjual['longitude'] !== '';

                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <div class="fw-semibold">
                                        <?= e($penjual['nama_usaha']) ?>
                                    </div>

                                    <small class="text-muted">
                                        ID: <?= (int)$penjual['id'] ?>
                                    </small>

                                </td>


                                <td>

                                    <?= e(
                                        $penjual['username'] ?? '-'
                                    ) ?>

                                </td>


                                <td style="min-width:220px">

                                    <?= e(
                                        $penjual['alamat'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?php if ($adaLokasi): ?>

                                        <div class="coordinate">

                                            <?= e(
                                                $penjual['latitude']
                                            ) ?>

                                            <br>

                                            <?= e(
                                                $penjual['longitude']
                                            ) ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            Belum tersedia
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if ($adaLokasi): ?>

                                        <span class="badge bg-success">

                                            <i class="bi bi-check-circle"></i>

                                            Lengkap

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">

                                            <i class="bi bi-exclamation-circle"></i>

                                            Belum Diatur

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalLokasi<?= (int)$penjual['id'] ?>"
                                    >

                                        <i class="bi bi-geo-alt"></i>

                                        <?= $adaLokasi
                                            ? 'Ubah Lokasi'
                                            : 'Atur Lokasi'
                                        ?>

                                    </button>

                                </td>

                            </tr>


                            <!-- MODAL LOKASI -->

                            <div
                                class="modal fade"
                                id="modalLokasi<?= (int)$penjual['id'] ?>"
                                tabindex="-1"
                            >

                                <div class="modal-dialog modal-dialog-centered">

                                    <div class="modal-content">

                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-geo-alt-fill text-success"></i>

                                                Lokasi Usaha

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <form method="POST">

                                            <div class="modal-body">

                                                <input
                                                    type="hidden"
                                                    name="penjual_id"
                                                    value="<?= (int)$penjual['id'] ?>"
                                                >


                                                <div class="mb-3">

                                                    <label class="form-label fw-semibold">

                                                        Nama Usaha

                                                    </label>

                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        value="<?= e($penjual['nama_usaha']) ?>"
                                                        readonly
                                                    >

                                                </div>


                                                <div class="mb-3">

                                                    <label class="form-label fw-semibold">

                                                        Alamat

                                                    </label>

                                                    <textarea
                                                        class="form-control"
                                                        rows="3"
                                                        readonly
                                                    ><?= e($penjual['alamat'] ?? '') ?></textarea>

                                                </div>


                                                <div class="location-form">

                                                    <div class="d-flex justify-content-between align-items-center mb-3">

                                                        <strong>
                                                            <i class="bi bi-crosshair"></i>
                                                            Koordinat GPS
                                                        </strong>

                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-success btn-location"
                                                            onclick="ambilLokasi(<?= (int)$penjual['id'] ?>)"
                                                        >

                                                            <i class="bi bi-geo-alt-fill"></i>

                                                            Gunakan Lokasi Saya

                                                        </button>

                                                    </div>


                                                    <div
                                                        id="statusLokasi<?= (int)$penjual['id'] ?>"
                                                        class="small text-muted mb-3"
                                                    >

                                                        Klik tombol untuk mengambil lokasi GPS.

                                                    </div>


                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">

                                                                Latitude

                                                            </label>

                                                            <input
                                                                type="number"
                                                                step="any"
                                                                name="latitude"
                                                                id="latitude<?= (int)$penjual['id'] ?>"
                                                                class="form-control"
                                                                value="<?= e($penjual['latitude'] ?? '') ?>"
                                                                placeholder="-5.147665"
                                                                required
                                                            >

                                                        </div>


                                                        <div class="col-md-6">

                                                            <label class="form-label">

                                                                Longitude

                                                            </label>

                                                            <input
                                                                type="number"
                                                                step="any"
                                                                name="longitude"
                                                                id="longitude<?= (int)$penjual['id'] ?>"
                                                                class="form-control"
                                                                value="<?= e($penjual['longitude'] ?? '') ?>"
                                                                placeholder="119.432732"
                                                                required
                                                            >

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>


                                            <div class="modal-footer">

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal"
                                                >

                                                    Batal

                                                </button>


                                                <button
                                                    type="submit"
                                                    name="simpan_lokasi"
                                                    class="btn btn-success"
                                                >

                                                    <i class="bi bi-save"></i>

                                                    Simpan Lokasi

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<script>

function ambilLokasi(id) {

    const status = document.getElementById(
        'statusLokasi' + id
    );

    const latitude = document.getElementById(
        'latitude' + id
    );

    const longitude = document.getElementById(
        'longitude' + id
    );


    if (!navigator.geolocation) {

        status.innerHTML =
            '<span class="text-danger">' +
            '<i class="bi bi-x-circle"></i> ' +
            'Browser tidak mendukung GPS.' +
            '</span>';

        return;
    }


    status.innerHTML =
        '<span class="text-primary">' +
        '<span class="spinner-border spinner-border-sm"></span> ' +
        'Mengambil lokasi...' +
        '</span>';


    navigator.geolocation.getCurrentPosition(

        function(position) {

            latitude.value =
                position.coords.latitude.toFixed(8);

            longitude.value =
                position.coords.longitude.toFixed(8);


            status.innerHTML =
                '<span class="text-success">' +
                '<i class="bi bi-check-circle-fill"></i> ' +
                'Lokasi berhasil ditemukan.' +
                '</span>';

        },

        function(error) {

            let pesan = 'Gagal mendapatkan lokasi.';

            if (error.code === 1) {
                pesan =
                    'Izin lokasi ditolak. Silakan izinkan akses lokasi pada browser.';
            }

            if (error.code === 2) {
                pesan =
                    'Lokasi tidak tersedia.';
            }

            if (error.code === 3) {
                pesan =
                    'Waktu pengambilan lokasi habis.';
            }


            status.innerHTML =
                '<span class="text-danger">' +
                '<i class="bi bi-exclamation-triangle"></i> ' +
                pesan +
                '</span>';

        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }

    );

}

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>