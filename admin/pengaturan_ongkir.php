<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");


/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/

$pesan = "";
$tipePesan = "";


/*
|--------------------------------------------------------------------------
| PROSES SIMPAN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_pengaturan = trim(
        $_POST['nama_pengaturan'] ?? ''
    );

    $tarif_dasar = (float) (
        $_POST['tarif_dasar'] ?? 0
    );

    $tarif_per_km = (float) (
        $_POST['tarif_per_km'] ?? 0
    );

    $minimal_gratis = (float) (
        $_POST['minimal_gratis'] ?? 0
    );

    $maksimal_jarak_km = (float) (
        $_POST['maksimal_jarak_km'] ?? 0
    );

    $status = trim(
        $_POST['status'] ?? 'aktif'
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if ($nama_pengaturan === '') {

        $pesan = "Nama pengaturan wajib diisi.";
        $tipePesan = "danger";

    } elseif ($tarif_dasar < 0) {

        $pesan = "Tarif dasar tidak boleh negatif.";
        $tipePesan = "danger";

    } elseif ($tarif_per_km < 0) {

        $pesan = "Tarif per km tidak boleh negatif.";
        $tipePesan = "danger";

    } elseif ($minimal_gratis < 0) {

        $pesan = "Minimal gratis ongkir tidak boleh negatif.";
        $tipePesan = "danger";

    } elseif ($maksimal_jarak_km < 0) {

        $pesan = "Maksimal jarak tidak boleh negatif.";
        $tipePesan = "danger";

    } elseif (
        !in_array(
            $status,
            ['aktif', 'nonaktif'],
            true
        )
    ) {

        $pesan = "Status pengaturan tidak valid.";
        $tipePesan = "danger";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CEK APAKAH SUDAH ADA PENGATURAN
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id
            FROM pengaturan_ongkir
            ORDER BY id ASC
            LIMIT 1
        ");

        $stmt->execute();

        $dataLama = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();


        if ($dataLama) {

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            $id = (int) $dataLama['id'];

            $stmt = $conn->prepare("
                UPDATE pengaturan_ongkir

                SET
                    nama_pengaturan = ?,
                    tarif_dasar = ?,
                    tarif_per_km = ?,
                    minimal_gratis = ?,
                    maksimal_jarak_km = ?,
                    status = ?

                WHERE id = ?
            ");

            $stmt->bind_param(
                "sddddsi",
                $nama_pengaturan,
                $tarif_dasar,
                $tarif_per_km,
                $minimal_gratis,
                $maksimal_jarak_km,
                $status,
                $id
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | INSERT
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                INSERT INTO pengaturan_ongkir
                (
                    nama_pengaturan,
                    tarif_dasar,
                    tarif_per_km,
                    minimal_gratis,
                    maksimal_jarak_km,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->bind_param(
                "sdddds",
                $nama_pengaturan,
                $tarif_dasar,
                $tarif_per_km,
                $minimal_gratis,
                $maksimal_jarak_km,
                $status
            );
        }


        if ($stmt->execute()) {

            $pesan =
                "Pengaturan ongkos kirim berhasil disimpan.";

            $tipePesan = "success";

        } else {

            $pesan =
                "Gagal menyimpan pengaturan ongkos kirim.";

            $tipePesan = "danger";
        }

        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL PENGATURAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        nama_pengaturan,
        tarif_dasar,
        tarif_per_km,
        minimal_gratis,
        maksimal_jarak_km,
        status,
        created_at,
        updated_at
    FROM pengaturan_ongkir
    ORDER BY id ASC
    LIMIT 1
");

$stmt->execute();

$pengaturan = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| NILAI DEFAULT FORM
|--------------------------------------------------------------------------
*/

if ($pengaturan) {

    $nama_pengaturan =
        $pengaturan['nama_pengaturan'];

    $tarif_dasar =
        (float) $pengaturan['tarif_dasar'];

    $tarif_per_km =
        (float) $pengaturan['tarif_per_km'];

    $minimal_gratis =
        (float) $pengaturan['minimal_gratis'];

    $maksimal_jarak_km =
        (float) $pengaturan['maksimal_jarak_km'];

    $status =
        $pengaturan['status'];

} else {

    $nama_pengaturan =
        'Ongkir Default';

    $tarif_dasar = 5000;

    $tarif_per_km = 3000;

    $minimal_gratis = 500000;

    $maksimal_jarak_km = 30;

    $status = 'aktif';
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Pengaturan Ongkir - SIPESTA</title>


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

        .navbar-brand {
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 18px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 11px 13px;
        }

        .info-card {
            border-radius: 15px;
            background: #f8f9fa;
        }

        .preview-card {
            border-radius: 16px;
            background: linear-gradient(
                135deg,
                #198754,
                #146c43
            );
            color: white;
        }

        .preview-item {
            background: rgba(255,255,255,.12);
            border-radius: 12px;
            padding: 15px;
        }

    </style>

</head>


<body>


<!-- ================================================================ -->
<!-- NAVBAR -->
<!-- ================================================================ -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container-fluid px-4">

        <a
            href="index.php"
            class="navbar-brand text-success"
        >

            <i class="bi bi-shop"></i>

            SIPESTA

        </a>


        <div class="d-flex align-items-center gap-2">

            <a
                href="index.php"
                class="btn btn-outline-secondary btn-sm"
            >

                <i class="bi bi-speedometer2"></i>

                Dashboard

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </div>

    </div>

</nav>


<!-- ================================================================ -->
<!-- CONTENT -->
<!-- ================================================================ -->

<div class="container py-4">


    <!-- HEADER -->

    <div class="mb-4">

        <h2 class="fw-bold">

            <i class="bi bi-truck text-success"></i>

            Pengaturan Ongkos Kirim

        </h2>

        <p class="text-muted mb-0">

            Atur tarif pengiriman yang digunakan SIPESTA
            ketika pembeli melakukan checkout.

        </p>

    </div>


    <!-- PESAN -->

    <?php if ($pesan !== ''): ?>

        <div
            class="alert alert-<?= e($tipePesan) ?> alert-dismissible fade show"
            role="alert"
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


        <!-- ========================================================== -->
        <!-- FORM -->
        <!-- ========================================================== -->

        <div class="col-12 col-lg-7">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex align-items-center gap-3 mb-4">

                        <div
                            class="bg-success-subtle text-success rounded-3 p-3"
                        >

                            <i class="bi bi-sliders fs-4"></i>

                        </div>

                        <div>

                            <h5 class="fw-bold mb-1">
                                Tarif Pengiriman
                            </h5>

                            <small class="text-muted">
                                Pengaturan utama ongkos kirim
                            </small>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action=""
                    >


                        <!-- NAMA -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Nama Pengaturan

                            </label>

                            <input
                                type="text"
                                name="nama_pengaturan"
                                class="form-control"
                                value="<?= e($nama_pengaturan) ?>"
                                placeholder="Contoh: Ongkir Default"
                                required
                            >

                        </div>


                        <!-- TARIF DASAR -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Tarif Dasar

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="tarif_dasar"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    value="<?= e($tarif_dasar) ?>"
                                    required
                                >

                            </div>

                            <div class="form-text">

                                Biaya awal sebelum ditambahkan
                                tarif berdasarkan jarak.

                            </div>

                        </div>


                        <!-- TARIF PER KM -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Tarif Per Kilometer

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="tarif_per_km"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    value="<?= e($tarif_per_km) ?>"
                                    required
                                >

                                <span class="input-group-text">
                                    / km
                                </span>

                            </div>

                            <div class="form-text">

                                Biaya yang ditambahkan untuk
                                setiap kilometer jarak pengiriman.

                            </div>

                        </div>


                        <!-- MINIMAL GRATIS -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Minimal Gratis Ongkir

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="minimal_gratis"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    value="<?= e($minimal_gratis) ?>"
                                    required
                                >

                            </div>

                            <div class="form-text">

                                Jika total produk mencapai nilai ini,
                                ongkos kirim menjadi gratis.
                                Isi <strong>0</strong> jika tidak ingin
                                menggunakan gratis ongkir.

                            </div>

                        </div>


                        <!-- MAKSIMAL JARAK -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Maksimal Jarak Pengiriman

                            </label>

                            <div class="input-group">

                                <input
                                    type="number"
                                    name="maksimal_jarak_km"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?= e($maksimal_jarak_km) ?>"
                                    required
                                >

                                <span class="input-group-text">
                                    KM
                                </span>

                            </div>

                            <div class="form-text">

                                Pesanan di luar jarak ini akan ditolak.
                                Isi <strong>0</strong> jika tidak ingin
                                membatasi jarak.

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">

                                Status

                            </label>

                            <select
                                name="status"
                                class="form-select"
                                required
                            >

                                <option
                                    value="aktif"
                                    <?= $status === 'aktif'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Aktif
                                </option>

                                <option
                                    value="nonaktif"
                                    <?= $status === 'nonaktif'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Nonaktif
                                </option>

                            </select>

                        </div>


                        <!-- TOMBOL -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >

                                <i class="bi bi-save"></i>

                                Simpan Pengaturan

                            </button>


                            <a
                                href="index.php"
                                class="btn btn-outline-secondary"
                            >

                                Batal

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <!-- ========================================================== -->
        <!-- INFORMASI -->
        <!-- ========================================================== -->

        <div class="col-12 col-lg-5">


            <!-- PREVIEW -->

            <div class="preview-card shadow-sm p-4 mb-4">

                <div class="d-flex align-items-center gap-3 mb-4">

                    <div>

                        <i class="bi bi-truck fs-1"></i>

                    </div>

                    <div>

                        <h5 class="fw-bold mb-1">
                            Preview Ongkir
                        </h5>

                        <small>
                            Perhitungan berdasarkan jarak
                        </small>

                    </div>

                </div>


                <div class="row g-3">


                    <div class="col-6">

                        <div class="preview-item">

                            <small>
                                Tarif Dasar
                            </small>

                            <div class="fw-bold mt-1">

                                <?= rupiah($tarif_dasar) ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-6">

                        <div class="preview-item">

                            <small>
                                Tarif / KM
                            </small>

                            <div class="fw-bold mt-1">

                                <?= rupiah($tarif_per_km) ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-6">

                        <div class="preview-item">

                            <small>
                                Gratis Ongkir
                            </small>

                            <div class="fw-bold mt-1">

                                <?php if ($minimal_gratis > 0): ?>

                                    <?= rupiah($minimal_gratis) ?>

                                <?php else: ?>

                                    Tidak Aktif

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                    <div class="col-6">

                        <div class="preview-item">

                            <small>
                                Maksimal Jarak
                            </small>

                            <div class="fw-bold mt-1">

                                <?php if ($maksimal_jarak_km > 0): ?>

                                    <?= e($maksimal_jarak_km) ?> KM

                                <?php else: ?>

                                    Tidak Terbatas

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CARA KERJA -->

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">

                        <i class="bi bi-info-circle text-primary"></i>

                        Cara Kerja

                    </h5>


                    <div class="info-card p-3 mb-3">

                        <div class="fw-semibold mb-1">

                            1. Lokasi Pembeli

                        </div>

                        <small class="text-muted">

                            Sistem mengambil koordinat lokasi
                            pengiriman pembeli.

                        </small>

                    </div>


                    <div class="info-card p-3 mb-3">

                        <div class="fw-semibold mb-1">

                            2. Lokasi Penjual

                        </div>

                        <small class="text-muted">

                            Sistem menggunakan koordinat usaha
                            setiap penjual dalam keranjang.

                        </small>

                    </div>


                    <div class="info-card p-3 mb-3">

                        <div class="fw-semibold mb-1">

                            3. Hitung Jarak

                        </div>

                        <small class="text-muted">

                            Jarak dihitung menggunakan koordinat
                            penjual dan pembeli.

                        </small>

                    </div>


                    <div class="info-card p-3">

                        <div class="fw-semibold mb-1">

                            4. Hitung Ongkir

                        </div>

                        <small class="text-muted">

                            Sistem menghitung tarif dasar +
                            jarak × tarif per kilometer.

                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================================================== -->
    <!-- CONTOH PERHITUNGAN -->
    <!-- ========================================================== -->

    <div class="card shadow-sm mt-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">

                <i class="bi bi-calculator text-success"></i>

                Contoh Perhitungan

            </h5>


            <div class="table-responsive">

                <table class="table table-bordered align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>
                                Komponen
                            </th>

                            <th>
                                Nilai
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>
                                Tarif Dasar
                            </td>

                            <td>
                                <?= rupiah($tarif_dasar) ?>
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Tarif Per KM
                            </td>

                            <td>
                                <?= rupiah($tarif_per_km) ?>
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Contoh Jarak
                            </td>

                            <td>
                                10 KM
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Perhitungan
                            </td>

                            <td>

                                <?= rupiah($tarif_dasar) ?>

                                +

                                (10 × <?= rupiah($tarif_per_km) ?>)

                            </td>

                        </tr>

                        <tr class="table-success">

                            <th>
                                Contoh Ongkir
                            </th>

                            <th>

                                <?= rupiah(
                                    $tarif_dasar +
                                    (10 * $tarif_per_km)
                                ) ?>

                            </th>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>