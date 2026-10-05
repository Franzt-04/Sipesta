<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("penjual");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA PENJUAL
|--------------------------------------------------------------------------
*/

$stmtPenjual = $conn->prepare("
    SELECT
        id,
        nama_usaha,
        alamat
    FROM penjual
    WHERE user_id = ?
    LIMIT 1
");

$stmtPenjual->bind_param("i", $user_id);
$stmtPenjual->execute();

$penjual = $stmtPenjual->get_result()->fetch_assoc();

if (!$penjual) {
    die("Data penjual tidak ditemukan.");
}

$penjual_id = (int) $penjual['id'];


/*
|--------------------------------------------------------------------------
| FILTER STATUS
|--------------------------------------------------------------------------
*/

$status = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';


/*
|--------------------------------------------------------------------------
| QUERY PESANAN
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.kode_pesanan,
        p.nama_penerima,
        p.no_hp,
        p.tanggal_pesanan,
        p.metode_pembayaran,
        p.status,

        SUM(dp.subtotal) AS total_produk,
        SUM(dp.jumlah) AS jumlah_produk

    FROM pesanan p

    INNER JOIN detail_pesanan dp
        ON p.id = dp.pesanan_id

    INNER JOIN produk pr
        ON dp.produk_id = pr.id

    WHERE pr.penjual_id = ?
";

$params = [$penjual_id];
$types = "i";


/*
|--------------------------------------------------------------------------
| FILTER STATUS
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= "
        AND p.status = ?
    ";

    $params[] = $status;
    $types .= "s";
}


$sql .= "
    GROUP BY
        p.id,
        p.kode_pesanan,
        p.nama_penerima,
        p.no_hp,
        p.tanggal_pesanan,
        p.metode_pembayaran,
        p.status

    ORDER BY p.tanggal_pesanan DESC
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$pesanan = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| FUNGSI STATUS
|--------------------------------------------------------------------------
*/

function badgeStatusPenjual($status)
{
    switch (strtolower($status)) {

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
                        Tidak diketahui
                    </span>';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pesanan Penjual - SIPESTA</title>

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

        .card-custom {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
        }

        .table thead th {
            white-space: nowrap;
        }

        .order-code {
            font-weight: 700;
        }

        .filter-btn {
            border-radius: 10px;
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

            SIPESTA Penjual

        </a>

        <a
            href="../logout.php"
            class="btn btn-light btn-sm"
        >

            <i class="bi bi-box-arrow-right me-1"></i>

            Logout

        </a>

    </div>

</nav>


<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex
                justify-content-between
                align-items-center
                mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="bi bi-cart-check me-2"></i>

                Pesanan

            </h3>

            <div class="text-muted">

                Kelola pesanan produk Anda

            </div>

        </div>

        <a
            href="index.php"
            class="btn btn-outline-success"
        >

            <i class="bi bi-speedometer2 me-1"></i>

            Dashboard

        </a>

    </div>


    <!-- FILTER -->

    <div class="card card-custom mb-4">

        <div class="card-body">

            <div class="fw-semibold mb-3">

                Filter Status

            </div>

            <div class="d-flex flex-wrap gap-2">

                <a
                    href="pesanan.php"
                    class="btn
                        <?= $status === ''
                            ? 'btn-success'
                            : 'btn-outline-success'
                        ?>
                        filter-btn"
                >

                    Semua

                </a>

                <a
                    href="pesanan.php?status=menunggu"
                    class="btn
                        <?= $status === 'menunggu'
                            ? 'btn-warning'
                            : 'btn-outline-warning'
                        ?>
                        filter-btn"
                >

                    Menunggu

                </a>

                <a
                    href="pesanan.php?status=diproses"
                    class="btn
                        <?= $status === 'diproses'
                            ? 'btn-primary'
                            : 'btn-outline-primary'
                        ?>
                        filter-btn"
                >

                    Diproses

                </a>

                <a
                    href="pesanan.php?status=dikirim"
                    class="btn
                        <?= $status === 'dikirim'
                            ? 'btn-info'
                            : 'btn-outline-info'
                        ?>
                        filter-btn"
                >

                    Dikirim

                </a>

                <a
                    href="pesanan.php?status=selesai"
                    class="btn
                        <?= $status === 'selesai'
                            ? 'btn-success'
                            : 'btn-outline-success'
                        ?>
                        filter-btn"
                >

                    Selesai

                </a>

                <a
                    href="pesanan.php?status=dibatalkan"
                    class="btn
                        <?= $status === 'dibatalkan'
                            ? 'btn-danger'
                            : 'btn-outline-danger'
                        ?>
                        filter-btn"
                >

                    Dibatalkan

                </a>

            </div>

        </div>

    </div>


    <!-- TABLE -->

    <div class="card card-custom">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table
                              table-hover
                              align-middle">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Kode Pesanan</th>

                            <th>Pembeli</th>

                            <th>Tanggal</th>

                            <th>Produk</th>

                            <th>Total</th>

                            <th>Pembayaran</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($pesanan->num_rows > 0): ?>

                        <?php
                        $no = 1;
                        ?>

                        <?php while (
                            $row = $pesanan->fetch_assoc()
                        ): ?>

                            <tr>

                                <td>
                                    <?= $no++ ?>
                                </td>

                                <td>

                                    <div class="order-code">

                                        <?= e(
                                            $row['kode_pesanan']
                                        ) ?>

                                    </div>

                                </td>

                                <td>

                                    <?= e(
                                        $row['nama_penerima']
                                    ) ?>

                                </td>

                                <td>

                                    <?= date(
                                        'd-m-Y H:i',
                                        strtotime(
                                            $row['tanggal_pesanan']
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= e(
                                        $row['jumlah_produk']
                                    ) ?>

                                    item

                                </td>

                                <td>

                                    <strong>

                                        <?= rupiah(
                                            $row['total_produk']
                                        ) ?>

                                    </strong>

                                </td>

                                <td>

                                    <?= e(
                                        $row['metode_pembayaran']
                                    ) ?>

                                </td>

                                <td>

                                    <?= badgeStatusPenjual(
                                        $row['status']
                                    ) ?>

                                </td>

                                <td>

                                    <a
                                        href="detail_pesanan.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-sm btn-outline-success"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Detail

                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-inbox fs-1 text-muted">
                                </i>

                                <div class="mt-2 text-muted">

                                    Belum ada pesanan.

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>