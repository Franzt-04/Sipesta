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
| AMBIL ID PESANAN
|--------------------------------------------------------------------------
*/

$pesanan_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($pesanan_id <= 0) {
    header("Location: pesanan.php");
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

$stmtPenjual->bind_param(
    "i",
    $user_id
);

$stmtPenjual->execute();

$penjual = $stmtPenjual
    ->get_result()
    ->fetch_assoc();

if (!$penjual) {
    die("Data penjual tidak ditemukan.");
}

$penjual_id = (int) $penjual['id'];


/*
|--------------------------------------------------------------------------
| AMBIL DATA PESANAN
|--------------------------------------------------------------------------
|
| Pesanan hanya boleh dilihat jika memiliki produk
| milik penjual yang sedang login.
|
*/

$stmtPesanan = $conn->prepare("
    SELECT DISTINCT

        p.id,
        p.kode_pesanan,
        p.pembeli_id,
        p.nama_penerima,
        p.no_hp,
        p.tanggal_pesanan,
        p.alamat_pengiriman,
        p.catatan,
        p.metode_pembayaran,
        p.status

    FROM pesanan p

    INNER JOIN detail_pesanan dp
        ON p.id = dp.pesanan_id

    INNER JOIN produk pr
        ON dp.produk_id = pr.id

    WHERE p.id = ?
      AND pr.penjual_id = ?

    LIMIT 1
");

$stmtPesanan->bind_param(
    "ii",
    $pesanan_id,
    $penjual_id
);

$stmtPesanan->execute();

$pesanan = $stmtPesanan
    ->get_result()
    ->fetch_assoc();

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau tidak memiliki produk Anda.");
}


/*
|--------------------------------------------------------------------------
| AMBIL PRODUK MILIK PENJUAL
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
        pr.foto

    FROM detail_pesanan dp

    INNER JOIN produk pr
        ON dp.produk_id = pr.id

    WHERE dp.pesanan_id = ?
      AND pr.penjual_id = ?

    ORDER BY dp.id ASC
");

$stmtDetail->bind_param(
    "ii",
    $pesanan_id,
    $penjual_id
);

$stmtDetail->execute();

$detailPesanan = $stmtDetail->get_result();


/*
|--------------------------------------------------------------------------
| HITUNG TOTAL PRODUK PENJUAL
|--------------------------------------------------------------------------
*/

$totalPenjual = 0;
$jumlahItem = 0;

$detailData = [];

while ($item = $detailPesanan->fetch_assoc()) {

    $totalPenjual += (float) $item['subtotal'];

    $jumlahItem += (float) $item['jumlah'];

    $detailData[] = $item;
}


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

function badgeStatusPenjualDetail($status)
{
    switch (strtolower(trim($status))) {

        case 'menunggu':

            return '
                <span class="badge bg-warning text-dark px-3 py-2">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Menunggu
                </span>
            ';

        case 'diproses':

            return '
                <span class="badge bg-primary px-3 py-2">
                    <i class="bi bi-gear me-1"></i>
                    Diproses
                </span>
            ';

        case 'dikirim':

            return '
                <span class="badge bg-info text-dark px-3 py-2">
                    <i class="bi bi-truck me-1"></i>
                    Dikirim
                </span>
            ';

        case 'selesai':

            return '
                <span class="badge bg-success px-3 py-2">
                    <i class="bi bi-check-circle me-1"></i>
                    Selesai
                </span>
            ';

        case 'dibatalkan':

            return '
                <span class="badge bg-danger px-3 py-2">
                    <i class="bi bi-x-circle me-1"></i>
                    Dibatalkan
                </span>
            ';

        default:

            return '
                <span class="badge bg-secondary px-3 py-2">
                    Tidak diketahui
                </span>
            ';
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
            font-size: 21px;
            font-weight: 700;
        }

        .info-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
        }

        .address-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
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

        .total-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }

        .action-box {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
            padding: 20px;
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
            href="pesanan.php"
            class="btn btn-light btn-sm"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Daftar Pesanan

        </a>

    </div>

</nav>


<div class="container py-4">


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


<!-- HEADER PESANAN -->

<div class="card card-custom mb-4">

    <div class="card-body p-4">

        <div class="row align-items-center">

            <div class="col-md-8">

                <div class="info-label">
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


            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <div class="info-label mb-2">
                    Status Pesanan
                </div>

                <?= badgeStatusPenjualDetail(
                    $pesanan['status']
                ) ?>

            </div>

        </div>

    </div>

</div>


<div class="row g-4">


    <!-- INFORMASI PEMBELI -->

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
                            $pesanan['no_hp']
                        ) ?>

                    </div>

                </div>


                <div class="mb-3">

                    <div class="info-label">
                        Metode Pembayaran
                    </div>

                    <div class="info-value">

                        <?= e(
                            $pesanan['metode_pembayaran']
                        ) ?>

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
                            Catatan Pembeli
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


    <!-- PRODUK -->

    <div class="col-lg-7">

        <div class="card card-custom">

            <div class="card-header bg-white border-0 p-4">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-box-seam me-2"></i>

                    Produk Anda

                </h5>

            </div>


            <div class="card-body px-4">


                <?php if (count($detailData) > 0): ?>


                    <?php foreach ($detailData as $item): ?>

                        <div class="product-row">

                            <div class="row
                                        align-items-center
                                        g-3">


                                <!-- FOTO -->

                                <div class="col-auto">

                                    <?php if (
                                        !empty($item['foto']) &&
                                        file_exists(
                                            "../uploads/produk/" .
                                            $item['foto']
                                        )
                                    ): ?>

                                        <img
                                            src="../uploads/produk/<?= e($item['foto']) ?>"
                                            class="product-image"
                                            alt="<?= e($item['nama_produk']) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="product-placeholder">

                                            <i class="bi bi-image"></i>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- INFO -->

                                <div class="col">

                                    <div class="fw-bold">

                                        <?= e(
                                            $item['nama_produk']
                                        ) ?>

                                    </div>

                                    <div class="text-muted small mt-1">

                                        <?= e(
                                            $item['jumlah']
                                        ) ?>

                                        <?= e(
                                            $item['satuan']
                                        ) ?>

                                        ×

                                        <?= rupiah(
                                            $item['harga']
                                        ) ?>

                                    </div>

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

                    <?php endforeach; ?>


                <?php else: ?>

                    <div class="alert alert-warning">

                        Produk Anda tidak ditemukan.

                    </div>

                <?php endif; ?>


                <!-- TOTAL -->

                <div class="total-box mt-3">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <span class="fw-semibold">

                            Total Produk Anda

                        </span>

                        <span class="fs-4
                                     fw-bold
                                     text-success">

                            <?= rupiah(
                                $totalPenjual
                            ) ?>

                        </span>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


<!-- AKSI -->

<div class="action-box mt-4">

    <div class="fw-bold mb-3">

        <i class="bi bi-gear me-2"></i>

        Tindakan Pesanan

    </div>


    <?php

    $statusSekarang = strtolower(
        trim($pesanan['status'] ?? '')
    );

    ?>


    <?php if ($statusSekarang === 'menunggu'): ?>

        <div class="alert alert-warning mb-3">

            <i class="bi bi-hourglass-split me-2"></i>

            Pesanan menunggu untuk diproses.

        </div>

        <form
            action="proses_pesanan.php"
            method="POST"
            onsubmit="return confirm(
                'Apakah Anda yakin ingin memproses pesanan ini?'
            );"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$pesanan['id'] ?>"
            >

            <input
                type="hidden"
                name="aksi"
                value="proses"
            >

            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="bi bi-gear me-1"></i>

                Proses Pesanan

            </button>

        </form>


    <?php elseif ($statusSekarang === 'diproses'): ?>

        <div class="alert alert-primary mb-3">

            <i class="bi bi-box-seam me-2"></i>

            Pesanan sedang diproses.

        </div>

        <form
            action="proses_pesanan.php"
            method="POST"
            onsubmit="return confirm(
                'Apakah pesanan ini sudah dikirim?'
            );"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$pesanan['id'] ?>"
            >

            <input
                type="hidden"
                name="aksi"
                value="kirim"
            >

            <button
                type="submit"
                class="btn btn-info"
            >

                <i class="bi bi-truck me-1"></i>

                Tandai Dikirim

            </button>

        </form>


    <?php elseif ($statusSekarang === 'dikirim'): ?>

        <div class="alert alert-info mb-3">

            <i class="bi bi-truck me-2"></i>

            Pesanan sedang dalam proses pengiriman.

        </div>

        <form
            action="proses_pesanan.php"
            method="POST"
            onsubmit="return confirm(
                'Apakah pesanan ini sudah selesai?'
            );"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$pesanan['id'] ?>"
            >

            <input
                type="hidden"
                name="aksi"
                value="selesai"
            >

            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-check-circle me-1"></i>

                Tandai Selesai

            </button>

        </form>


    <?php elseif ($statusSekarang === 'selesai'): ?>

        <div class="alert alert-success mb-0">

            <i class="bi bi-check-circle me-2"></i>

            Pesanan telah selesai.

        </div>


    <?php elseif ($statusSekarang === 'dibatalkan'): ?>

        <div class="alert alert-danger mb-0">

            <i class="bi bi-x-circle me-2"></i>

            Pesanan telah dibatalkan oleh pembeli.

        </div>


    <?php else: ?>

        <div class="alert alert-secondary mb-0">

            Status pesanan tidak dikenali.

        </div>

    <?php endif; ?>

</div>


<div class="mt-4">

    <a
        href="pesanan.php"
        class="btn btn-outline-success"
    >

        <i class="bi bi-arrow-left me-1"></i>

        Kembali ke Daftar Pesanan

    </a>

</div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>