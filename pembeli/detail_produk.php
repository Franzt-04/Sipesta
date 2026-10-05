<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole('pembeli');


// =====================================================
// ID PRODUK
// =====================================================

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


// =====================================================
// AMBIL PRODUK
// =====================================================

$stmt = $conn->prepare("
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

        pen.id AS penjual_id,
        pen.nama_usaha,
        pen.alamat AS alamat_toko

    FROM produk p

    INNER JOIN kategori k
        ON p.kategori_id = k.id

    INNER JOIN penjual pen
        ON p.penjual_id = pen.id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header(
        "Location: index.php?error="
        . urlencode("Produk tidak ditemukan.")
    );

    exit;
}


$produk = $result->fetch_assoc();

$stmt->close();

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
        <?= e($produk['nama_produk']); ?> - SIPESTA
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .product-detail-image {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 15px;
        }

        .no-image {
            height: 400px;
            background: #e9ecef;
            border-radius: 15px;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .price {
            color: #198754;
            font-size: 28px;
            font-weight: 700;
        }

        .detail-card {
            border: none;
            border-radius: 15px;
        }

    </style>

</head>

<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-dark bg-success">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold"
        >

            <i class="fa-solid fa-store"></i>

            SIPESTA

        </a>


        <div>

            <span class="text-white me-3">

                <?= e($_SESSION['nama'] ?? 'Pembeli'); ?>

            </span>

            <a
                href="../logout.php"
                class="btn btn-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-4">


    <a
        href="index.php"
        class="btn btn-outline-secondary mb-4"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Kembali

    </a>


    <div class="card detail-card shadow-sm">

        <div class="card-body p-4">

            <div class="row g-4">


                <!-- FOTO -->

                <div class="col-md-6">

                    <?php if (!empty($produk['foto'])): ?>

                        <img
                            src="../uploads/produk/<?= e($produk['foto']); ?>"
                            class="product-detail-image"
                            alt="<?= e($produk['nama_produk']); ?>"
                        >

                    <?php else: ?>

                        <div class="no-image">

                            <i
                                class="fa-solid fa-image fa-5x text-secondary"
                            ></i>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- DETAIL -->

                <div class="col-md-6">


                    <span class="badge bg-success mb-2">

                        <?= e($produk['nama_kategori']); ?>

                    </span>


                    <h2 class="fw-bold">

                        <?= e($produk['nama_produk']); ?>

                    </h2>


                    <div class="text-muted mb-3">

                        <i class="fa-solid fa-store"></i>

                        <?= e($produk['nama_usaha']); ?>

                    </div>


                    <div class="price mb-1">

                        <?= rupiah($produk['harga']); ?>

                        <small class="text-muted fs-6">

                            / <?= e($produk['satuan']); ?>

                        </small>

                    </div>


                    <div class="mb-4">

                        <?php if ($produk['stok'] > 0): ?>

                            <span class="badge bg-success">

                                Stok tersedia

                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">

                                Stok habis

                            </span>

                        <?php endif; ?>

                        <span class="text-muted ms-2">

                            <?= e($produk['stok']); ?>

                            <?= e($produk['satuan']); ?>

                        </span>

                    </div>


                    <hr>


                    <h5 class="fw-bold">
                        Deskripsi
                    </h5>


                    <p class="text-muted">

                        <?php if (!empty($produk['deskripsi'])): ?>

                            <?= nl2br(e($produk['deskripsi'])); ?>

                        <?php else: ?>

                            Tidak ada deskripsi produk.

                        <?php endif; ?>

                    </p>


                    <hr>


                    <h5 class="fw-bold">
                        Informasi Toko
                    </h5>


                    <p class="mb-1">

                        <strong>
                            Nama Toko:
                        </strong>

                        <?= e($produk['nama_usaha']); ?>

                    </p>


                    <?php if (!empty($produk['alamat_toko'])): ?>

                        <p>

                            <strong>
                                Alamat:
                            </strong>

                            <?= e($produk['alamat_toko']); ?>

                        </p>

                    <?php endif; ?>


                    <!-- =================================================
                         BUTTON KERANJANG
                    ================================================== -->

                    <?php if ($produk['stok'] > 0): ?>

                        <form action="tambah_keranjang.php" method="POST">

    <input
        type="hidden"
        name="produk_id"
        value="<?= (int)$produk['id'] ?>"
    >

    <div class="mb-3">

        <label class="form-label fw-semibold">
            Jumlah
        </label>

        <div class="input-group">

            <input
                type="number"
                name="jumlah"
                class="form-control"
                value="1"
                min="0.01"
                max="<?= e($produk['stok']) ?>"
                step="0.01"
                required
            >

            <span class="input-group-text">
                <?= e($produk['satuan']) ?>
            </span>

        </div>

        <small class="text-muted">
            Stok tersedia:
            <?= e($produk['stok']) ?>
            <?= e($produk['satuan']) ?>
        </small>

    </div>

    <button
        type="submit"
        class="btn btn-success btn-lg w-100">

        <i class="fa-solid fa-cart-plus"></i>

        Tambah ke Keranjang

    </button>

</form>
                    <?php else: ?>

                        <button
                            type="button"
                            class="btn btn-secondary btn-lg w-100 mt-3"
                            disabled
                        >

                            Stok Habis

                        </button>

                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>