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
| AMBIL ID PESANAN
|--------------------------------------------------------------------------
*/

$pesanan_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pesanan_id <= 0) {
    header("Location: riwayat_pesanan.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBELI
|--------------------------------------------------------------------------
*/

$stmtPembeli = $conn->prepare("
    SELECT
        id,
        nama,
        no_hp,
        alamat,
        nama_toko
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmtPembeli->bind_param("i", $user_id);
$stmtPembeli->execute();

$pembeli = $stmtPembeli->get_result()->fetch_assoc();

$stmtPembeli->close();

if (!$pembeli) {
    die("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int) $pembeli['id'];


/*
|--------------------------------------------------------------------------
| AMBIL DATA PESANAN
|--------------------------------------------------------------------------
*/

$stmtPesanan = $conn->prepare("
    SELECT
        id,
        kode_pesanan,
        pembeli_id,
        nama_penerima,
        no_hp,
        tanggal_pesanan,
        total_harga,
        alamat_pengiriman,
        catatan,
        metode_pembayaran,
        status
    FROM pesanan
    WHERE id = ?
      AND pembeli_id = ?
    LIMIT 1
");

$stmtPesanan->bind_param(
    "ii",
    $pesanan_id,
    $pembeli_id
);

$stmtPesanan->execute();

$pesanan = $stmtPesanan->get_result()->fetch_assoc();

$stmtPesanan->close();

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau bukan milik Anda.");
}


/*
|--------------------------------------------------------------------------
| STATUS PESANAN
|--------------------------------------------------------------------------
*/

$statusPesanan = strtolower(
    trim($pesanan['status'] ?? 'menunggu')
);


/*
|--------------------------------------------------------------------------
| TIMELINE PESANAN
|--------------------------------------------------------------------------
*/

$statusTimeline = [
    'menunggu' => 1,
    'diproses' => 2,
    'dikirim'  => 3,
    'selesai'  => 4
];

$tahapSekarang = $statusTimeline[$statusPesanan] ?? 1;


/*
|--------------------------------------------------------------------------
| PROGRESS PESANAN
|--------------------------------------------------------------------------
*/

$progress = 25;

switch ($statusPesanan) {

    case 'menunggu':
        $progress = 25;
        break;

    case 'diproses':
        $progress = 50;
        break;

    case 'dikirim':
        $progress = 75;
        break;

    case 'selesai':
        $progress = 100;
        break;

    case 'dibatalkan':
        $progress = 0;
        break;
}


/*
|--------------------------------------------------------------------------
| AMBIL DETAIL PRODUK
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
        pr.satuan,
        pr.foto,
        pen.nama_usaha
    FROM detail_pesanan dp

    INNER JOIN produk pr
        ON dp.produk_id = pr.id

    INNER JOIN penjual pen
        ON pr.penjual_id = pen.id

    WHERE dp.pesanan_id = ?

    ORDER BY dp.id ASC
");

$stmtDetail->bind_param(
    "i",
    $pesanan_id
);

$stmtDetail->execute();

$detailPesanan = $stmtDetail->get_result();


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

function statusBadgeDetail($status)
{
    switch ($status) {

        case 'menunggu':

            return '<span class="badge bg-warning text-dark px-3 py-2">
                        <i class="bi bi-clock me-1"></i>
                        Menunggu
                    </span>';

        case 'diproses':

            return '<span class="badge bg-primary px-3 py-2">
                        <i class="bi bi-box-seam me-1"></i>
                        Diproses
                    </span>';

        case 'dikirim':

            return '<span class="badge bg-info text-dark px-3 py-2">
                        <i class="bi bi-truck me-1"></i>
                        Dikirim
                    </span>';

        case 'selesai':

            return '<span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>
                        Selesai
                    </span>';

        case 'dibatalkan':

            return '<span class="badge bg-danger px-3 py-2">
                        <i class="bi bi-x-circle me-1"></i>
                        Dibatalkan
                    </span>';

        default:

            return '<span class="badge bg-secondary px-3 py-2">
                        ' . e($status) . '
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
        Detail Pesanan - SIPESTA
    </title>

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
            box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
        }


        .order-code {
            font-size: 20px;
            font-weight: 700;
        }


        .product-row {
            border-bottom: 1px solid #eeeeee;
            padding: 18px 0;
        }


        .product-row:last-child {
            border-bottom: none;
        }


        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            background: #f1f3f5;
        }


        .product-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 30px;
        }


        .info-label {
            color: #6c757d;
            font-size: 14px;
        }


        .info-value {
            font-weight: 600;
        }


        .total-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }


        .address-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }


        .review-button {
            margin-top: 8px;
        }


        /* =========================================================
           TIMELINE PESANAN
           ========================================================= */

        .timeline-wrapper {
            position: relative;
            padding: 15px 10px 10px 55px;
        }


        .timeline-wrapper::before {
            content: "";
            position: absolute;
            left: 27px;
            top: 35px;
            bottom: 35px;
            width: 3px;
            background: #dee2e6;
            border-radius: 10px;
        }


        .timeline-item {
            position: relative;
            margin-bottom: 35px;
        }


        .timeline-item:last-child {
            margin-bottom: 0;
        }


        .timeline-icon {
            position: absolute;
            left: -55px;
            top: -2px;

            width: 42px;
            height: 42px;

            border-radius: 50%;

            background: #e9ecef;
            color: #6c757d;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;

            border: 4px solid #fff;

            box-shadow: 0 3px 10px rgba(0, 0, 0, .08);

            z-index: 2;
        }


        .timeline-item.completed .timeline-icon {
            background: #198754;
            color: #fff;
        }


        .timeline-item.current .timeline-icon {
            background: #0d6efd;
            color: #fff;

            box-shadow:
                0 0 0 5px rgba(13, 110, 253, .15),
                0 3px 10px rgba(0, 0, 0, .10);
        }


        .timeline-item.completed h6,
        .timeline-item.current h6 {
            font-weight: 700;
        }


        .timeline-title {
            font-size: 16px;
            margin-bottom: 4px;
        }


        .timeline-desc {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }


        .timeline-item.completed .timeline-desc,
        .timeline-item.current .timeline-desc {
            color: #495057;
        }


        /* =========================================================
           PROGRESS BAR
           ========================================================= */

        .progress-custom {
            height: 10px;
            border-radius: 20px;
            background: #e9ecef;
        }


        .progress-custom .progress-bar {
            border-radius: 20px;
        }


        /* =========================================================
           STATUS DIBATALKAN
           ========================================================= */

        .cancelled-box {
            background: #fff5f5;
            border: 1px solid #f5c2c7;
            border-radius: 15px;
            padding: 20px;
        }


        .cancelled-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #dc3545;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }


        @media (max-width: 768px) {

            .product-image,
            .product-placeholder {
                width: 65px;
                height: 65px;
            }


            .order-code {
                font-size: 17px;
            }


            .timeline-wrapper {
                padding-left: 50px;
            }


            .timeline-icon {
                left: -50px;
                width: 38px;
                height: 38px;
            }


            .timeline-wrapper::before {
                left: 25px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
     ========================================================= -->

<nav class="navbar navbar-dark navbar-custom">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold"
        >

            <i class="bi bi-shop me-2"></i>

            SIPESTA

        </a>


        <a
            href="riwayat_pesanan.php"
            class="btn btn-light btn-sm"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Riwayat Pesanan

        </a>

    </div>

</nav>


<div class="container py-4">


<!-- =========================================================
     SUCCESS
     ========================================================= -->

<?php if (!empty($_GET['success'])): ?>

    <div class="alert alert-success alert-dismissible fade show">

        <i class="bi bi-check-circle me-2"></i>

        <?= e($_GET['success']) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =========================================================
     ERROR
     ========================================================= -->

<?php if (!empty($_GET['error'])): ?>

    <div class="alert alert-danger alert-dismissible fade show">

        <i class="bi bi-exclamation-triangle me-2"></i>

        <?= e($_GET['error']) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =========================================================
     HEADER PESANAN
     ========================================================= -->

<div class="card card-custom mb-4">

    <div class="card-body p-4">

        <div class="row align-items-center">

            <div class="col-md-7">

                <div class="info-label mb-1">
                    Kode Pesanan
                </div>

                <div class="order-code">

                    <?= e($pesanan['kode_pesanan']) ?>

                </div>

                <div class="text-muted mt-2">

                    <i class="bi bi-calendar3 me-1"></i>

                    <?= date(
                        'd-m-Y H:i',
                        strtotime($pesanan['tanggal_pesanan'])
                    ) ?>

                </div>

            </div>


            <div class="col-md-5 text-md-end mt-3 mt-md-0">

                <div class="info-label mb-2">
                    Status Pesanan
                </div>

                <?= statusBadgeDetail($statusPesanan) ?>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     TRACKING PESANAN
     ========================================================= -->

<div class="card card-custom mb-4">

    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="fw-bold mb-1">

                    <i class="bi bi-truck me-2 text-success"></i>

                    Perjalanan Pesanan

                </h5>

                <div class="text-muted small">

                    Pantau perkembangan pesanan Anda

                </div>

            </div>


            <?php if ($statusPesanan !== 'dibatalkan'): ?>

                <span class="badge bg-light text-dark">

                    <?= $progress ?>%

                </span>

            <?php endif; ?>

        </div>


        <?php if ($statusPesanan === 'dibatalkan'): ?>

            <!-- PESANAN DIBATALKAN -->

            <div class="cancelled-box">

                <div class="d-flex align-items-center gap-3">

                    <div class="cancelled-icon">

                        <i class="bi bi-x-lg"></i>

                    </div>


                    <div>

                        <h6 class="fw-bold text-danger mb-1">

                            Pesanan Dibatalkan

                        </h6>

                        <div class="text-muted small">

                            Pesanan ini telah dibatalkan dan tidak dapat diproses kembali.

                        </div>

                    </div>

                </div>

            </div>


        <?php else: ?>


            <!-- PROGRESS -->

            <div class="mb-4">

                <div class="d-flex justify-content-between mb-2">

                    <small class="text-muted">
                        Progress Pesanan
                    </small>

                    <small class="fw-bold">
                        <?= $progress ?>%
                    </small>

                </div>


                <div class="progress progress-custom">

                    <div
                        class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                        role="progressbar"
                        style="width: <?= $progress ?>%;"
                    ></div>

                </div>

            </div>


            <!-- TIMELINE -->

            <div class="timeline-wrapper">


                <!-- PESANAN DIBUAT -->

                <div class="timeline-item
                    <?= $tahapSekarang > 1
                        ? 'completed'
                        : 'current'
                    ?>"
                >

                    <div class="timeline-icon">

                        <?php if ($tahapSekarang > 1): ?>

                            <i class="bi bi-check-lg"></i>

                        <?php else: ?>

                            <i class="bi bi-cart-check"></i>

                        <?php endif; ?>

                    </div>


                    <div>

                        <div class="timeline-title">

                            Pesanan Dibuat

                        </div>

                        <div class="timeline-desc">

                            Pesanan berhasil dibuat dan sedang menunggu diproses oleh penjual.

                        </div>

                    </div>

                </div>


                <!-- DIPROSES -->

                <div class="timeline-item
                    <?=
                        $tahapSekarang > 2
                            ? 'completed'
                            : (
                                $tahapSekarang === 2
                                    ? 'current'
                                    : ''
                            )
                    ?>"
                >

                    <div class="timeline-icon">

                        <?php if ($tahapSekarang > 2): ?>

                            <i class="bi bi-check-lg"></i>

                        <?php else: ?>

                            <i class="bi bi-box-seam"></i>

                        <?php endif; ?>

                    </div>


                    <div>

                        <div class="timeline-title">

                            Pesanan Diproses

                        </div>

                        <div class="timeline-desc">

                            Penjual sedang menyiapkan produk pesanan Anda.

                        </div>

                    </div>

                </div>


                <!-- DIKIRIM -->

                <div class="timeline-item
                    <?=
                        $tahapSekarang > 3
                            ? 'completed'
                            : (
                                $tahapSekarang === 3
                                    ? 'current'
                                    : ''
                            )
                    ?>"
                >

                    <div class="timeline-icon">

                        <?php if ($tahapSekarang > 3): ?>

                            <i class="bi bi-check-lg"></i>

                        <?php else: ?>

                            <i class="bi bi-truck"></i>

                        <?php endif; ?>

                    </div>


                    <div>

                        <div class="timeline-title">

                            Pesanan Dikirim

                        </div>

                        <div class="timeline-desc">

                            Pesanan sedang dalam perjalanan menuju alamat pengiriman Anda.

                        </div>

                    </div>

                </div>


                <!-- SELESAI -->

                <div class="timeline-item
                    <?= $tahapSekarang === 4 ? 'completed current' : '' ?>"
                >

                    <div class="timeline-icon">

                        <?php if ($tahapSekarang >= 4): ?>

                            <i class="bi bi-check-lg"></i>

                        <?php else: ?>

                            <i class="bi bi-house-check"></i>

                        <?php endif; ?>

                    </div>


                    <div>

                        <div class="timeline-title">

                            Pesanan Selesai

                        </div>

                        <div class="timeline-desc">

                            <?php if ($tahapSekarang >= 4): ?>

                                Pesanan telah selesai. Terima kasih telah berbelanja di SIPESTA.

                            <?php else: ?>

                                Pesanan akan selesai setelah diterima.

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


            </div>

        <?php endif; ?>

    </div>

</div>


<div class="row g-4">


<!-- =========================================================
     DATA PEMBELI
     ========================================================= -->

<div class="col-lg-5">

    <div class="card card-custom h-100">

        <div class="card-header bg-white border-0 p-4">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-person me-2"></i>

                Informasi Pembeli

            </h5>

        </div>


        <div class="card-body px-4">


            <div class="mb-3">

                <div class="info-label">
                    Nama Penerima
                </div>

                <div class="info-value">

                    <?= e($pesanan['nama_penerima']) ?>

                </div>

            </div>


            <div class="mb-3">

                <div class="info-label">
                    No. HP
                </div>

                <div class="info-value">

                    <?= e($pesanan['no_hp']) ?>

                </div>

            </div>


            <div class="mb-3">

                <div class="info-label">
                    Metode Pembayaran
                </div>

                <div class="info-value">

                    <?= e($pesanan['metode_pembayaran']) ?>

                </div>

            </div>


            <div class="mb-3">

                <div class="info-label">
                    Alamat Pengiriman
                </div>

                <div class="address-box mt-2">

                    <?= nl2br(
                        e($pesanan['alamat_pengiriman'])
                    ) ?>

                </div>

            </div>


            <?php if (!empty($pesanan['catatan'])): ?>

                <div>

                    <div class="info-label">
                        Catatan
                    </div>

                    <div class="address-box mt-2">

                        <?= nl2br(
                            e($pesanan['catatan'])
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


        </div>

    </div>

</div>


<!-- =========================================================
     PRODUK
     ========================================================= -->

<div class="col-lg-7">

    <div class="card card-custom">

        <div class="card-header bg-white border-0 p-4">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-cart-check me-2"></i>

                Produk Pesanan

            </h5>

        </div>


        <div class="card-body px-4">

            <?php if ($detailPesanan->num_rows > 0): ?>


                <?php while (
                    $item = $detailPesanan->fetch_assoc()
                ): ?>

                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | FOTO PRODUK
                    |--------------------------------------------------------------------------
                    */

                    $foto = trim(
                        $item['foto'] ?? ''
                    );

                    $foto = str_replace(
                        '\\',
                        '/',
                        $foto
                    );

                    $namaFileFoto = basename($foto);

                    $pathFoto = __DIR__
                        . "/../uploads/produk/"
                        . $namaFileFoto;

                    $urlFoto = "../uploads/produk/"
                        . rawurlencode($namaFileFoto);


                    /*
                    |--------------------------------------------------------------------------
                    | CEK ULASAN
                    |--------------------------------------------------------------------------
                    */

                    $sudahUlasan = false;

                    if ($statusPesanan === 'selesai') {

                        $stmtUlasan = $conn->prepare("
                            SELECT id
                            FROM ulasan
                            WHERE pesanan_id = ?
                              AND produk_id = ?
                              AND pembeli_id = ?
                            LIMIT 1
                        ");

                        $stmtUlasan->bind_param(
                            "iii",
                            $pesanan_id,
                            $item['produk_id'],
                            $pembeli_id
                        );

                        $stmtUlasan->execute();

                        $resultUlasan =
                            $stmtUlasan->get_result();

                        $sudahUlasan =
                            (bool)$resultUlasan->fetch_assoc();

                        $stmtUlasan->close();
                    }

                    ?>


                    <div class="product-row">

                        <div class="row align-items-center g-3">


                            <!-- FOTO -->

                            <div class="col-auto">

                                <?php if (
                                    $namaFileFoto !== '' &&
                                    file_exists($pathFoto)
                                ): ?>

                                    <img
                                        src="<?= e($urlFoto) ?>"
                                        class="product-image"
                                        alt="<?= e($item['nama_produk']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="product-placeholder">

                                        <i class="bi bi-image"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- NAMA -->

                            <div class="col">

                                <div class="fw-bold">

                                    <?= e(
                                        $item['nama_produk']
                                    ) ?>

                                </div>


                                <div class="text-muted small">

                                    Penjual:

                                    <?= e(
                                        $item['nama_usaha']
                                    ) ?>

                                </div>


                                <div class="text-muted small mt-1">

                                    <?= e($item['jumlah']) ?>

                                    <?= e($item['satuan']) ?>

                                    ×

                                    <?= rupiah(
                                        $item['harga']
                                    ) ?>

                                </div>


                                <!-- ULASAN -->

                                <?php if (
                                    $statusPesanan === 'selesai'
                                ): ?>

                                    <div class="review-button">

                                        <?php if ($sudahUlasan): ?>

                                            <a
                                                href="ulasan.php?pesanan_id=<?= $pesanan_id ?>&produk_id=<?= (int)$item['produk_id'] ?>"
                                                class="btn btn-sm btn-outline-warning"
                                            >

                                                <i class="bi bi-pencil-square me-1"></i>

                                                Edit Ulasan

                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="ulasan.php?pesanan_id=<?= $pesanan_id ?>&produk_id=<?= (int)$item['produk_id'] ?>"
                                                class="btn btn-sm btn-warning"
                                            >

                                                <i class="bi bi-star-fill me-1"></i>

                                                Beri Ulasan

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>


                            </div>


                            <!-- SUBTOTAL -->

                            <div class="col-auto">

                                <strong>

                                    <?= rupiah(
                                        $item['subtotal']
                                    ) ?>

                                </strong>

                            </div>


                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>

                <div class="alert alert-warning">

                    Detail produk tidak ditemukan.

                </div>

            <?php endif; ?>


            <!-- TOTAL -->

            <div class="total-box mt-3">

                <div class="d-flex
                            justify-content-between
                            align-items-center">

                    <span class="fw-semibold">

                        Total Pesanan

                    </span>

                    <span class="fs-4
                                 fw-bold
                                 text-success">

                        <?= rupiah(
                            $pesanan['total_harga']
                        ) ?>

                    </span>

                </div>

            </div>


        </div>

    </div>

</div>


</div>


<!-- =========================================================
     ACTION
     ========================================================= -->

<div class="mt-4 d-flex
            flex-column
            flex-md-row
            gap-2">


    <!-- KEMBALI -->

    <a
        href="riwayat_pesanan.php"
        class="btn btn-outline-success"
    >

        <i class="bi bi-arrow-left me-1"></i>

        Kembali ke Riwayat

    </a>


    <!-- BELANJA LAGI -->

    <a
        href="index.php"
        class="btn btn-success"
    >

        <i class="bi bi-shop me-1"></i>

        Belanja Lagi

    </a>


    <!-- BATALKAN PESANAN -->

    <?php if ($statusPesanan === 'menunggu'): ?>

        <form
            action="batalkan_pesanan.php"
            method="POST"
            class="d-inline"
            onsubmit="return confirm(
                'Apakah Anda yakin ingin membatalkan pesanan ini? Stok produk akan dikembalikan.'
            );"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$pesanan['id'] ?>"
            >

            <button
                type="submit"
                class="btn btn-outline-danger"
            >

                <i class="bi bi-x-circle me-1"></i> b

                Batalkan Pesanan

            </button>

        </form>

    <?php endif; ?>


</div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>