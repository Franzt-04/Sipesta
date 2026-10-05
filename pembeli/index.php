<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   DATA USER
========================================================= */

$nama_user = $_SESSION['nama'] ?? 'Pembeli';

/* =========================================================
   SEARCH
========================================================= */

$keyword = trim($_GET['q'] ?? '');

/* =========================================================
   QUERY PRODUK
========================================================= */

$sql = "
    SELECT
        p.id,
        p.nama_produk,
        p.deskripsi,
        p.harga,
        p.satuan,
        p.stok,
        p.foto,
        p.status,
        k.nama_kategori,
        pen.nama_usaha
    FROM produk p

    LEFT JOIN kategori k
        ON p.kategori_id = k.id

    LEFT JOIN penjual pen
        ON p.penjual_id = pen.id

    WHERE p.status = 'tersedia'
      AND p.stok > 0
";

$params = [];
$types = "";

if ($keyword !== '') {

    $sql .= "
        AND (
            p.nama_produk LIKE ?
            OR p.deskripsi LIKE ?
            OR pen.nama_usaha LIKE ?
            OR k.nama_kategori LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types = "ssss";
}

$sql .= "
    ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query produk gagal: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$produk = [];

while ($row = $result->fetch_assoc()) {
    $produk[] = $row;
}

$stmt->close();

/* =========================================================
   HITUNG JUMLAH KERANJANG
========================================================= */

$jumlah_keranjang = 0;

if (
    isset($_SESSION['keranjang']) &&
    is_array($_SESSION['keranjang'])
) {
    foreach ($_SESSION['keranjang'] as $jumlah) {
        $jumlah_keranjang += (int)$jumlah;
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

    <title>Beranda Pembeli - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }

        .navbar {
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }

        .navbar-brand {
            font-weight: bold;
        }

        .hero {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #198754
            );

            color: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
        }

        .hero h2 {
            font-weight: bold;
        }

        .search-box {
            background: white;
            padding: 8px;
            border-radius: 12px;
        }

        .search-box input {
            border: none;
            box-shadow: none !important;
        }

        .product-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
            transition: .2s;
            background: white;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,.10);
        }

        /* =====================================================
           FOTO PRODUK
        ===================================================== */

        .product-image {
            height: 210px;
            background: #eef2f7;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-image {
            font-size: 55px;
            color: #adb5bd;
        }

        .product-name {
            font-weight: bold;
            font-size: 18px;
        }

        .product-price {
            color: #198754;
            font-weight: bold;
            font-size: 20px;
        }

        .store-name {
            color: #6c757d;
            font-size: 14px;
        }

        .badge-stock {
            font-size: 12px;
        }

        .empty-box {
            background: white;
            border-radius: 18px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

    </style>

</head>

<body>

<!-- ======================================================
     NAVBAR
======================================================= -->

<nav class="navbar navbar-expand-lg bg-white">

    <div class="container">

        <a
            class="navbar-brand text-primary"
            href="index.php"
        >

            <i class="fa-solid fa-store"></i>
            SIPESTA

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <!-- BERANDA -->

                <li class="nav-item me-lg-3">

                    <a
                        class="nav-link"
                        href="index.php"
                    >

                        <i class="fa-solid fa-house"></i>
                        Beranda

                    </a>

                </li>


                <!-- KERANJANG -->

                <li class="nav-item me-lg-3">

                    <a
                        class="btn btn-outline-success"
                        href="keranjang.php"
                    >

                        <i class="fa-solid fa-cart-shopping"></i>
                        Keranjang

                        <?php if ($jumlah_keranjang > 0): ?>

                            <span class="badge bg-danger ms-1">
                                <?= $jumlah_keranjang ?>
                            </span>

                        <?php endif; ?>

                    </a>

                </li>


                <!-- PESANAN -->

                <li class="nav-item me-lg-3">

                    <a
                        class="nav-link"
                        href="pesanan.php"
                    >

                        <i class="fa-solid fa-box"></i>
                        Pesanan

                    </a>

                </li>


                <!-- USER -->

                <li class="nav-item dropdown">

                    <a
                        class="nav-link dropdown-toggle"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >

                        <i class="fa-solid fa-user"></i>

                        <?= htmlspecialchars($nama_user) ?>

                    </a>


                    <ul class="dropdown-menu dropdown-menu-end">

                        <!-- PROFIL -->

                        <li>

                            <a
                                class="dropdown-item"
                                href="profil.php"
                            >

                                <i class="fa-solid fa-user me-2"></i>
                                Profil

                            </a>

                        </li>


                        <li>
                            <hr class="dropdown-divider">
                        </li>


                        <!-- LOGOUT -->

                        <li>

                            <a
                                class="dropdown-item text-danger"
                                href="../logout.php"
                            >

                                <i class="fa-solid fa-right-from-bracket me-2"></i>
                                Logout

                            </a>

                        </li>

                    </ul>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- ======================================================
     CONTENT
======================================================= -->

<div class="container py-4">


    <!-- HERO -->

    <div class="hero">

        <div class="row align-items-center">

            <div class="col-md-7">

                <h2>

                    Selamat Datang,
                    <?= htmlspecialchars($nama_user) ?> 👋

                </h2>

                <p class="mb-0">

                    Temukan berbagai produk dari penjual
                    terpercaya di SIPESTA.

                </p>

            </div>


            <div class="col-md-5 mt-4 mt-md-0">

                <form method="GET">

                    <div class="search-box d-flex">

                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari produk atau toko..."
                            value="<?= htmlspecialchars($keyword) ?>"
                        >

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fa-solid fa-search"></i>
                            Cari

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- JUDUL -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="fw-bold mb-1">

                <i class="fa-solid fa-box-open"></i>
                Produk Tersedia

            </h4>


            <?php if ($keyword !== ''): ?>

                <small class="text-muted">

                    Hasil pencarian:
                    <strong>
                        <?= htmlspecialchars($keyword) ?>
                    </strong>

                </small>

            <?php else: ?>

                <small class="text-muted">

                    Produk yang tersedia untuk dipesan

                </small>

            <?php endif; ?>


































            y6hny  6                                     hhhhhh+            n 6hhh 
        </div>

    </div>


    <!-- PRODUK -->

    <?php if (count($produk) > 0): ?>

        <div class="row g-4">

            <?php foreach ($produk as $item): ?>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">

                    <div class="product-card">


                        <!-- FOTO PRODUK -->

                        <div class="product-image">

                            <?php

$foto = trim($item['foto'] ?? '');

/*
|--------------------------------------------------------------------------
| Ambil nama file saja
|--------------------------------------------------------------------------
| Contoh:
| Ikan_nila.jpg
| uploads/produk/Ikan_nila.jpg
| C:\xampp\htdocs\sipesta\uploads\produk\Ikan_nila.jpg
|
| semuanya akan menjadi:
| Ikan_nila.jpg
|--------------------------------------------------------------------------
*/

$foto = str_replace('\\', '/', $foto);

$namaFileFoto = basename($foto);

/*
|--------------------------------------------------------------------------
| Lokasi file sebenarnya di komputer/server
|--------------------------------------------------------------------------
*/

$fileFoto = __DIR__ . "/../uploads/produk/" . $namaFileFoto;

/*
|--------------------------------------------------------------------------
| URL yang dibaca browser
|--------------------------------------------------------------------------
*/

$urlFoto = "../uploads/produk/" . rawurlencode($namaFileFoto);

?>

                        <?php if (
                            $namaFileFoto !== '' &&
                            file_exists($fileFoto)
                        ): ?>

                            <img
                                src="<?= htmlspecialchars($urlFoto) ?>"
                                alt="<?= htmlspecialchars($item['nama_produk']) ?>"
                                style="
                                    width: 100%;
                                    height: 210px;
                                    object-fit: cover;
                                    display: block;
                                "
                            >

                        <?php else: ?>

                            <div class="no-image">

                                <i class="fa-solid fa-image"></i>

                            </div>

                        <?php endif; ?>

                                                </div>


                        <!-- DETAIL PRODUK -->

                        <div class="p-3">


                            <!-- KATEGORI -->

                            <?php if (
                                !empty($item['nama_kategori'])
                            ): ?>

                                <span class="badge bg-primary mb-2">

                                    <?= htmlspecialchars(
                                        $item['nama_kategori']
                                    ) ?>

                                </span>

                            <?php endif; ?>


                            <!-- NAMA PRODUK -->

                            <div class="product-name mb-1">

                                <?= htmlspecialchars(
                                    $item['nama_produk']
                                ) ?>

                            </div>


                            <!-- TOKO -->

                            <div class="store-name mb-2">

                                <i class="fa-solid fa-store"></i>

                                <?= htmlspecialchars(
                                    $item['nama_usaha']
                                    ?? 'Nama usaha belum tersedia'
                                ) ?>

                            </div>


                            <!-- HARGA -->

                            <div class="product-price">

                                Rp
                                <?= number_format(
                                    (float)$item['harga'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </div>


                            <!-- SATUAN -->

                            <small class="text-muted">

                                /
                                <?= htmlspecialchars(
                                    $item['satuan']
                                ) ?>

                            </small>


                            <!-- STOK -->

                            <div class="mt-2">

                                <span
                                    class="badge bg-success badge-stock"
                                >

                                    Stok:

                                    <?= number_format(
                                        (float)$item['stok'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $item['satuan']
                                    ) ?>

                                </span>

                            </div>


                            <!-- DESKRIPSI -->

                            <?php if (
                                !empty($item['deskripsi'])
                            ): ?>

                                <p
                                    class="text-muted small mt-2 mb-3"
                                >

                                    <?= htmlspecialchars(
                                        mb_strimwidth(
                                            $item['deskripsi'],
                                            0,
                                            90,
                                            '...'
                                        )
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <!-- TOMBOL -->

                            <div class="d-grid mt-3">

                                <a
                                    href="detail_produk.php?id=<?= (int)$item['id'] ?>"
                                    class="btn btn-primary"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                    Lihat Produk

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


    <?php else: ?>


        <!-- TIDAK ADA PRODUK -->

        <div class="empty-box">

            <div
                class="text-muted mb-3"
                style="font-size:60px;"
            >

                <i class="fa-solid fa-box-open"></i>

            </div>


            <?php if ($keyword !== ''): ?>

                <h5 class="fw-bold">

                    Produk tidak ditemukan

                </h5>

                <p class="text-muted">

                    Tidak ada produk yang sesuai dengan
                    pencarian
                    "<strong><?= htmlspecialchars($keyword) ?></strong>".

                </p>


                <a
                    href="index.php"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-rotate-left"></i>

                    Lihat Semua Produk

                </a>


            <?php else: ?>

                <h5 class="fw-bold">

                    Belum Ada Produk

                </h5>

                <p class="text-muted">

                    Saat ini belum ada produk yang tersedia
                    untuk dipesan.

                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>