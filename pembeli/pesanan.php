<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");

/*
|--------------------------------------------------------------------------
| Ambil ID pembeli berdasarkan user yang sedang login
|--------------------------------------------------------------------------
*/

$user_id = userId();

$stmt = $conn->prepare("
    SELECT id, nama, nama_toko, no_hp, alamat
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$resultPembeli = $stmt->get_result();
$pembeli = $resultPembeli->fetch_assoc();

$stmt->close();

if (!$pembeli) {
    die("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int) $pembeli['id'];


/*
|--------------------------------------------------------------------------
| Filter status
|--------------------------------------------------------------------------
*/

$statusFilter = $_GET['status'] ?? '';

$statusValid = [
    'menunggu',
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan'
];

$whereStatus = "";

if ($statusFilter !== '' && in_array($statusFilter, $statusValid)) {
    $whereStatus = "AND ps.status = ?";
}


/*
|--------------------------------------------------------------------------
| Ambil pesanan pembeli
|--------------------------------------------------------------------------
*/

$pesanan = [];

if ($whereStatus !== '') {

    $stmt = $conn->prepare("
        SELECT
            ps.id,
            ps.kode_pesanan,
            ps.tanggal_pesanan,
            ps.total_harga,
            ps.alamat_pengiriman,
            ps.metode_pembayaran,
            ps.status,
            COUNT(dp.id) AS jumlah_item
        FROM pesanan ps
        LEFT JOIN detail_pesanan dp
            ON dp.pesanan_id = ps.id
        WHERE ps.pembeli_id = ?
        $whereStatus
        GROUP BY
            ps.id,
            ps.kode_pesanan,
            ps.tanggal_pesanan,
            ps.total_harga,
            ps.alamat_pengiriman,
            ps.metode_pembayaran,
            ps.status
        ORDER BY ps.id DESC
    ");

    $stmt->bind_param(
        "is",
        $pembeli_id,
        $statusFilter
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            ps.id,
            ps.kode_pesanan,
            ps.tanggal_pesanan,
            ps.total_harga,
            ps.alamat_pengiriman,
            ps.metode_pembayaran,
            ps.status,
            COUNT(dp.id) AS jumlah_item
        FROM pesanan ps
        LEFT JOIN detail_pesanan dp
            ON dp.pesanan_id = ps.id
        WHERE ps.pembeli_id = ?
        GROUP BY
            ps.id,
            ps.kode_pesanan,
            ps.tanggal_pesanan,
            ps.total_harga,
            ps.alamat_pengiriman,
            ps.metode_pembayaran,
            ps.status
        ORDER BY ps.id DESC
    ");

    $stmt->bind_param(
        "i",
        $pembeli_id
    );
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pesanan[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| Fungsi badge status
|--------------------------------------------------------------------------
*/

function badgeStatusPembeli($status)
{
    switch ($status) {

        case 'menunggu':
            return '<span class="badge bg-warning text-dark">
                        Menunggu
                    </span>';

        case 'diproses':
            return '<span class="badge bg-info text-dark">
                        Diproses
                    </span>';

        case 'dikirim':
            return '<span class="badge bg-primary">
                        Dikirim
                    </span>';

        case 'selesai':
            return '<span class="badge bg-success">
                        Selesai
                    </span>';

        case 'dibatalkan':
            return '<span class="badge bg-danger">
                        Dibatalkan
                    </span>';

        default:
            return '<span class="badge bg-secondary">
                        ' . e($status) . '
                    </span>';
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Pesanan Saya - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
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
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }

        .order-card {
            transition: .2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
        }

        .order-code {
            font-weight: 700;
            font-size: 16px;
        }

        .total {
            font-size: 18px;
            font-weight: 700;
        }

        .filter-btn {
            border-radius: 20px;
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-dark navbar-dark">

    <div class="container">

        <a class="navbar-brand"
           href="index.php">

            SIPESTA

        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu">

            <span class="navbar-toggler-icon"></span>

        </button>


        <div class="collapse navbar-collapse"
             id="navbarMenu">

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">

                    <a class="nav-link"
                       href="index.php">

                        Produk

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link"
                       href="keranjang.php">

                        Keranjang

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link active"
                       href="pesanan.php">

                        Pesanan Saya

                    </a>

                </li>

                <li class="nav-item">

                    <a class="nav-link"
                       href="../logout.php">

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- CONTENT -->

<div class="container py-4">


    <!-- HEADER -->

    <div class="mb-4">

        <h2 class="fw-bold mb-1">

            Pesanan Saya

        </h2>

        <p class="text-muted mb-0">

            Lihat dan pantau status pesanan Anda.

        </p>

    </div>


    <!-- INFORMASI PEMBELI -->

    <div class="card mb-4">

        <div class="card-body">

            <div class="row">

                <div class="col-md-4">

                    <small class="text-muted">
                        Nama Pembeli
                    </small>

                    <div class="fw-bold">

                        <?= e($pembeli['nama']) ?>

                    </div>

                </div>


                <div class="col-md-4">

                    <small class="text-muted">
                        Nama Toko
                    </small>

                    <div class="fw-bold">

                        <?= e($pembeli['nama_toko'] ?: '-') ?>

                    </div>

                </div>


                <div class="col-md-4">

                    <small class="text-muted">
                        No. HP
                    </small>

                    <div class="fw-bold">

                        <?= e($pembeli['no_hp']) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- FILTER -->

    <div class="mb-4">

        <div class="d-flex flex-wrap gap-2">

            <a
                href="pesanan.php"
                class="btn
                <?= $statusFilter === ''
                    ? 'btn-dark'
                    : 'btn-outline-dark' ?>
                filter-btn">

                Semua

            </a>


            <a
                href="pesanan.php?status=menunggu"
                class="btn
                <?= $statusFilter === 'menunggu'
                    ? 'btn-warning'
                    : 'btn-outline-warning' ?>
                filter-btn">

                Menunggu

            </a>


            <a
                href="pesanan.php?status=diproses"
                class="btn
                <?= $statusFilter === 'diproses'
                    ? 'btn-info'
                    : 'btn-outline-info' ?>
                filter-btn">

                Diproses

            </a>


            <a
                href="pesanan.php?status=dikirim"
                class="btn
                <?= $statusFilter === 'dikirim'
                    ? 'btn-primary'
                    : 'btn-outline-primary' ?>
                filter-btn">

                Dikirim

            </a>


            <a
                href="pesanan.php?status=selesai"
                class="btn
                <?= $statusFilter === 'selesai'
                    ? 'btn-success'
                    : 'btn-outline-success' ?>
                filter-btn">

                Selesai

            </a>


            <a
                href="pesanan.php?status=dibatalkan"
                class="btn
                <?= $statusFilter === 'dibatalkan'
                    ? 'btn-danger'
                    : 'btn-outline-danger' ?>
                filter-btn">

                Dibatalkan

            </a>

        </div>

    </div>


    <!-- DAFTAR PESANAN -->

    <?php if (empty($pesanan)): ?>

        <div class="card">

            <div class="card-body text-center py-5">

                <div style="font-size:50px;">
                    🛒
                </div>

                <h5 class="mt-3">
                    Belum Ada Pesanan
                </h5>

                <p class="text-muted">

                    Anda belum memiliki pesanan
                    <?= $statusFilter
                        ? 'dengan status ' . e($statusFilter)
                        : '' ?>.

                </p>

                <a
                    href="index.php"
                    class="btn btn-primary">

                    Mulai Belanja

                </a>

            </div>

        </div>

    <?php else: ?>


        <div class="row g-4">

            <?php foreach ($pesanan as $order): ?>

                <div class="col-12">

                    <div class="card order-card">

                        <div class="card-body">

                            <div class="row align-items-center">


                                <!-- KODE PESANAN -->

                                <div class="col-md-2 mb-3 mb-md-0">

                                    <small class="text-muted">
                                        Kode Pesanan
                                    </small>

                                    <div class="order-code">

                                        <?= e(
                                            $order['kode_pesanan']
                                        ) ?>

                                    </div>

                                </div>


                                <!-- TANGGAL -->

                                <div class="col-md-2 mb-3 mb-md-0">

                                    <small class="text-muted">
                                        Tanggal
                                    </small>

                                    <div>

                                        <?= e(
                                            $order['tanggal_pesanan']
                                        ) ?>

                                    </div>

                                </div>


                                <!-- ITEM -->

                                <div class="col-md-1 mb-3 mb-md-0">

                                    <small class="text-muted">
                                        Item
                                    </small>

                                    <div class="fw-bold">

                                        <?= (int)
                                            $order['jumlah_item'] ?>

                                    </div>

                                </div>


                                <!-- PEMBAYARAN -->

                                <div class="col-md-2 mb-3 mb-md-0">

                                    <small class="text-muted">
                                        Pembayaran
                                    </small>

                                    <div>

                                        <?= e(
                                            strtoupper(
                                                $order['metode_pembayaran']
                                            )
                                        ) ?>

                                    </div>

                                </div>


                                <!-- TOTAL -->

                                <div class="col-md-2 mb-3 mb-md-0">

                                    <small class="text-muted">
                                        Total
                                    </small>

                                    <div class="total">

                                        <?= rupiah(
                                            $order['total_harga']
                                        ) ?>

                                    </div>

                                </div>


                                <!-- STATUS -->

                                <div class="col-md-1 mb-3 mb-md-0">

                                    <?= badgeStatusPembeli(
                                        $order['status']
                                    ) ?>

                                </div>


                                <!-- DETAIL -->

                                <div class="col-md-2 text-md-end">

                                    <a
                                        href="detail_pesanan.php?id=<?= (int)$order['id'] ?>"
                                        class="btn btn-outline-primary">

                                        Detail

                                    </a>

                                </div>

                            </div>


                            <!-- ALAMAT -->

                            <hr>

                            <div>

                                <small class="text-muted">
                                    Alamat Pengiriman
                                </small>

                                <div>

                                    <?= e(
                                        $order['alamat_pengiriman']
                                    ) ?>

                                </div>

                            </div>


                            <!-- AKSI -->

                            <?php if (
                                $order['status'] === 'menunggu'
                            ): ?>

                                <div class="mt-3">

                                    <a
                                        href="detail_pesanan.php?id=<?= (int)$order['id'] ?>"
                                        class="btn btn-primary">

                                        Lihat Detail / Batalkan

                                    </a>

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>