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
    | NOTIFIKASI
    |--------------------------------------------------------------------------
    */

    $stmtNotif = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ?
        AND dibaca = 0
    ");

    $stmtNotif->bind_param("i", $user_id);
    $stmtNotif->execute();

    $resultNotif = $stmtNotif->get_result();
    $jumlahNotif = (int) $resultNotif->fetch_assoc()['total'];

    $stmtNotif->close();


    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA PENJUAL
    |--------------------------------------------------------------------------
    */

    $stmtPenjual = $conn->prepare("
        SELECT
            id,
            nama_usaha,
            alamat,
            latitude,
            longitude
        FROM penjual
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmtPenjual->bind_param("i", $user_id);
    $stmtPenjual->execute();

    $penjual = $stmtPenjual
        ->get_result()
        ->fetch_assoc();

    $stmtPenjual->close();

    if (!$penjual) {
        die("Data penjual tidak ditemukan.");
    }

    $penjual_id = (int) $penjual['id'];


    /*
    |--------------------------------------------------------------------------
    | STATUS LOKASI USAHA
    |--------------------------------------------------------------------------
    */

    $lokasiSudahDiatur =
        $penjual['latitude'] !== null &&
        $penjual['latitude'] !== '' &&
        $penjual['longitude'] !== null &&
        $penjual['longitude'] !== '';


    /*
    |--------------------------------------------------------------------------
    | STATISTIK PRODUK
    |--------------------------------------------------------------------------
    */


    /* Total produk */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM produk
        WHERE penjual_id = ?
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $totalProduk = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Produk tersedia */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM produk
        WHERE penjual_id = ?
        AND status = 'tersedia'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $produkTersedia = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Produk stok habis */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM produk
        WHERE penjual_id = ?
        AND status = 'habis'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $produkHabis = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Total stok */

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(stok), 0) AS total
        FROM produk
        WHERE penjual_id = ?
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $totalStok = (float) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | STATISTIK PESANAN
    |--------------------------------------------------------------------------
    */


    /* Menunggu */

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'menunggu'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananMenunggu = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Diproses */

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'diproses'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananDiproses = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Dikirim */

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'dikirim'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananDikirim = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Selesai */

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'selesai'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananSelesai = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /* Dibatalkan */

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'dibatalkan'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananDibatalkan = (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | PENDAPATAN
    |--------------------------------------------------------------------------
    |
    | Pendapatan dihitung dari produk milik penjual
    | yang status pesanannya = selesai.
    |
    */

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(dp.subtotal), 0) AS total
        FROM detail_pesanan dp

        INNER JOIN pesanan p
            ON dp.pesanan_id = p.id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?
        AND p.status = 'selesai'
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $totalPendapatan = (float) $stmt
        ->get_result()
        ->fetch_assoc()['total'];

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | PESANAN TERBARU
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.kode_pesanan,
            p.nama_penerima,
            p.tanggal_pesanan,
            p.metode_pembayaran,
            p.status,
            SUM(dp.subtotal) AS total_produk

        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE pr.penjual_id = ?

        GROUP BY
            p.id,
            p.kode_pesanan,
            p.nama_penerima,
            p.tanggal_pesanan,
            p.metode_pembayaran,
            p.status

        ORDER BY p.tanggal_pesanan DESC

        LIMIT 5
    ");

    $stmt->bind_param("i", $penjual_id);
    $stmt->execute();

    $pesananTerbaru = $stmt->get_result();


    /*
    |--------------------------------------------------------------------------
    | BADGE STATUS
    |--------------------------------------------------------------------------
    */

    function badgeStatusDashboard($status)
    {
        $status = strtolower(trim($status));

        switch ($status) {

            case 'menunggu':
                return '<span class="badge text-bg-warning">Menunggu</span>';

            case 'diproses':
                return '<span class="badge text-bg-primary">Diproses</span>';

            case 'dikirim':
                return '<span class="badge text-bg-info">Dikirim</span>';

            case 'selesai':
                return '<span class="badge text-bg-success">Selesai</span>';

            case 'dibatalkan':
                return '<span class="badge text-bg-danger">Dibatalkan</span>';

            default:
                return '<span class="badge text-bg-secondary">'
                    . e($status)
                    . '</span>';
        }
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

        <title>Dashboard Penjual - SIPESTA</title>


        <!-- Bootstrap -->

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >


        <!-- Bootstrap Icons -->

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

            .card-stat {
                border: none;
                border-radius: 16px;
                transition: .2s;
            }

            .card-stat:hover {
                transform: translateY(-3px);
            }

            .icon-box {
                width: 52px;
                height: 52px;
                border-radius: 14px;

                display: flex;
                align-items: center;
                justify-content: center;

                font-size: 24px;
            }

            .dashboard-title {
                font-weight: 700;
            }

            .table-card {
                border: none;
                border-radius: 16px;
                overflow: hidden;
            }

            .menu-card {
                border: none;
                border-radius: 16px;
                transition: .2s;
            }

            .menu-card:hover {
                transform: translateY(-3px);
            }

            .menu-icon {
                font-size: 30px;
            }

            /*
            |--------------------------------------------------------------------------
            | KARTU LOKASI
            |--------------------------------------------------------------------------
            */

            .location-card {
                border: none;
                border-radius: 18px;
                overflow: hidden;
            }

            .location-icon {
                width: 58px;
                height: 58px;

                border-radius: 15px;

                display: flex;
                align-items: center;
                justify-content: center;

                font-size: 26px;
            }

            .coordinate-box {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 12px 15px;
            }

            .coordinate-value {
                font-weight: 700;
                word-break: break-word;
            }

            .notification-badge {
                font-size: 11px;
            }

            @media (max-width: 768px) {

                .navbar .container-fluid {
                    padding-left: 15px !important;
                    padding-right: 15px !important;
                }

            }

        </style>

    </head>


    <body>


    <!-- ================================================================== -->
    <!-- NAVBAR -->
    <!-- ================================================================== -->

    <nav class="navbar navbar-expand-lg bg-white shadow-sm">

        <div class="container-fluid px-4">


            <!-- LOGO -->

            <a
                class="navbar-brand text-success"
                href="index.php"
            >

                <i class="bi bi-shop"></i>

                SIPESTA

            </a>


            <!-- MENU KANAN -->

            <div class="d-flex align-items-center gap-2">


                <!-- NAMA TOKO -->

                <span class="text-muted d-none d-lg-block me-2">

                    <i class="bi bi-shop"></i>

                    <?= e($penjual['nama_usaha']) ?>

                </span>


                <!-- LOKASI USAHA -->

                <a
                    href="lokasi_usaha.php"
                    class="btn btn-outline-success btn-sm"
                >

                    <i class="bi bi-geo-alt"></i>

                    <span class="d-none d-md-inline">
                        Lokasi Usaha
                    </span>

                </a>


                <!-- NOTIFIKASI -->

                <a
                    href="notifikasi.php"
                    class="btn btn-outline-secondary btn-sm position-relative"
                    title="Notifikasi"
                >

                    <i class="bi bi-bell"></i>

                    <span class="d-none d-md-inline">
                        Notifikasi
                    </span>

                    <?php if ($jumlahNotif > 0): ?>

                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                        >
                            <?= $jumlahNotif ?>
                        </span>

                    <?php endif; ?>

                </a>


                <!-- LOGOUT -->

                <a
                    href="../logout.php"
                    class="btn btn-outline-danger btn-sm"
                >

                    <i class="bi bi-box-arrow-right"></i>

                    <span class="d-none d-md-inline">
                        Logout
                    </span>

                </a>

            </div>

        </div>

    </nav>


    <!-- ================================================================== -->
    <!-- CONTENT -->
    <!-- ================================================================== -->

    <div class="container-fluid px-4 py-4">


        <!-- ================================================================== -->
        <!-- HEADER -->
        <!-- ================================================================== -->

        <div class="mb-4">

            <h2 class="dashboard-title">

                Dashboard Penjual

            </h2>

            <p class="text-muted mb-0">

                Kelola produk, stok, pesanan, dan lokasi usaha Anda.

            </p>

        </div>


        <!-- ================================================================== -->
        <!-- STATUS LOKASI USAHA -->
        <!-- ================================================================== -->

        <div class="card location-card shadow-sm mb-4">

            <div class="card-body p-4">

                <div class="row align-items-center g-3">


                    <!-- ICON -->

                    <div class="col-auto">

                        <div
                            class="
                                location-icon
                                <?= $lokasiSudahDiatur
                                    ? 'bg-success-subtle text-success'
                                    : 'bg-warning-subtle text-warning'
                                ?>
                            "
                        >

                            <i
                                class="
                                    bi
                                    <?= $lokasiSudahDiatur
                                        ? 'bi-geo-alt-fill'
                                        : 'bi-geo-alt'
                                    ?>
                                "
                            ></i>

                        </div>

                    </div>


                    <!-- INFORMASI -->

                    <div class="col">

                        <?php if ($lokasiSudahDiatur): ?>

                            <div class="d-flex flex-wrap align-items-center gap-2">

                                <h5 class="fw-bold mb-0">
                                    Lokasi Usaha Sudah Diatur
                                </h5>

                                <span class="badge text-bg-success">
                                    Aktif
                                </span>

                            </div>

                            <p class="text-muted mb-2 mt-1">

                                Lokasi usaha Anda sudah tersedia dan dapat
                                digunakan untuk menghitung jarak serta ongkos
                                kirim pembeli.

                            </p>


                            <div class="row g-2">

                                <div class="col-md-6">

                                    <div class="coordinate-box">

                                        <small class="text-muted d-block">
                                            Latitude
                                        </small>

                                        <span class="coordinate-value">

                                            <?= e($penjual['latitude']) ?>

                                        </span>

                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <div class="coordinate-box">

                                        <small class="text-muted d-block">
                                            Longitude
                                        </small>

                                        <span class="coordinate-value">

                                            <?= e($penjual['longitude']) ?>

                                        </span>

                                    </div>

                                </div>

                            </div>

                        <?php else: ?>

                            <h5 class="fw-bold mb-1">

                                Lokasi Usaha Belum Diatur

                            </h5>

                            <p class="text-muted mb-0">

                                Atur lokasi usaha agar SIPESTA dapat menghitung
                                jarak dan ongkos kirim dari toko Anda ke pembeli.

                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- TOMBOL -->

                    <div class="col-12 col-lg-auto">

                        <?php if ($lokasiSudahDiatur): ?>

                            <a
                                href="lokasi_usaha.php"
                                class="btn btn-outline-success"
                            >

                                <i class="bi bi-pencil-square"></i>

                                Ubah Lokasi

                            </a>

                        <?php else: ?>

                            <a
                                href="lokasi_usaha.php"
                                class="btn btn-warning"
                            >

                                <i class="bi bi-geo-alt"></i>

                                Atur Lokasi Usaha

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================================== -->
        <!-- STATISTIK PRODUK -->
        <!-- ================================================================== -->

        <h5 class="fw-bold mb-3">

            Ringkasan Produk

        </h5>


        <div class="row g-3 mb-4">


            <!-- TOTAL PRODUK -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card card-stat shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">
                                    Total Produk
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">
                                    <?= $totalProduk ?>
                                </h3>

                            </div>

                            <div class="icon-box bg-primary-subtle text-primary">

                                <i class="bi bi-box-seam"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PRODUK TERSEDIA -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card card-stat shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">
                                    Produk Tersedia
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">
                                    <?= $produkTersedia ?>
                                </h3>

                            </div>

                            <div class="icon-box bg-success-subtle text-success">

                                <i class="bi bi-check-circle"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- STOK -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card card-stat shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">
                                    Total Stok
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">

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

                            <div class="icon-box bg-warning-subtle text-warning">

                                <i class="bi bi-boxes"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- HABIS -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card card-stat shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">
                                    Stok Habis
                                </small>

                                <h3 class="fw-bold mt-2 mb-0">
                                    <?= $produkHabis ?>
                                </h3>

                            </div>

                            <div class="icon-box bg-danger-subtle text-danger">

                                <i class="bi bi-exclamation-circle"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================================== -->
        <!-- STATISTIK PESANAN -->
        <!-- ================================================================== -->

        <h5 class="fw-bold mb-3">

            Ringkasan Pesanan

        </h5>


        <div class="row g-3 mb-4">


            <!-- MENUNGGU -->

            <div class="col-6 col-md-4 col-xl">

                <a
                    href="pesanan.php?status=menunggu"
                    class="text-decoration-none"
                >

                    <div class="card card-stat shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Menunggu
                            </small>

                            <h3 class="fw-bold text-warning mt-2">
                                <?= $pesananMenunggu ?>
                            </h3>

                        </div>

                    </div>

                </a>

            </div>


            <!-- DIPROSES -->

            <div class="col-6 col-md-4 col-xl">

                <a
                    href="pesanan.php?status=diproses"
                    class="text-decoration-none"
                >

                    <div class="card card-stat shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Diproses
                            </small>

                            <h3 class="fw-bold text-primary mt-2">
                                <?= $pesananDiproses ?>
                            </h3>

                        </div>

                    </div>

                </a>

            </div>


            <!-- DIKIRIM -->

            <div class="col-6 col-md-4 col-xl">

                <a
                    href="pesanan.php?status=dikirim"
                    class="text-decoration-none"
                >

                    <div class="card card-stat shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Dikirim
                            </small>

                            <h3 class="fw-bold text-info mt-2">
                                <?= $pesananDikirim ?>
                            </h3>

                        </div>

                    </div>

                </a>

            </div>


            <!-- SELESAI -->

            <div class="col-6 col-md-4 col-xl">

                <a
                    href="pesanan.php?status=selesai"
                    class="text-decoration-none"
                >

                    <div class="card card-stat shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Selesai
                            </small>

                            <h3 class="fw-bold text-success mt-2">
                                <?= $pesananSelesai ?>
                            </h3>

                        </div>

                    </div>

                </a>

            </div>


            <!-- DIBATALKAN -->

            <div class="col-6 col-md-4 col-xl">

                <a
                    href="pesanan.php?status=dibatalkan"
                    class="text-decoration-none"
                >

                    <div class="card card-stat shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Dibatalkan
                            </small>

                            <h3 class="fw-bold text-danger mt-2">
                                <?= $pesananDibatalkan ?>
                            </h3>

                        </div>

                    </div>

                </a>

            </div>

        </div>


        <!-- ================================================================== -->
        <!-- PENDAPATAN -->
        <!-- ================================================================== -->

        <div class="row g-3 mb-4">

            <div class="col-12 col-lg-4">

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body">

                        <div class="d-flex align-items-center gap-3">

                            <div class="icon-box bg-success-subtle text-success">

                                <i class="bi bi-cash-stack"></i>

                            </div>

                            <div>

                                <small class="text-muted">
                                    Pendapatan Pesanan Selesai
                                </small>

                                <h4 class="fw-bold text-success mb-0">

                                    <?= rupiah($totalPendapatan) ?>

                                </h4>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================================== -->
        <!-- MENU CEPAT -->
        <!-- ================================================================== -->

        <h5 class="fw-bold mb-3">

            Menu Penjual

        </h5>


        <div class="row g-3 mb-4">


            <!-- KELOLA PRODUK -->

            <div class="col-12 col-md-6 col-lg-3">

                <a
                    href="produk.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="card menu-card shadow-sm h-100">

                        <div class="card-body text-center py-4">

                            <i class="bi bi-box-seam text-primary menu-icon"></i>

                            <h5 class="fw-bold mt-3">
                                Kelola Produk
                            </h5>

                            <p class="text-muted mb-0">
                                Tambah, edit, dan kelola produk.
                            </p>

                        </div>

                    </div>

                </a>

            </div>


            <!-- KELOLA PESANAN -->

            <div class="col-12 col-md-6 col-lg-3">

                <a
                    href="pesanan.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="card menu-card shadow-sm h-100">

                        <div class="card-body text-center py-4">

                            <i class="bi bi-cart-check text-success menu-icon"></i>

                            <h5 class="fw-bold mt-3">
                                Kelola Pesanan
                            </h5>

                            <p class="text-muted mb-0">
                                Lihat dan proses pesanan pembeli.
                            </p>

                        </div>

                    </div>

                </a>

            </div>


            <!-- LOKASI USAHA -->

            <div class="col-12 col-md-6 col-lg-3">

                <a
                    href="lokasi_usaha.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="card menu-card shadow-sm h-100">

                        <div class="card-body text-center py-4">

                            <i
                                class="
                                    bi
                                    bi-geo-alt
                                    <?= $lokasiSudahDiatur
                                        ? 'text-success'
                                        : 'text-warning'
                                    ?>
                                    menu-icon
                                "
                            ></i>

                            <h5 class="fw-bold mt-3">
                                Lokasi Usaha
                            </h5>

                            <p class="text-muted mb-2">

                                Atur lokasi untuk perhitungan ongkir.

                            </p>

                            <?php if ($lokasiSudahDiatur): ?>

                                <span class="badge text-bg-success">

                                    <i class="bi bi-check-circle"></i>

                                    Sudah Diatur

                                </span>

                            <?php else: ?>

                                <span class="badge text-bg-warning">

                                    <i class="bi bi-exclamation-circle"></i>

                                    Belum Diatur

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </a>

            </div>


            <!-- KELUAR -->

            <div class="col-12 col-md-6 col-lg-3">

                <a
                    href="../logout.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="card menu-card shadow-sm h-100">

                        <div class="card-body text-center py-4">

                            <i class="bi bi-box-arrow-right text-danger menu-icon"></i>

                            <h5 class="fw-bold mt-3">
                                Keluar
                            </h5>

                            <p class="text-muted mb-0">
                                Keluar dari akun penjual.
                            </p>

                        </div>

                    </div>

                </a>

            </div>

        </div>


        <!-- ================================================================== -->
        <!-- PESANAN TERBARU -->
        <!-- ================================================================== -->

        <div class="card table-card shadow-sm">

            <div class="card-header bg-white py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="fw-bold mb-0">

                        <i class="bi bi-clock-history"></i>

                        Pesanan Terbaru

                    </h5>


                    <a
                        href="pesanan.php"
                        class="btn btn-sm btn-outline-success"
                    >

                        Lihat Semua

                    </a>

                </div>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="px-3">
                                    Kode
                                </th>

                                <th>
                                    Pembeli
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Pembayaran
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

                        <?php if ($pesananTerbaru->num_rows > 0): ?>

                            <?php while ($pesanan = $pesananTerbaru->fetch_assoc()): ?>

                                <tr>

                                    <td class="px-3 fw-semibold">

                                        <?= e($pesanan['kode_pesanan']) ?>

                                    </td>


                                    <td>

                                        <?= e($pesanan['nama_penerima']) ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $pesanan['tanggal_pesanan']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $pesanan['metode_pembayaran']
                                        ) ?>

                                    </td>


                                    <td class="fw-semibold">

                                        <?= rupiah(
                                            $pesanan['total_produk']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= badgeStatusDashboard(
                                            $pesanan['status']
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="detail_pesanan.php?id=<?= (int)$pesanan['id'] ?>"
                                            class="btn btn-sm btn-outline-success"
                                            title="Lihat Detail"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center text-muted py-4"
                                >

                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>

                                    Belum ada pesanan.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </div>


    <!-- Bootstrap JS -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    ></script>


    </body>

    </html>