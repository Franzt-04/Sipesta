<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");

// ======================================================
// VALIDASI ID
// ======================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: penjual.php?error=ID penjual tidak valid.");
    exit;
}

$penjual_id = (int) $_GET['id'];

if ($penjual_id <= 0) {
    header("Location: penjual.php?error=ID penjual tidak valid.");
    exit;
}


// ======================================================
// DATA PENJUAL
// ======================================================

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.user_id,
        p.nama_usaha,
        p.alamat,
        u.username,
        u.role
    FROM penjual p
    INNER JOIN users u
        ON p.user_id = u.id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$penjual = $result->fetch_assoc();

$stmt->close();

if (!$penjual) {
    header("Location: penjual.php?error=Data penjual tidak ditemukan.");
    exit;
}


// ======================================================
// JUMLAH PRODUK
// ======================================================

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM produk
    WHERE penjual_id = ?
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$totalProduk = (int)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// PRODUK TERSEDIA
// ======================================================

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM produk
    WHERE penjual_id = ?
      AND status = 'tersedia'
      AND stok > 0
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$produkTersedia = (int)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// PRODUK HABIS
// ======================================================

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM produk
    WHERE penjual_id = ?
      AND (
          status = 'habis'
          OR stok <= 0
      )
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$produkHabis = (int)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// PRODUK NONAKTIF
// ======================================================

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM produk
    WHERE penjual_id = ?
      AND status = 'nonaktif'
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$produkNonaktif = (int)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// TOTAL STOK
// ======================================================

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(stok), 0) AS total
    FROM produk
    WHERE penjual_id = ?
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$totalStok = (float)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// JUMLAH PESANAN YANG MEMUAT PRODUK PENJUAL
// ======================================================

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT dp.pesanan_id) AS total
    FROM detail_pesanan dp
    INNER JOIN produk pr
        ON dp.produk_id = pr.id
    WHERE pr.penjual_id = ?
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$totalPesanan = (int)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// TOTAL PENJUALAN SELESAI
// ======================================================

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(dp.subtotal), 0) AS total
    FROM detail_pesanan dp
    INNER JOIN produk pr
        ON dp.produk_id = pr.id
    INNER JOIN pesanan ps
        ON dp.pesanan_id = ps.id
    WHERE pr.penjual_id = ?
      AND ps.status = 'selesai'
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$totalPenjualan = (float)($result->fetch_assoc()['total'] ?? 0);

$stmt->close();


// ======================================================
// DAFTAR PRODUK
// ======================================================

$stmt = $conn->prepare("
    SELECT
        pr.id,
        pr.nama_produk,
        pr.deskripsi,
        pr.harga,
        pr.satuan,
        pr.stok,
        pr.foto,
        pr.status,
        k.nama_kategori,
        pr.created_at
    FROM produk pr
    LEFT JOIN kategori k
        ON pr.kategori_id = k.id
    WHERE pr.penjual_id = ?
    ORDER BY pr.created_at DESC
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$produkList = $stmt->get_result();


// ======================================================
// BADGE PRODUK
// ======================================================

function badgeProdukDetailAdmin($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'tersedia':
            return '<span class="badge bg-success">Tersedia</span>';

        case 'habis':
            return '<span class="badge bg-danger">Habis</span>';

        case 'nonaktif':
            return '<span class="badge bg-secondary">Nonaktif</span>';

        default:
            return '<span class="badge bg-secondary">' .
                e($status ?: 'Tidak diketahui') .
                '</span>';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Detail Penjual - SIPESTA
    </title>

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

        .card-custom {
            border: 0;
            border-radius: 16px;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 34px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 21px;
        }

        .table > :not(caption) > * > * {
            vertical-align: middle;
        }

        .product-image {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-placeholder {
            width: 55px;
            height: 55px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f0f0f0;
            color: #999;
            font-size: 22px;
        }

    </style>

</head>

<body>


<!-- ==================================================
     NAVBAR
================================================== -->

<nav class="navbar navbar-dark bg-success shadow-sm">

    <div class="container-fluid">

        <a href="index.php"
           class="navbar-brand">

            <i class="bi bi-shop"></i>

            SIPESTA Admin

        </a>


        <div>

            <a href="penjual.php"
               class="btn btn-light btn-sm me-2">

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>

            <a href="../logout.php"
               class="btn btn-outline-light btn-sm">

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </div>

    </div>

</nav>



<div class="container-fluid py-4">


    <!-- ==================================================
         JUDUL
    ================================================== -->

    <div class="mb-4">

        <h3 class="fw-bold mb-1">

            <i class="bi bi-person-vcard text-success"></i>

            Detail Penjual

        </h3>

        <p class="text-muted mb-0">

            Informasi dan aktivitas penjual.

        </p>

    </div>



    <!-- ==================================================
         PROFIL PENJUAL
    ================================================== -->

    <div class="card card-custom shadow-sm mb-4">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-md-8">

                    <div class="d-flex align-items-center">

                        <div class="profile-icon
                                    bg-success-subtle
                                    text-success
                                    me-3">

                            <i class="bi bi-shop"></i>

                        </div>

                        <div>

                            <h4 class="fw-bold mb-1">

                                <?= e(
                                    $penjual['nama_usaha']
                                ) ?>

                            </h4>

                            <div class="text-muted">

                                <i class="bi bi-person"></i>

                                <?= e(
                                    $penjual['username']
                                ) ?>

                            </div>

                            <div class="text-muted">

                                <i class="bi bi-geo-alt"></i>

                                <?= e(
                                    $penjual['alamat'] ?: '-'
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 mt-3 mt-md-0
                            text-md-end">

                    <span class="badge bg-success fs-6">

                        <i class="bi bi-person-check"></i>

                        <?= e($penjual['role']) ?>

                    </span>

                    <div class="small text-muted mt-2">

                        User ID:
                        <?= (int)$penjual['user_id'] ?>

                        <br>

                        Penjual ID:
                        <?= (int)$penjual['id'] ?>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- ==================================================
         STATISTIK
    ================================================== -->

    <div class="row g-3 mb-4">


        <!-- Total Produk -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-primary-subtle
                                text-primary
                                mb-3">

                        <i class="bi bi-box-seam"></i>

                    </div>

                    <small class="text-muted">
                        Total Produk
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= $totalProduk ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- Tersedia -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-success-subtle
                                text-success
                                mb-3">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <small class="text-muted">
                        Tersedia
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= $produkTersedia ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- Habis -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-danger-subtle
                                text-danger
                                mb-3">

                        <i class="bi bi-exclamation-circle"></i>

                    </div>

                    <small class="text-muted">
                        Habis
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= $produkHabis ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- Nonaktif -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-secondary-subtle
                                text-secondary
                                mb-3">

                        <i class="bi bi-eye-slash"></i>

                    </div>

                    <small class="text-muted">
                        Nonaktif
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= $produkNonaktif ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- Pesanan -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-warning-subtle
                                text-warning
                                mb-3">

                        <i class="bi bi-cart-check"></i>

                    </div>

                    <small class="text-muted">
                        Pesanan
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= $totalPesanan ?>
                    </h3>

                </div>

            </div>

        </div>


        <!-- Total Stok -->

        <div class="col-6 col-md-4 col-xl-2">

            <div class="card card-custom shadow-sm h-100">

                <div class="card-body">

                    <div class="stat-icon
                                bg-info-subtle
                                text-info
                                mb-3">

                        <i class="bi bi-boxes"></i>

                    </div>

                    <small class="text-muted">
                        Total Stok
                    </small>

                    <h3 class="fw-bold mb-0">

                        <?= e(
                            rtrim(
                                rtrim(
                                    number_format(
                                        $totalStok,
                                        2,
                                        ',',
                                        '.'
                                    ),
                                    '0'
                                ),
                                ','
                            )
                        ) ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>



    <!-- ==================================================
         TOTAL PENJUALAN
    ================================================== -->

    <div class="card card-custom shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex align-items-center">

                <div class="stat-icon
                            bg-success-subtle
                            text-success
                            me-3">

                    <i class="bi bi-cash-stack"></i>

                </div>

                <div>

                    <small class="text-muted">

                        Total Penjualan
                        dari Pesanan Selesai

                    </small>

                    <h3 class="fw-bold mb-0">

                        <?= rupiah($totalPenjualan) ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>



    <!-- ==================================================
         DAFTAR PRODUK
    ================================================== -->

    <div class="card card-custom shadow-sm">

        <div class="card-header bg-white py-3">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-box-seam"></i>

                Produk Milik Penjual

            </h5>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Produk</th>

                            <th>Kategori</th>

                            <th>Harga</th>

                            <th>Stok</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($produkList->num_rows > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($produk = $produkList->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= $no++ ?>
                                </td>


                                <td>

                                    <div class="d-flex
                                                align-items-center">

                                        <?php if (
                                            !empty($produk['foto']) &&
                                            file_exists(
                                                "../uploads/produk/" .
                                                $produk['foto']
                                            )
                                        ): ?>

                                            <img
                                                src="../uploads/produk/<?= e($produk['foto']) ?>"
                                                class="product-image me-2"
                                                alt="<?= e($produk['nama_produk']) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="product-placeholder me-2">

                                                <i class="bi bi-image"></i>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <div class="fw-semibold">

                                                <?= e(
                                                    $produk['nama_produk']
                                                ) ?>

                                            </div>

                                            <small class="text-muted">

                                                ID:
                                                <?= (int)$produk['id'] ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <?= e(
                                        $produk['nama_kategori'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= rupiah(
                                        $produk['harga']
                                    ) ?>

                                    <small class="text-muted">

                                        /
                                        <?= e(
                                            $produk['satuan']
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= e(
                                        $produk['stok']
                                    ) ?>

                                    <small class="text-muted">

                                        <?= e(
                                            $produk['satuan']
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= badgeProdukDetailAdmin(
                                        $produk['status']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6"
                                class="text-center py-5">

                                <i class="bi bi-box-seam
                                          fs-1 text-muted"></i>

                                <p class="text-muted mt-2 mb-0">

                                    Penjual ini belum memiliki
                                    produk.

                                </p>

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