<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBELI
|--------------------------------------------------------------------------
*/

$stmtPembeli = $conn->prepare("
    SELECT id, nama
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmtPembeli->bind_param("i", $user_id);
$stmtPembeli->execute();

$pembeli = $stmtPembeli->get_result()->fetch_assoc();

if (!$pembeli) {
    die("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int) $pembeli['id'];

/*
|--------------------------------------------------------------------------
| AMBIL RIWAYAT PESANAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        kode_pesanan,
        tanggal_pesanan,
        total_harga,
        metode_pembayaran,
        status
    FROM pesanan
    WHERE pembeli_id = ?
    ORDER BY tanggal_pesanan DESC, id DESC
");

$stmt->bind_param("i", $pembeli_id);
$stmt->execute();

$pesanan = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

function statusBadge($status)
{
    switch ($status) {

        case 'menunggu':
            return '<span class="badge bg-warning text-dark">
                        Menunggu
                    </span>';

        case 'diproses':
            return '<span class="badge bg-primary">
                        Diproses
                    </span>';

        case 'dikirim':
            return '<span class="badge bg-info text-dark">
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
          content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pesanan - SIPESTA</title>

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

        .navbar-custom {
            background: #198754;
        }

        .page-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
        }

        .table-card {
            overflow: hidden;
        }

        .table th {
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .empty-box {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 60px;
            color: #adb5bd;
        }

        .order-code {
            font-weight: 600;
        }

        @media (max-width: 768px) {

            .table-responsive {
                border-radius: 12px;
            }

            .table {
                font-size: 14px;
            }

        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-dark navbar-custom">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold"
        >

            <i class="bi bi-shop me-2"></i>

            SIPESTA

        </a>

        <div>

            <a
                href="index.php"
                class="btn btn-light btn-sm"
            >

                <i class="bi bi-shop me-1"></i>

                Belanja

            </a>

        </div>

    </div>

</nav>


<!-- CONTENT -->

<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex
                flex-column
                flex-md-row
                justify-content-between
                align-items-md-center
                gap-3
                mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="bi bi-clock-history me-2"></i>

                Riwayat Pesanan

            </h3>

            <p class="text-muted mb-0">

                Daftar pesanan yang telah Anda buat.

            </p>

        </div>

        <div>

            <span class="text-muted">

                Pembeli:

            </span>

            <strong>

                <?= e($pembeli['nama']) ?>

            </strong>

        </div>

    </div>


    <!-- TABLE -->

    <div class="card page-card table-card">

        <div class="card-body p-0">

            <?php if ($pesanan->num_rows > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="px-4 py-3">
                                    No
                                </th>

                                <th class="py-3">
                                    Kode Pesanan
                                </th>

                                <th class="py-3">
                                    Tanggal
                                </th>

                                <th class="py-3">
                                    Total
                                </th>

                                <th class="py-3">
                                    Pembayaran
                                </th>

                                <th class="py-3">
                                    Status
                                </th>

                                <th class="py-3 text-center">
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php
                        $no = 1;
                        ?>

                        <?php while ($row = $pesanan->fetch_assoc()): ?>

                            <tr>

                                <td class="px-4">

                                    <?= $no++ ?>

                                </td>

                                <td>

                                    <span class="order-code">

                                        <?= e($row['kode_pesanan']) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= date(
                                        'd-m-Y H:i',
                                        strtotime($row['tanggal_pesanan'])
                                    ) ?>

                                </td>

                                <td>

                                    <strong>

                                        <?= rupiah($row['total_harga']) ?>

                                    </strong>

                                </td>

                                <td>

                                    <?= e(
                                        $row['metode_pembayaran']
                                    ) ?>

                                </td>

                                <td>

                                    <?= statusBadge(
                                        $row['status']
                                    ) ?>

                                </td>

                                <td class="text-center">

                                    <a
                                        href="detail_pesanan.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-outline-success btn-sm"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Detail

                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-box">

                    <div class="empty-icon">

                        <i class="bi bi-receipt"></i>

                    </div>

                    <h5 class="fw-bold mt-3">

                        Belum Ada Pesanan

                    </h5>

                    <p class="text-muted">

                        Anda belum melakukan pemesanan produk.

                    </p>

                    <a
                        href="index.php"
                        class="btn btn-success"
                    >

                        <i class="bi bi-shop me-1"></i>

                        Mulai Belanja

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>