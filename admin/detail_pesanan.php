<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: pesanan.php?error=ID pesanan tidak valid");
    exit;
}


/*
|--------------------------------------------------------------------------
| Data Pesanan
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        pe.*,
        pb.nama AS nama_pembeli,
        pb.nama_toko,
        pb.jenis_kelamin,
        pb.lama_usaha,
        u.username
    FROM pesanan pe
    INNER JOIN pembeli pb
        ON pb.id = pe.pembeli_id
    LEFT JOIN users u
        ON u.id = pb.user_id
    WHERE pe.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {

    header(
        "Location: pesanan.php?error=Pesanan tidak ditemukan"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Detail Produk
|--------------------------------------------------------------------------
*/

$stmtDetail = $conn->prepare("
    SELECT
        dp.id,
        dp.produk_id,
        dp.jumlah,
        dp.harga,
        dp.subtotal,
        p.nama_produk,
        p.satuan,
        p.foto,
        pen.nama_usaha,
        k.nama_kategori
    FROM detail_pesanan dp
    INNER JOIN produk p
        ON p.id = dp.produk_id
    LEFT JOIN penjual pen
        ON pen.id = p.penjual_id
    LEFT JOIN kategori k
        ON k.id = p.kategori_id
    WHERE dp.pesanan_id = ?
    ORDER BY dp.id ASC
");

$stmtDetail->bind_param("i", $id);
$stmtDetail->execute();

$detailResult = $stmtDetail->get_result();


/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

function badgeDetailPesananAdmin($status)
{
    switch ($status) {

        case 'menunggu':
            return '<span class="badge bg-warning text-dark">
                        <i class="fa-solid fa-clock"></i>
                        Menunggu
                    </span>';

        case 'diproses':
            return '<span class="badge bg-primary">
                        <i class="fa-solid fa-gears"></i>
                        Diproses
                    </span>';

        case 'dikirim':
            return '<span class="badge bg-info text-dark">
                        <i class="fa-solid fa-truck"></i>
                        Dikirim
                    </span>';

        case 'selesai':
            return '<span class="badge bg-success">
                        <i class="fa-solid fa-circle-check"></i>
                        Selesai
                    </span>';

        case 'dibatalkan':
            return '<span class="badge bg-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
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

    <title>
        Detail Pesanan <?= e($pesanan['kode_pesanan']) ?>
        - SIPESTA
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .card {
            border: 0;
            border-radius: 15px;
        }

        .info-label {
            color: #6c757d;
            font-size: 13px;
        }

        .info-value {
            font-weight: 600;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

    </style>

</head>

<body>

<div class="container-fluid">

    <div class="row">


        <!-- SIDEBAR -->

        <div class="col-md-2 p-0 bg-dark text-white min-vh-100">

            <div class="p-3">

                <h4>

                    <i class="fa-solid fa-store"></i>

                    SIPESTA

                </h4>

                <small>
                    Panel Administrator
                </small>

            </div>

            <hr>


            <a
                href="index.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-gauge me-2"></i>

                Dashboard

            </a>


            <a
                href="penjual.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-users me-2"></i>

                Data Penjual

            </a>


            <a
                href="pembeli.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-user-group me-2"></i>

                Data Pembeli

            </a>


            <a
                href="produk.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-box me-2"></i>

                Data Produk

            </a>


            <a
                href="kategori.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-layer-group me-2"></i>

                Kategori

            </a>


            <a
                href="pesanan.php"
                class="text-white text-decoration-none d-block p-3 bg-secondary"
            >

                <i class="fa-solid fa-cart-shopping me-2"></i>

                Pesanan

            </a>


            <hr>


            <a
                href="../logout.php"
                class="text-white text-decoration-none d-block p-3"
            >

                <i class="fa-solid fa-right-from-bracket me-2"></i>

                Logout

            </a>

        </div>


        <!-- CONTENT -->

        <div class="col-md-10 p-4">


            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2>

                        <i class="fa-solid fa-file-invoice"></i>

                        Detail Pesanan

                    </h2>

                    <p class="text-muted mb-0">

                        <?= e($pesanan['kode_pesanan']) ?>

                    </p>

                </div>


                <a
                    href="pesanan.php"
                    class="btn btn-secondary"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Kembali

                </a>

            </div>


            <!-- STATUS -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <small class="text-muted">
                                Status Pesanan
                            </small>

                            <div class="mt-2">

                                <?= badgeDetailPesananAdmin($pesanan['status']) ?>

                            </div>

                        </div>


                        <div class="col-md-4 text-md-end mt-3 mt-md-0">

                            <small class="text-muted">
                                Total Pesanan
                            </small>

                            <h3 class="text-success mb-0">

                                <?= rupiah($pesanan['total_harga']) ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>


            <!-- INFORMASI PESANAN -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-file-lines"></i>

                        Informasi Pesanan

                    </strong>

                </div>


                <div class="card-body">

                    <div class="row">


                        <div class="col-md-6">


                            <div class="mb-3">

                                <div class="info-label">
                                    Kode Pesanan
                                </div>

                                <div class="info-value">
                                    <?= e($pesanan['kode_pesanan']) ?>
                                </div>

                            </div>


                            <div class="mb-3">

                                <div class="info-label">
                                    Tanggal Pesanan
                                </div>

                                <div class="info-value">

                                    <?= !empty($pesanan['tanggal_pesanan'])
                                        ? date(
                                            'd-m-Y H:i',
                                            strtotime($pesanan['tanggal_pesanan'])
                                        )
                                        : '-'
                                    ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <div class="info-label">
                                    Metode Pembayaran
                                </div>

                                <div class="info-value">

                                    <?= e(
                                        $pesanan['metode_pembayaran'] ?: '-'
                                    ) ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <div class="info-label">
                                    Catatan
                                </div>

                                <div class="info-value">

                                    <?= nl2br(
                                        e($pesanan['catatan'] ?: '-')
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <div class="col-md-6">


                            <div class="mb-3">

                                <div class="info-label">
                                    Nama Penerima
                                </div>

                                <div class="info-value">

                                    <?= e(
                                        $pesanan['nama_penerima']
                                    ) ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <div class="info-label">
                                    No. HP
                                </div>

                                <div class="info-value">

                                    <?= e(
                                        $pesanan['no_hp'] ?: '-'
                                    ) ?>

                                </div>

                            </div>


                            <div class="mb-3">

                                <div class="info-label">
                                    Alamat Pengiriman
                                </div>

                                <div class="info-value">

                                    <?= nl2br(
                                        e(
                                            $pesanan['alamat_pengiriman']
                                            ?: '-'
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PEMBELI -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-user"></i>

                        Informasi Pembeli

                    </strong>

                </div>


                <div class="card-body">

                    <div class="row">


                        <div class="col-md-4">

                            <div class="info-label">
                                Nama
                            </div>

                            <div class="info-value">
                                <?= e($pesanan['nama_pembeli']) ?>
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Username
                            </div>

                            <div class="info-value">
                                <?= e($pesanan['username'] ?: '-') ?>
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Nama Toko
                            </div>

                            <div class="info-value">
                                <?= e($pesanan['nama_toko'] ?: '-') ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PRODUK -->

            <div class="card shadow-sm">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-box"></i>

                        Produk yang Dipesan

                    </strong>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead class="table-dark">

                                <tr>

                                    <th>No</th>

                                    <th>Produk</th>

                                    <th>Penjual</th>

                                    <th>Kategori</th>

                                    <th>Harga</th>

                                    <th>Jumlah</th>

                                    <th>Subtotal</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php

                            $no = 1;
                            $totalDetail = 0;

                            ?>

                            <?php if ($detailResult->num_rows > 0): ?>

                                <?php while ($detail = $detailResult->fetch_assoc()): ?>

                                    <?php
                                    $totalDetail += (float)$detail['subtotal'];
                                    ?>

                                    <tr>


                                        <td>
                                            <?= $no++ ?>
                                        </td>


                                        <td>

                                            <div class="d-flex align-items-center gap-2">

                                                <?php if (!empty($detail['foto'])): ?>

                                                    <img
                                                        src="../uploads/produk/<?= e($detail['foto']) ?>"
                                                        class="product-img"
                                                        alt="<?= e($detail['nama_produk']) ?>"
                                                    >

                                                <?php else: ?>

                                                    <div class="no-image">

                                                        <i class="fa-solid fa-image"></i>

                                                    </div>

                                                <?php endif; ?>


                                                <strong>

                                                    <?= e(
                                                        $detail['nama_produk']
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </td>


                                        <td>

                                            <?= e(
                                                $detail['nama_usaha']
                                                ?: '-'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                $detail['nama_kategori']
                                                ?: '-'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= rupiah(
                                                $detail['harga']
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                $detail['jumlah']
                                            ) ?>

                                            <?= e(
                                                $detail['satuan']
                                                ?: ''
                                            ) ?>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= rupiah(
                                                    $detail['subtotal']
                                                ) ?>

                                            </strong>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center py-4"
                                    >

                                        Tidak ada detail produk.

                                    </td>

                                </tr>

                            <?php endif; ?>

                            </tbody>


                            <tfoot>

                                <tr>

                                    <th
                                        colspan="6"
                                        class="text-end"
                                    >

                                        Total

                                    </th>

                                    <th>

                                        <?= rupiah($totalDetail) ?>

                                    </th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>