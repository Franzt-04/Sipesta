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

$pesanan_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pesanan_id <= 0) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil data pembeli
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
| Ambil data pesanan
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        p.id,
        p.kode_pesanan,
        p.nama_penerima,
        p.no_hp,
        p.tanggal_pesanan,
        p.total_harga,
        p.alamat_pengiriman,
        p.catatan,
        p.metode_pembayaran,
        p.status
    FROM pesanan p
    WHERE p.id = ?
        AND p.pembeli_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $pesanan_id, $pembeli_id);
$stmt->execute();

$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau bukan milik Anda.");
}

/*
|--------------------------------------------------------------------------
| Ambil detail pesanan
|--------------------------------------------------------------------------
*/
$stmtDetail = $conn->prepare("
    SELECT
        dp.id,
        dp.produk_id,
        dp.jumlah,
        dp.harga,
        dp.subtotal,
        pr.nama_produk,
        pr.satuan
    FROM detail_pesanan dp
    INNER JOIN produk pr
        ON dp.produk_id = pr.id
    WHERE dp.pesanan_id = ?
    ORDER BY dp.id ASC
");

$stmtDetail->bind_param("i", $pesanan_id);
$stmtDetail->execute();

$detailPesanan = $stmtDetail->get_result();


function statusClass($status)
{
    switch ($status) {

        case 'menunggu':
            return 'bg-warning text-dark';

        case 'diproses':
            return 'bg-primary';

        case 'dikirim':
            return 'bg-info text-dark';

        case 'selesai':
            return 'bg-success';

        case 'dibatalkan':
            return 'bg-danger';

        default:
            return 'bg-secondary';
    }
}

function statusText($status)
{
    switch ($status) {

        case 'menunggu':
            return 'Menunggu';

        case 'diproses':
            return 'Diproses';

        case 'dikirim':
            return 'Dikirim';

        case 'selesai':
            return 'Selesai';

        case 'dibatalkan':
            return 'Dibatalkan';

        default:
            return ucfirst($status);
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
            content="width=device-width, initial-scale=1.0">

    <title>Pesanan Berhasil - SIPESTA</title>

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

        .success-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        .success-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #d1e7dd;
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }

        .order-code {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }

        .product-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .total-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <!-- SUCCESS -->

    <div class="card success-card mb-4">

        <div class="card-body text-center p-5">

            <div class="success-icon">

                <i class="bi bi-check-lg"></i>

            </div>

            <h2 class="fw-bold text-success">
                Pesanan Berhasil!
            </h2>

            <p class="text-muted mb-4">
                Terima kasih, pesanan Anda berhasil dibuat.
            </p>

            <div class="order-code">

                <small class="text-muted">
                    Kode Pesanan
                </small>

                <h4 class="fw-bold mb-0">
                    <?= e($pesanan['kode_pesanan']) ?>
                </h4>

            </div>

        </div>

    </div>


    <!-- INFORMASI PESANAN -->

    <div class="card success-card mb-4">

        <div class="card-header bg-white border-0 p-4">

            <h5 class="fw-bold mb-0">
                <i class="bi bi-receipt me-2"></i>
                Informasi Pesanan
            </h5>

        </div>

        <div class="card-body px-4">

            <div class="row g-4">

                <div class="col-md-6">

                    <small class="text-muted">
                        Nama Penerima
                    </small>

                    <div class="fw-semibold">
                        <?= e($pesanan['nama_penerima']) ?>
                    </div>

                </div>

                <div class="col-md-6">

                    <small class="text-muted">
                        No. HP
                    </small>

                    <div class="fw-semibold">
                        <?= e($pesanan['no_hp']) ?>
                    </div>

                </div>

                <div class="col-md-6">

                    <small class="text-muted">
                        Tanggal Pesanan
                    </small>

                    <div class="fw-semibold">

                        <?= date(
                            'd-m-Y H:i',
                            strtotime($pesanan['tanggal_pesanan'])
                        ) ?>

                    </div>

                </div>

                <div class="col-md-6">

                    <small class="text-muted">
                        Metode Pembayaran
                    </small>

                    <div class="fw-semibold">

                        <?= e($pesanan['metode_pembayaran']) ?>

                    </div>

                </div>

                <div class="col-12">

                    <small class="text-muted">
                        Alamat Pengiriman
                    </small>

                    <div class="fw-semibold">

                        <?= nl2br(e($pesanan['alamat_pengiriman'])) ?>

                    </div>

                </div>

                <?php if (!empty($pesanan['catatan'])): ?>

                <div class="col-12">

                    <small class="text-muted">
                        Catatan
                    </small>

                    <div class="fw-semibold">

                        <?= nl2br(e($pesanan['catatan'])) ?>

                    </div>

                </div>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- STATUS -->

    <div class="card success-card mb-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <small class="text-muted">
                        Status Pesanan
                    </small>

                    <div class="mt-1">

                        <span class="badge <?= statusClass($pesanan['status']) ?> px-3 py-2">

                            <?= statusText($pesanan['status']) ?>

                        </span>

                    </div>

                </div>

                <i class="bi bi-box-seam fs-1 text-secondary"></i>

            </div>

        </div>

    </div>


    <!-- DETAIL PRODUK -->

    <div class="card success-card mb-4">

        <div class="card-header bg-white border-0 p-4">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-cart-check me-2"></i>

                Detail Produk

            </h5>

        </div>

        <div class="card-body px-4">

            <?php if ($detailPesanan->num_rows > 0): ?>

                <?php while ($item = $detailPesanan->fetch_assoc()): ?>

                    <div class="product-item">

                        <div class="row align-items-center g-3">

                            <div class="col-md-6">

                                <div class="fw-semibold">

                                    <?= e($item['nama_produk']) ?>

                                </div>

                                <small class="text-muted">

                                    <?= e($item['jumlah']) ?>
                                    <?= e($item['satuan']) ?>

                                    ×

                                    <?= rupiah($item['harga']) ?>

                                </small>

                            </div>

                            <div class="col-md-6 text-md-end">

                                <div class="fw-bold">

                                    <?= rupiah($item['subtotal']) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="alert alert-warning">

                    Detail produk pesanan tidak ditemukan.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- TOTAL -->

    <div class="card success-card mb-4">

        <div class="card-body p-4">

            <div class="total-box">

                <div class="d-flex justify-content-between align-items-center">

                    <span class="fw-semibold">
                        Total Pesanan
                    </span>

                    <span class="fs-4 fw-bold text-success">

                        <?= rupiah($pesanan['total_harga']) ?>

                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- BUTTON -->

    <div class="d-flex flex-column flex-md-row gap-2 justify-content-center">

        <a
            href="index.php"
            class="btn btn-success px-4"
        >

            <i class="bi bi-shop me-1"></i>

            Belanja Lagi

        </a>

        <a
            href="riwayat_pesanan.php"
            class="btn btn-outline-success px-4"
        >

            <i class="bi bi-clock-history me-1"></i>

            Riwayat Pesanan

        </a>

    </div>

</div>

</body>

</html>