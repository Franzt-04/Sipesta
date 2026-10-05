<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");


/*
|--------------------------------------------------------------------------
| USER LOGIN
|--------------------------------------------------------------------------
*/

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CARI DATA PEMBELI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$pembeli = $result->fetch_assoc();

$stmt->close();


if (!$pembeli) {
    die(
        "Data pembeli tidak ditemukan untuk user_id: "
        . $user_id
    );
}

$pembeli_id = (int)$pembeli['id'];


/*
|--------------------------------------------------------------------------
| CARI KERANJANG
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM keranjang
    WHERE pembeli_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $pembeli_id);
$stmt->execute();

$result = $stmt->get_result();
$keranjang = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| SIAPKAN DATA
|--------------------------------------------------------------------------
*/

$items = [];
$total = 0;


/*
|--------------------------------------------------------------------------
| AMBIL DETAIL KERANJANG
|--------------------------------------------------------------------------
*/

if ($keranjang) {

    $keranjang_id = (int)$keranjang['id'];

    $stmt = $conn->prepare("
        SELECT
            kd.id,
            kd.produk_id,
            kd.jumlah,
            kd.harga,
            kd.subtotal,

            p.nama_produk,
            p.foto,
            p.stok,
            p.satuan,
            p.status,

            pen.nama_usaha

        FROM keranjang_detail kd

        INNER JOIN produk p
            ON p.id = kd.produk_id

        INNER JOIN penjual pen
            ON pen.id = p.penjual_id

        WHERE kd.keranjang_id = ?

        ORDER BY kd.id DESC
    ");

    $stmt->bind_param("i", $keranjang_id);
    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        /*
        |--------------------------------------------------------------------------
        | HITUNG ULANG SUBTOTAL
        |--------------------------------------------------------------------------
        */

        $jumlah = (float)$row['jumlah'];
        $harga  = (float)$row['harga'];

        $row['subtotal'] = $jumlah * $harga;

        $total += $row['subtotal'];

        $items[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Keranjang - SIPESTA</title>


    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f5f7fb;
        }


        .navbar-brand {
            font-weight: 700;
        }


        .cart-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }


        .product-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
        }


        .summary-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);

            position: sticky;
            top: 20px;
        }


        .price {
            font-weight: 700;
        }


        .product-name {
            font-size: 17px;
        }


        .stock-warning {
            font-size: 13px;
        }


        @media (max-width: 768px) {

            .product-img {
                width: 70px;
                height: 70px;
            }


            .product-name {
                font-size: 15px;
            }


            .summary-card {
                position: static;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container">

        <a
            class="navbar-brand text-success"
            href="index.php"
        >

            <i class="fa-solid fa-store"></i>

            SIPESTA

        </a>


        <div class="d-flex align-items-center gap-2">

            <a
                href="index.php"
                class="btn btn-outline-success btn-sm"
            >

                <i class="fa-solid fa-shop"></i>

                <span class="d-none d-sm-inline">
                    Belanja
                </span>

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Apakah Anda yakin ingin keluar?')"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span class="d-none d-sm-inline">
                    Keluar
                </span>

            </a>

        </div>

    </div>

</nav>


<!-- =========================================================
     CONTENT
========================================================= -->

<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="fa-solid fa-cart-shopping text-success"></i>

                Keranjang Saya

            </h3>


            <p class="text-muted mb-0">

                Periksa kembali produk sebelum checkout.

            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-outline-success"
        >

            <i class="fa-solid fa-arrow-left"></i>

            <span class="d-none d-sm-inline">
                Lanjut Belanja
            </span>

        </a>

    </div>



    <!-- =====================================================
         PESAN SUKSES
    ====================================================== -->

    <?php if (!empty($success)): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-check"></i>

            <?= e($success) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         PESAN ERROR
    ====================================================== -->

    <?php if (!empty($error)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= e($error) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         KERANJANG KOSONG
    ====================================================== -->

    <?php if (empty($items)): ?>

        <div class="card cart-card">

            <div class="card-body text-center py-5">

                <i
                    class="fa-solid fa-cart-shopping text-muted"
                    style="font-size:60px;"
                ></i>


                <h4 class="mt-4">

                    Keranjang masih kosong

                </h4>


                <p class="text-muted">

                    Silakan pilih produk terlebih dahulu.

                </p>


                <a
                    href="index.php"
                    class="btn btn-success"
                >

                    <i class="fa-solid fa-store"></i>

                    Mulai Belanja

                </a>

            </div>

        </div>


    <?php else: ?>


        <!-- =================================================
             PRODUK + RINGKASAN
        ================================================== -->

        <div class="row g-4">


            <!-- =================================================
                 DAFTAR PRODUK
            ================================================== -->

            <div class="col-lg-8">


                <?php foreach ($items as $item): ?>


                    <div class="card cart-card mb-3">

                        <div class="card-body">


                            <div class="row align-items-center g-3">


                                <!-- FOTO -->

                                <div class="col-3 col-md-2">

                                    <?php if (!empty($item['foto'])): ?>

                                        <img
                                            src="../uploads/produk/<?= e($item['foto']) ?>"
                                            class="product-img"
                                            alt="<?= e($item['nama_produk']) ?>"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="bg-light rounded d-flex align-items-center justify-content-center"
                                            style="width:90px;height:90px;"
                                        >

                                            <i
                                                class="fa-solid fa-image text-muted fs-3"
                                            ></i>

                                        </div>

                                    <?php endif; ?>

                                </div>



                                <!-- INFORMASI PRODUK -->

                                <div class="col-9 col-md-4">


                                    <h5 class="fw-bold mb-1 product-name">

                                        <?= e($item['nama_produk']) ?>

                                    </h5>


                                    <small class="text-muted">

                                        <i class="fa-solid fa-store"></i>

                                        <?= e($item['nama_usaha']) ?>

                                    </small>


                                    <div class="mt-2">

                                        <span class="price text-success">

                                            <?= rupiah($item['harga']) ?>

                                        </span>


                                        <small class="text-muted">

                                            / <?= e($item['satuan']) ?>

                                        </small>

                                    </div>


                                    <!-- STATUS -->

                                    <?php if ($item['status'] !== 'tersedia'): ?>

                                        <div class="mt-1">

                                            <span class="badge bg-danger">

                                                Produk tidak tersedia

                                            </span>

                                        </div>

                                    <?php endif; ?>


                                </div>



                                <!-- JUMLAH -->

                                <div class="col-md-3">


                                    <form
                                        action="update_keranjang.php"
                                        method="POST"
                                        class="d-flex gap-2"
                                    >


                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$item['id'] ?>"
                                        >


                                        <input
                                            type="number"
                                            name="jumlah"
                                            class="form-control"
                                            value="<?= e($item['jumlah']) ?>"
                                            min="0.01"
                                            max="<?= e($item['stok']) ?>"
                                            step="0.01"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-outline-success"
                                            title="Update jumlah"
                                        >

                                            <i class="fa-solid fa-rotate"></i>

                                        </button>

                                    </form>


                                    <small class="text-muted">

                                        Stok tersedia:

                                        <?= e($item['stok']) ?>

                                        <?= e($item['satuan']) ?>

                                    </small>


                                </div>



                                <!-- SUBTOTAL -->

                                <div class="col-md-2 text-md-end">


                                    <div class="fw-bold mb-2">

                                        <?= rupiah($item['subtotal']) ?>

                                    </div>


                                    <a
                                        href="hapus_keranjang.php?id=<?= (int)$item['id'] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Hapus produk dari keranjang?')"
                                        title="Hapus produk"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                    </a>

                                </div>


                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            </div>



            <!-- =================================================
                 RINGKASAN
            ================================================== -->

            <div class="col-lg-4">


                <div class="card summary-card">


                    <div class="card-body">


                        <h5 class="fw-bold mb-4">

                            <i class="fa-solid fa-receipt text-success"></i>

                            Ringkasan Pesanan

                        </h5>



                        <!-- JUMLAH PRODUK -->

                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total Produk
                            </span>


                            <span class="fw-bold">

                                <?= count($items) ?>

                            </span>

                        </div>



                        <!-- TOTAL ITEM -->

                        <?php

                        $total_qty = 0;

                        foreach ($items as $item) {

                            $total_qty += (float)$item['jumlah'];

                        }

                        ?>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total Jumlah
                            </span>


                            <span class="fw-bold">

                                <?= rtrim(rtrim(number_format($total_qty, 2, ',', '.'), '0'), ',') ?>

                            </span>

                        </div>


                        <hr>



                        <!-- TOTAL -->

                        <div class="d-flex justify-content-between align-items-center">

                            <span class="fw-bold">

                                Total

                            </span>


                            <span class="fw-bold text-success fs-4">

                                <?= rupiah($total) ?>

                            </span>

                        </div>



                        <!-- CHECKOUT -->

                        <div class="d-grid mt-4">


                            <a
                                href="checkout.php"
                                class="btn btn-success btn-lg"
                            >

                                <i class="fa-solid fa-credit-card"></i>

                                Lanjut ke Checkout

                            </a>

                        </div>


                    </div>

                </div>


            </div>


        </div>


    <?php endif; ?>


</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>