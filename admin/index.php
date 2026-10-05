<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");


/* =========================================================
   STATISTIK UTAMA
========================================================= */

$totalPenjual = 0;
$totalPembeli = 0;
$totalProduk = 0;
$totalPesanan = 0;

$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM penjual
");

if ($q) {
    $totalPenjual = (int)$q->fetch_assoc()['total'];
}


$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM pembeli
");

if ($q) {
    $totalPembeli = (int)$q->fetch_assoc()['total'];
}


$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM produk
");

if ($q) {
    $totalProduk = (int)$q->fetch_assoc()['total'];
}


$q = $conn->query("
    SELECT COUNT(*) AS total
    FROM pesanan
");

if ($q) {
    $totalPesanan = (int)$q->fetch_assoc()['total'];
}


/* =========================================================
   STATUS PESANAN
========================================================= */

$menunggu = 0;
$diproses = 0;
$dikirim = 0;
$selesai = 0;
$dibatalkan = 0;

$q = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM pesanan
    GROUP BY status
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        switch ($row['status']) {

            case 'menunggu':
                $menunggu = (int)$row['total'];
                break;

            case 'diproses':
                $diproses = (int)$row['total'];
                break;

            case 'dikirim':
                $dikirim = (int)$row['total'];
                break;

            case 'selesai':
                $selesai = (int)$row['total'];
                break;

            case 'dibatalkan':
                $dibatalkan = (int)$row['total'];
                break;
        }
    }
}


/* =========================================================
   TOTAL OMZET
========================================================= */

$totalOmzet = 0;

$q = $conn->query("
    SELECT
        COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE status = 'selesai'
");

if ($q) {
    $totalOmzet =
        (float)$q->fetch_assoc()['total'];
}


/* =========================================================
   PRODUK TERSEDIA / HABIS
========================================================= */

$produkTersedia = 0;
$produkHabis = 0;
$produkNonaktif = 0;

$q = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM produk
    GROUP BY status
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        if ($row['status'] === 'tersedia') {

            $produkTersedia =
                (int)$row['total'];

        }

        if ($row['status'] === 'habis') {

            $produkHabis =
                (int)$row['total'];

        }

        if ($row['status'] === 'nonaktif') {

            $produkNonaktif =
                (int)$row['total'];

        }

    }
}


/* =========================================================
   AKUN AKTIF / NONAKTIF
========================================================= */

$penjualAktif = 0;
$penjualNonaktif = 0;
$pembeliAktif = 0;
$pembeliNonaktif = 0;

$q = $conn->query("
    SELECT
        role,
        status,
        COUNT(*) AS total
    FROM users
    WHERE role IN ('penjual', 'pembeli')
    GROUP BY role, status
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        if ($row['role'] === 'penjual') {

            if ($row['status'] === 'aktif') {

                $penjualAktif =
                    (int)$row['total'];

            }

            if ($row['status'] === 'nonaktif') {

                $penjualNonaktif =
                    (int)$row['total'];

            }

        }


        if ($row['role'] === 'pembeli') {

            if ($row['status'] === 'aktif') {

                $pembeliAktif =
                    (int)$row['total'];

            }

            if ($row['status'] === 'nonaktif') {

                $pembeliNonaktif =
                    (int)$row['total'];

            }

        }

    }
}


/* =========================================================
   PRODUK TERLARIS
========================================================= */

$produkTerlaris = [];

$q = $conn->query("
    SELECT

        p.nama_produk,

        p.satuan,

        SUM(dp.jumlah)
            AS jumlah_terjual,

        SUM(dp.subtotal)
            AS total_penjualan

    FROM detail_pesanan dp

    INNER JOIN pesanan ps
        ON ps.id = dp.pesanan_id

    INNER JOIN produk p
        ON p.id = dp.produk_id

    WHERE ps.status = 'selesai'

    GROUP BY dp.produk_id

    ORDER BY jumlah_terjual DESC

    LIMIT 5
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        $produkTerlaris[] = $row;

    }
}


/* =========================================================
   PESANAN TERBARU
========================================================= */

$pesananTerbaru = [];

$q = $conn->query("
    SELECT

        ps.id,

        ps.kode_pesanan,

        ps.tanggal_pesanan,

        ps.total_harga,

        ps.status,

        p.nama AS nama_pembeli

    FROM pesanan ps

    INNER JOIN pembeli p
        ON p.id = ps.pembeli_id

    ORDER BY ps.id DESC

    LIMIT 8
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        $pesananTerbaru[] = $row;

    }
}


/* =========================================================
   DATA GRAFIK 6 BULAN TERAKHIR
========================================================= */

$grafikLabel = [];
$grafikJumlah = [];
$grafikOmzet = [];

$q = $conn->query("
    SELECT

        DATE_FORMAT(
            tanggal_pesanan,
            '%Y-%m'
        ) AS bulan,

        COUNT(*) AS jumlah,

        COALESCE(
            SUM(total_harga),
            0
        ) AS omzet

    FROM pesanan

    WHERE status = 'selesai'

      AND tanggal_pesanan >=
          DATE_SUB(
              CURDATE(),
              INTERVAL 5 MONTH
          )

    GROUP BY
        DATE_FORMAT(
            tanggal_pesanan,
            '%Y-%m'
        )

    ORDER BY bulan ASC
");

if ($q) {

    while ($row = $q->fetch_assoc()) {

        $grafikLabel[] =
            $row['bulan'];

        $grafikJumlah[] =
            (int)$row['jumlah'];

        $grafikOmzet[] =
            (float)$row['omzet'];

    }
}


/* =========================================================
   PENGATURAN ONGKIR
========================================================= */

$pengaturanOngkir = null;

$q = $conn->query("
    SELECT
        id,
        nama_pengaturan,
        tarif_dasar,
        tarif_per_km,
        minimal_gratis,
        maksimal_jarak_km,
        status,
        updated_at

    FROM pengaturan_ongkir

    ORDER BY id ASC

    LIMIT 1
");

if ($q) {

    $pengaturanOngkir =
        $q->fetch_assoc();

}


/* =========================================================
   DATA ONGKIR
========================================================= */

$ongkirAktif = false;

$tarifDasar =
    0;

$tarifPerKm =
    0;

$minimalGratis =
    0;

$maksimalJarak =
    0;

$namaPengaturanOngkir =
    'Belum Diatur';


if ($pengaturanOngkir) {

    $ongkirAktif =
        $pengaturanOngkir['status'] === 'aktif';

    $tarifDasar =
        (float)$pengaturanOngkir['tarif_dasar'];

    $tarifPerKm =
        (float)$pengaturanOngkir['tarif_per_km'];

    $minimalGratis =
        (float)$pengaturanOngkir['minimal_gratis'];

    $maksimalJarak =
        (float)$pengaturanOngkir['maksimal_jarak_km'];

    $namaPengaturanOngkir =
        $pengaturanOngkir['nama_pengaturan'];

}


/* =========================================================
   STATUS LOKASI PENJUAL
========================================================= */

$totalPenjualDenganLokasi = 0;
$totalPenjualTanpaLokasi = 0;

$q = $conn->query("
    SELECT

        SUM(
            CASE
                WHEN latitude IS NOT NULL
                 AND longitude IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS dengan_lokasi,

        SUM(
            CASE
                WHEN latitude IS NULL
                  OR longitude IS NULL
                THEN 1
                ELSE 0
            END
        ) AS tanpa_lokasi

    FROM penjual
");

if ($q) {

    $lokasiPenjual =
        $q->fetch_assoc();

    $totalPenjualDenganLokasi =
        (int)(
            $lokasiPenjual['dengan_lokasi']
            ?? 0
        );

    $totalPenjualTanpaLokasi =
        (int)(
            $lokasiPenjual['tanpa_lokasi']
            ?? 0
        );

}


/* =========================================================
   NOTIFIKASI
========================================================= */

$notifikasiPesanan =
    $menunggu;

$notifikasiProduk =
    $produkHabis;

$notifikasiPenjual =
    $penjualNonaktif;

$notifikasiPembeli =
    $pembeliNonaktif;

$totalNotifikasi =

    $notifikasiPesanan +

    $notifikasiProduk +

    $notifikasiPenjual +

    $notifikasiPembeli;


/* =========================================================
   BADGE STATUS
========================================================= */

function badgeStatusAdmin($status)
{

    $class =
        "bg-secondary";

    switch ($status) {

        case "menunggu":

            $class =
                "bg-warning text-dark";

            break;

        case "diproses":

            $class =
                "bg-info text-dark";

            break;

        case "dikirim":

            $class =
                "bg-primary";

            break;

        case "selesai":

            $class =
                "bg-success";

            break;

        case "dibatalkan":

            $class =
                "bg-danger";

            break;

    }

    return

        '<span class="badge ' .
        $class .
        '">' .

        htmlspecialchars(
            ucfirst($status)
        ) .

        '</span>';

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

    <title>
        Dashboard Admin - SIPESTA
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>


    <style>

        body {

            background:
                #f5f7fb;

        }


        .sidebar {

            min-height:
                100vh;

            background:
                #212529;

        }


        .sidebar a {

            color:
                #fff;

            text-decoration:
                none;

            display:
                block;

            padding:
                12px 18px;

            transition:
                .2s;

        }


        .sidebar a:hover {

            background:
                rgba(255,255,255,.1);

        }


        .sidebar a.active {

            background:
                #198754;

        }


        .stat-card {

            border:
                none;

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .stat-number {

            font-size:
                28px;

            font-weight:
                700;

        }


        .dashboard-card {

            border:
                none;

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .notification-item {

            border-bottom:
                1px solid #eee;

            padding:
                12px 0;

        }


        .notification-item:last-child {

            border-bottom:
                none;

        }


        .menu-card {

            border:
                none;

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

            transition:
                .2s;

        }


        .menu-card:hover {

            transform:
                translateY(-3px);

        }


        .menu-icon {

            width:
                55px;

            height:
                55px;

            border-radius:
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                26px;

        }


        .ongkir-card {

            border:
                none;

            border-radius:
                15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .ongkir-value {

            font-size:
                18px;

            font-weight:
                700;

        }


        .location-progress {

            height:
                8px;

            border-radius:
                20px;

        }


        @media (max-width: 767px) {

            .sidebar {

                min-height:
                    auto;

            }

            .stat-number {

                font-size:
                    24px;

            }

        }

    </style>

</head>


<body>


<div class="container-fluid">

    <div class="row">


        <!-- =====================================================
             SIDEBAR
        ====================================================== -->

        <div
            class="col-md-2 p-0 sidebar"
        >


            <div
                class="p-4 text-white"
            >

                <h4 class="fw-bold">

                    <i
                        class="bi bi-shop"
                    ></i>

                    SIPESTA

                </h4>

                <small>
                    Panel Administrator
                </small>

            </div>


            <a
                href="index.php"
                class="active"
            >

                <i
                    class="bi bi-speedometer2 me-2"
                ></i>

                Dashboard

            </a>


            <a
                href="penjual.php"
            >

                <i
                    class="bi bi-person-badge me-2"
                ></i>

                Penjual

            </a>


            <a
                href="pembeli.php"
            >

                <i
                    class="bi bi-people me-2"
                ></i>

                Pembeli

            </a>


            <a
                href="produk.php"
            >

                <i
                    class="bi bi-box-seam me-2"
                ></i>

                Produk

            </a>


            <a
                href="kategori.php"
            >

                <i
                    class="bi bi-grid me-2"
                ></i>

                Kategori

            </a>


            <a
                href="pesanan.php"
            >

                <i
                    class="bi bi-cart-check me-2"
                ></i>

                Pesanan

            </a>


            <!-- MENU BARU -->

            <a
                href="pengaturan_ongkir.php"
            >

                <i
                    class="bi bi-truck me-2"
                ></i>

                Pengaturan Ongkir

            </a>


            <a
                href="lokasi_penjual.php"
            >

                <i
                    class="bi bi-geo-alt me-2"
                ></i>

                Lokasi Penjual

            </a>


            <a
                href="../logout.php"
                class="mt-3"
            >

                <i
                    class="bi bi-box-arrow-right me-2"
                ></i>

                Logout

            </a>


        </div>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div
            class="col-md-10 p-4"
        >


            <!-- HEADER -->

            <div
                class="d-flex
                       justify-content-between
                       align-items-center
                       mb-4"
            >

                <div>

                    <h2
                        class="fw-bold"
                    >

                        Dashboard Admin

                    </h2>

                    <p
                        class="text-muted mb-0"
                    >

                        Ringkasan aktivitas
                        SIPESTA

                    </p>

                </div>


                <div>

                    <span
                        class="badge
                               bg-dark
                               p-2"
                    >

                        <i
                            class="bi bi-shield-check"
                        ></i>

                        Admin

                    </span>

                </div>

            </div>


            <!-- =================================================
                 STATISTIK UTAMA
            ================================================== -->

            <div
                class="row g-3 mb-4"
            >


                <div
                    class="col-6 col-md-3"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small
                                class="text-muted"
                            >

                                Total Penjual

                            </small>


                            <div
                                class="stat-number"
                            >

                                <?= $totalPenjual ?>

                            </div>


                            <small
                                class="text-success"
                            >

                                <i
                                    class="bi bi-check-circle"
                                ></i>

                                <?= $penjualAktif ?>
                                aktif

                            </small>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small
                                class="text-muted"
                            >

                                Total Pembeli

                            </small>


                            <div
                                class="stat-number"
                            >

                                <?= $totalPembeli ?>

                            </div>


                            <small
                                class="text-success"
                            >

                                <i
                                    class="bi bi-check-circle"
                                ></i>

                                <?= $pembeliAktif ?>
                                aktif

                            </small>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small
                                class="text-muted"
                            >

                                Total Produk

                            </small>


                            <div
                                class="stat-number"
                            >

                                <?= $totalProduk ?>

                            </div>


                            <small
                                class="text-primary"
                            >

                                <i
                                    class="bi bi-box-seam"
                                ></i>

                                <?= $produkTersedia ?>
                                tersedia

                            </small>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small
                                class="text-muted"
                            >

                                Total Pesanan

                            </small>


                            <div
                                class="stat-number"
                            >

                                <?= $totalPesanan ?>

                            </div>


                            <small
                                class="text-warning"
                            >

                                <i
                                    class="bi bi-clock"
                                ></i>

                                <?= $menunggu ?>
                                menunggu

                            </small>

                        </div>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 OMZET + STATUS PESANAN
            ================================================== -->

            <div
                class="row g-3 mb-4"
            >


                <div
                    class="col-12 col-lg-4"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small
                                class="text-muted"
                            >

                                Total Omzet

                            </small>


                            <h3
                                class="fw-bold
                                       text-success"
                            >

                                <?= rupiah($totalOmzet) ?>

                            </h3>


                            <small
                                class="text-muted"
                            >

                                Dari transaksi selesai

                            </small>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3 col-lg"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small>
                                Menunggu
                            </small>

                            <h3
                                class="text-warning"
                            >

                                <?= $menunggu ?>

                            </h3>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3 col-lg"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small>
                                Diproses
                            </small>

                            <h3
                                class="text-info"
                            >

                                <?= $diproses ?>

                            </h3>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3 col-lg"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small>
                                Dikirim
                            </small>

                            <h3
                                class="text-primary"
                            >

                                <?= $dikirim ?>

                            </h3>

                        </div>

                    </div>

                </div>


                <div
                    class="col-6 col-md-3 col-lg"
                >

                    <div
                        class="card stat-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <small>
                                Selesai
                            </small>

                            <h3
                                class="text-success"
                            >

                                <?= $selesai ?>

                            </h3>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 PENGATURAN ONGKIR
            ================================================== -->

            <div
                class="card ongkir-card mb-4"
            >

                <div
                    class="card-body p-4"
                >


                    <div
                        class="d-flex
                               flex-column
                               flex-md-row
                               justify-content-between
                               align-items-md-center
                               gap-3
                               mb-4"
                    >

                        <div>

                            <h5
                                class="fw-bold mb-1"
                            >

                                <i
                                    class="bi bi-truck
                                           text-success"
                                ></i>

                                Pengaturan Ongkos Kirim

                            </h5>

                            <small
                                class="text-muted"
                            >

                                Konfigurasi tarif pengiriman
                                SIPESTA berdasarkan jarak.

                            </small>

                        </div>


                        <a
                            href="pengaturan_ongkir.php"
                            class="btn btn-success"
                        >

                            <i
                                class="bi bi-gear"
                            ></i>

                            Kelola Ongkir

                        </a>

                    </div>


                    <?php if (!$pengaturanOngkir): ?>


                        <div
                            class="alert
                                   alert-warning
                                   mb-0"
                        >

                            <i
                                class="bi bi-exclamation-triangle-fill"
                            ></i>

                            <strong>
                                Pengaturan ongkir belum tersedia.
                            </strong>

                            <div
                                class="mt-1"
                            >

                                Silakan buat pengaturan ongkir
                                sebelum pembeli melakukan checkout.

                            </div>

                        </div>


                    <?php else: ?>


                        <div
                            class="row g-3"
                        >


                            <!-- STATUS -->

                            <div
                                class="col-12 col-sm-6 col-xl-2"
                            >

                                <div
                                    class="bg-light
                                           rounded-4
                                           p-3
                                           h-100"
                                >

                                    <small
                                        class="text-muted"
                                    >

                                        Status

                                    </small>


                                    <div
                                        class="mt-2"
                                    >

                                        <?php if ($ongkirAktif): ?>

                                            <span
                                                class="badge
                                                       bg-success"
                                            >

                                                <i
                                                    class="bi bi-check-circle"
                                                ></i>

                                                Aktif

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge
                                                       bg-secondary"
                                            >

                                                <i
                                                    class="bi bi-pause-circle"
                                                ></i>

                                                Nonaktif

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>


                            <!-- TARIF DASAR -->

                            <div
                                class="col-12 col-sm-6 col-xl-2"
                            >

                                <div
                                    class="bg-light
                                           rounded-4
                                           p-3
                                           h-100"
                                >

                                    <small
                                        class="text-muted"
                                    >

                                        Tarif Dasar

                                    </small>


                                    <div
                                        class="ongkir-value
                                               text-success
                                               mt-2"
                                    >

                                        <?= rupiah($tarifDasar) ?>

                                    </div>

                                </div>

                            </div>


                            <!-- TARIF KM -->

                            <div
                                class="col-12 col-sm-6 col-xl-2"
                            >

                                <div
                                    class="bg-light
                                           rounded-4
                                           p-3
                                           h-100"
                                >

                                    <small
                                        class="text-muted"
                                    >

                                        Tarif / KM

                                    </small>


                                    <div
                                        class="ongkir-value
                                               text-primary
                                               mt-2"
                                    >

                                        <?= rupiah($tarifPerKm) ?>

                                    </div>

                                </div>

                            </div>


                            <!-- GRATIS -->

                            <div
                                class="col-12 col-sm-6 col-xl-3"
                            >

                                <div
                                    class="bg-light
                                           rounded-4
                                           p-3
                                           h-100"
                                >

                                    <small
                                        class="text-muted"
                                    >

                                        Gratis Ongkir

                                    </small>


                                    <div
                                        class="ongkir-value
                                               text-warning
                                               mt-2"
                                    >

                                        <?php if ($minimalGratis > 0): ?>

                                            <?= rupiah($minimalGratis) ?>

                                        <?php else: ?>

                                            Tidak Aktif

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>


                            <!-- MAKSIMAL JARAK -->

                            <div
                                class="col-12 col-sm-6 col-xl-3"
                            >

                                <div
                                    class="bg-light
                                           rounded-4
                                           p-3
                                           h-100"
                                >

                                    <small
                                        class="text-muted"
                                    >

                                        Maksimal Jarak

                                    </small>


                                    <div
                                        class="ongkir-value
                                               text-danger
                                               mt-2"
                                    >

                                        <?php if ($maksimalJarak > 0): ?>

                                            <?= e($maksimalJarak) ?>
                                            KM

                                        <?php else: ?>

                                            Tidak Terbatas

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div
                            class="mt-3
                                   text-muted
                                   small"
                        >

                            <i
                                class="bi bi-info-circle"
                            ></i>

                            Pengaturan:

                            <strong>
                                <?= e($namaPengaturanOngkir) ?>
                            </strong>

                        </div>


                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 STATUS LOKASI PENJUAL
            ================================================== -->

            <div
                class="card dashboard-card mb-4"
            >

                <div
                    class="card-body p-4"
                >

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center
                               mb-3"
                    >

                        <div>

                            <h5
                                class="fw-bold mb-1"
                            >

                                <i
                                    class="bi bi-geo-alt
                                           text-danger"
                                ></i>

                                Status Lokasi Penjual

                            </h5>

                            <small
                                class="text-muted"
                            >

                                Lokasi diperlukan untuk
                                perhitungan ongkos kirim.

                            </small>

                        </div>


                        <a
                            href="lokasi_penjual.php"
                            class="btn btn-sm
                                   btn-outline-success"
                        >

                            <i
                                class="bi bi-geo-alt"
                            ></i>

                            Kelola Lokasi

                        </a>

                    </div>


                    <?php

                    $persentaseLokasi =
                        $totalPenjual > 0

                        ? (
                            $totalPenjualDenganLokasi
                            /
                            $totalPenjual
                            *
                            100
                        )

                        : 0;

                    ?>


                    <div
                        class="row g-3"
                    >


                        <div
                            class="col-12 col-md-4"
                        >

                            <div
                                class="bg-success-subtle
                                       rounded-4
                                       p-3
                                       h-100"
                            >

                                <small
                                    class="text-muted"
                                >

                                    Lokasi Lengkap

                                </small>


                                <h3
                                    class="fw-bold
                                           text-success
                                           mb-0"
                                >

                                    <?= $totalPenjualDenganLokasi ?>

                                </h3>


                                <small>

                                    Penjual

                                </small>

                            </div>

                        </div>


                        <div
                            class="col-12 col-md-4"
                        >

                            <div
                                class="bg-danger-subtle
                                       rounded-4
                                       p-3
                                       h-100"
                            >

                                <small
                                    class="text-muted"
                                >

                                    Belum Ada Lokasi

                                </small>


                                <h3
                                    class="fw-bold
                                           text-danger
                                           mb-0"
                                >

                                    <?= $totalPenjualTanpaLokasi ?>

                                </h3>


                                <small>

                                    Penjual

                                </small>

                            </div>

                        </div>


                        <div
                            class="col-12 col-md-4"
                        >

                            <div
                                class="bg-light
                                       rounded-4
                                       p-3
                                       h-100"
                            >

                                <small
                                    class="text-muted"
                                >

                                    Kelengkapan Lokasi

                                </small>


                                <h3
                                    class="fw-bold
                                           text-primary
                                           mb-2"
                                >

                                    <?= number_format(
                                        $persentaseLokasi,
                                        0
                                    ) ?>%

                                </h3>


                                <div
                                    class="progress
                                           location-progress"
                                >

                                    <div
                                        class="progress-bar
                                               bg-success"
                                        style="width:
                                        <?= $persentaseLokasi ?>%"
                                    ></div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <?php if ($totalPenjualTanpaLokasi > 0): ?>

                        <div
                            class="alert
                                   alert-warning
                                   mt-3
                                   mb-0"
                        >

                            <i
                                class="bi bi-exclamation-triangle"
                            ></i>

                            Ada

                            <strong>
                                <?= $totalPenjualTanpaLokasi ?>
                            </strong>

                            penjual yang belum mempunyai
                            koordinat lokasi usaha.

                            <a
                                href="lokasi_penjual.php"
                                class="alert-link"
                            >

                                Lengkapi sekarang

                            </a>

                        </div>

                    <?php else: ?>

                        <div
                            class="alert
                                   alert-success
                                   mt-3
                                   mb-0"
                        >

                            <i
                                class="bi bi-check-circle"
                            ></i>

                            Semua penjual sudah mempunyai
                            koordinat lokasi usaha.

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 GRAFIK
            ================================================== -->

            <div
                class="row g-4 mb-4"
            >


                <div
                    class="col-12 col-lg-8"
                >

                    <div
                        class="card dashboard-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <h5
                                class="fw-bold"
                            >

                                Grafik Transaksi

                            </h5>


                            <canvas
                                id="grafikTransaksi"
                                height="120"
                            ></canvas>

                        </div>

                    </div>

                </div>


                <!-- NOTIFIKASI -->

                <div
                    class="col-12 col-lg-4"
                >

                    <div
                        class="card dashboard-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <div
                                class="d-flex
                                       justify-content-between"
                            >

                                <h5
                                    class="fw-bold"
                                >

                                    Notifikasi

                                </h5>


                                <span
                                    class="badge
                                           bg-danger"
                                >

                                    <?= $totalNotifikasi ?>

                                </span>

                            </div>


                            <div
                                class="notification-item"
                            >

                                <i
                                    class="bi bi-cart text-warning"
                                ></i>

                                Pesanan menunggu

                                <span
                                    class="float-end
                                           badge
                                           bg-warning
                                           text-dark"
                                >

                                    <?= $notifikasiPesanan ?>

                                </span>

                            </div>


                            <div
                                class="notification-item"
                            >

                                <i
                                    class="bi bi-box text-danger"
                                ></i>

                                Produk habis

                                <span
                                    class="float-end
                                           badge
                                           bg-danger"
                                >

                                    <?= $notifikasiProduk ?>

                                </span>

                            </div>


                            <div
                                class="notification-item"
                            >

                                <i
                                    class="bi bi-person-x
                                           text-secondary"
                                ></i>

                                Penjual nonaktif

                                <span
                                    class="float-end
                                           badge
                                           bg-secondary"
                                >

                                    <?= $notifikasiPenjual ?>

                                </span>

                            </div>


                            <div
                                class="notification-item"
                            >

                                <i
                                    class="bi bi-person-x
                                           text-secondary"
                                ></i>

                                Pembeli nonaktif

                                <span
                                    class="float-end
                                           badge
                                           bg-secondary"
                                >

                                    <?= $notifikasiPembeli ?>

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 PRODUK TERLARIS
            ================================================== -->

            <div
                class="row g-4 mb-4"
            >


                <div
                    class="col-12 col-lg-6"
                >

                    <div
                        class="card dashboard-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <div
                                class="d-flex
                                       justify-content-between
                                       mb-3"
                            >

                                <h5
                                    class="fw-bold"
                                >

                                    Produk Terlaris

                                </h5>


                                <a
                                    href="produk.php"
                                    class="btn btn-sm
                                           btn-outline-primary"
                                >

                                    Semua Produk

                                </a>

                            </div>


                            <div
                                class="table-responsive"
                            >

                                <table
                                    class="table
                                           table-hover
                                           align-middle"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                Produk
                                            </th>

                                            <th>
                                                Terjual
                                            </th>

                                            <th>
                                                Penjualan
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                    <?php if (
                                        empty($produkTerlaris)
                                    ): ?>

                                        <tr>

                                            <td
                                                colspan="3"
                                                class="text-center
                                                       text-muted"
                                            >

                                                Belum ada
                                                transaksi selesai.

                                            </td>

                                        </tr>


                                    <?php else: ?>


                                        <?php foreach (
                                            $produkTerlaris
                                            as $produk
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <?= e(
                                                        $produk[
                                                            'nama_produk'
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= e(
                                                        $produk[
                                                            'jumlah_terjual'
                                                        ]
                                                    ) ?>

                                                    <?= e(
                                                        $produk[
                                                            'satuan'
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= rupiah(
                                                        $produk[
                                                            'total_penjualan'
                                                        ]
                                                    ) ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- KONDISI PRODUK -->

                <div
                    class="col-12 col-lg-6"
                >

                    <div
                        class="card dashboard-card h-100"
                    >

                        <div
                            class="card-body"
                        >

                            <h5
                                class="fw-bold mb-4"
                            >

                                Kondisi Produk

                            </h5>


                            <!-- TERSEDIA -->

                            <div
                                class="mb-3"
                            >

                                <div
                                    class="d-flex
                                           justify-content-between"
                                >

                                    <span>
                                        Tersedia
                                    </span>

                                    <strong>
                                        <?= $produkTersedia ?>
                                    </strong>

                                </div>


                                <div
                                    class="progress"
                                >

                                    <div
                                        class="progress-bar
                                               bg-success"
                                        style="width:
                                        <?= $totalProduk > 0
                                            ? (
                                                $produkTersedia
                                                /
                                                $totalProduk
                                                * 100
                                            )
                                            : 0 ?>%"
                                    ></div>

                                </div>

                            </div>


                            <!-- HABIS -->

                            <div
                                class="mb-3"
                            >

                                <div
                                    class="d-flex
                                           justify-content-between"
                                >

                                    <span>
                                        Habis
                                    </span>

                                    <strong>
                                        <?= $produkHabis ?>
                                    </strong>

                                </div>


                                <div
                                    class="progress"
                                >

                                    <div
                                        class="progress-bar
                                               bg-danger"
                                        style="width:
                                        <?= $totalProduk > 0
                                            ? (
                                                $produkHabis
                                                /
                                                $totalProduk
                                                * 100
                                            )
                                            : 0 ?>%"
                                    ></div>

                                </div>

                            </div>


                            <!-- NONAKTIF -->

                            <div>

                                <div
                                    class="d-flex
                                           justify-content-between"
                                >

                                    <span>
                                        Nonaktif
                                    </span>

                                    <strong>
                                        <?= $produkNonaktif ?>
                                    </strong>

                                </div>


                                <div
                                    class="progress"
                                >

                                    <div
                                        class="progress-bar
                                               bg-secondary"
                                        style="width:
                                        <?= $totalProduk > 0
                                            ? (
                                                $produkNonaktif
                                                /
                                                $totalProduk
                                                * 100
                                            )
                                            : 0 ?>%"
                                    ></div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 PESANAN TERBARU
            ================================================== -->

            <div
                class="card dashboard-card"
            >

                <div
                    class="card-body"
                >


                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center
                               mb-3"
                    >

                        <h5
                            class="fw-bold"
                        >

                            <i
                                class="bi bi-clock-history"
                            ></i>

                            Pesanan Terbaru

                        </h5>


                        <a
                            href="pesanan.php"
                            class="btn btn-sm
                                   btn-primary"
                        >

                            Lihat Semua

                        </a>

                    </div>


                    <div
                        class="table-responsive"
                    >

                        <table
                            class="table
                                   table-hover
                                   align-middle"
                        >

                            <thead>

                                <tr>

                                    <th>
                                        Kode
                                    </th>

                                    <th>
                                        Tanggal
                                    </th>

                                    <th>
                                        Pembeli
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (
                                empty($pesananTerbaru)
                            ): ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        class="text-center
                                               text-muted
                                               py-4"
                                    >

                                        Belum ada pesanan.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $pesananTerbaru
                                    as $pesanan
                                ): ?>


                                    <tr>

                                        <td>

                                            <strong>

                                                <?= e(
                                                    $pesanan[
                                                        'kode_pesanan'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= e(
                                                $pesanan[
                                                    'tanggal_pesanan'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                $pesanan[
                                                    'nama_pembeli'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= rupiah(
                                                $pesanan[
                                                    'total_harga'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= badgeStatusAdmin(
                                                $pesanan[
                                                    'status'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <a
                                                href="detail_pesanan.php?id=<?= (int)$pesanan['id'] ?>"
                                                class="btn btn-sm
                                                       btn-outline-primary"
                                            >

                                                <i
                                                    class="bi bi-eye"
                                                ></i>

                                                Detail

                                            </a>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


        </div>

    </div>

</div>


<script>

const labels =
    <?= json_encode(
        $grafikLabel
    ) ?>;


const jumlah =
    <?= json_encode(
        $grafikJumlah
    ) ?>;


const omzet =
    <?= json_encode(
        $grafikOmzet
    ) ?>;


new Chart(

    document.getElementById(
        'grafikTransaksi'
    ),

    {

        type:
            'line',

        data: {

            labels:
                labels,

            datasets: [

                {

                    label:
                        'Jumlah Transaksi',

                    data:
                        jumlah,

                    tension:
                        0.3

                },

                {

                    label:
                        'Omzet',

                    data:
                        omzet,

                    tension:
                        0.3,

                    yAxisID:
                        'y1'

                }

            ]

        },


        options: {

            responsive:
                true,

            interaction: {

                mode:
                    'index',

                intersect:
                    false

            },


            scales: {

                y: {

                    beginAtZero:
                        true

                },


                y1: {

                    beginAtZero:
                        true,

                    position:
                        'right',

                    grid: {

                        drawOnChartArea:
                            false

                    }

                }

            }

        }

    }

);

</script>


</body>

</html>