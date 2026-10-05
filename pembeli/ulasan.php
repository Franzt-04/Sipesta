<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$user_id = userId();

$pesanan_id = (int)($_GET['pesanan_id'] ?? 0);
$produk_id  = (int)($_GET['produk_id'] ?? 0);

if ($pesanan_id <= 0 || $produk_id <= 0) {
    header("Location: riwayat_pesanan.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBELI
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
    die("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int)$pembeli['id'];


/*
|--------------------------------------------------------------------------
| CEK PESANAN DAN PRODUK
|--------------------------------------------------------------------------
|
| Hanya pemilik pesanan yang dapat memberikan ulasan.
| Ulasan hanya dapat diberikan untuk pesanan selesai.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        ps.id AS pesanan_id,
        ps.kode_pesanan,
        ps.status,

        dp.produk_id,
        dp.jumlah,
        dp.harga,

        p.nama_produk,
        p.foto,
        p.satuan

    FROM pesanan ps

    INNER JOIN detail_pesanan dp
        ON dp.pesanan_id = ps.id

    INNER JOIN produk p
        ON p.id = dp.produk_id

    WHERE ps.id = ?
      AND ps.pembeli_id = ?
      AND dp.produk_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $pesanan_id,
    $pembeli_id,
    $produk_id
);

$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$stmt->close();

if (!$data) {
    die("Produk dalam pesanan tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| PESANAN HARUS SELESAI
|--------------------------------------------------------------------------
*/

if ($data['status'] !== 'selesai') {

    header(
        "Location: detail_pesanan.php?id=" .
        $pesanan_id .
        "&error=" .
        urlencode("Produk hanya dapat diulas setelah pesanan selesai.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK ULASAN YANG SUDAH ADA
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        rating,
        ulasan
    FROM ulasan
    WHERE pesanan_id = ?
      AND produk_id = ?
      AND pembeli_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $pesanan_id,
    $produk_id,
    $pembeli_id
);

$stmt->execute();

$result = $stmt->get_result();

$ulasan_lama = $result->fetch_assoc();

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
        <?= $ulasan_lama ? 'Edit Ulasan' : 'Beri Ulasan' ?>
        - SIPESTA
    </title>

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
        }

        .navbar {
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }

        .review-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,.07);
        }

        .review-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #198754
            );

            color: white;
            padding: 30px;
        }

        .product-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }

        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            background: #e9ecef;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .rating input {
            display: none;
        }

        .rating label {
            font-size: 38px;
            color: #ced4da;
            cursor: pointer;
            transition: .15s;
        }

        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #ffc107;
        }

        .rating-text {
            color: #6c757d;
            font-size: 14px;
        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-white">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold text-primary"
        >

            <i class="fa-solid fa-store"></i>
            SIPESTA

        </a>

        <div class="ms-auto">

            <a
                href="detail_pesanan.php?id=<?= $pesanan_id ?>"
                class="btn btn-outline-primary"
            >

                <i class="fa-solid fa-arrow-left"></i>
                Kembali

            </a>

        </div>

    </div>

</nav>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card review-card">

                <!-- HEADER -->

                <div class="review-header">

                    <h3 class="mb-1">

                        <i class="fa-solid fa-star"></i>

                        <?= $ulasan_lama
                            ? 'Edit Ulasan'
                            : 'Beri Ulasan' ?>

                    </h3>

                    <p class="mb-0">

                        Bagikan pengalaman Anda terhadap produk ini.

                    </p>

                </div>


                <div class="card-body p-4">


                    <!-- PRODUK -->

                    <div class="product-box mb-4">

                        <div class="d-flex align-items-center gap-3">

                            <?php

                            $foto = trim(
                                $data['foto'] ?? ''
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

                            ?>

                            <?php if (
                                $namaFileFoto !== '' &&
                                file_exists($pathFoto)
                            ): ?>

                                <img
                                    src="<?= e($urlFoto) ?>"
                                    class="product-image"
                                    alt="<?= e($data['nama_produk']) ?>"
                                >

                            <?php else: ?>

                                <div
                                    class="product-image d-flex align-items-center justify-content-center"
                                >

                                    <i
                                        class="fa-solid fa-image text-secondary fs-2"
                                    ></i>

                                </div>

                            <?php endif; ?>


                            <div>

                                <h5 class="fw-bold mb-1">

                                    <?= e($data['nama_produk']) ?>

                                </h5>

                                <div class="text-muted">

                                    Pesanan:

                                    <strong>
                                        <?= e($data['kode_pesanan']) ?>
                                    </strong>

                                </div>

                                <div class="text-muted">

                                    Jumlah:

                                    <?= number_format(
                                        (float)$data['jumlah'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                    <?= e($data['satuan']) ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- FORM -->

                    <form
                        action="proses_ulasan.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="pesanan_id"
                            value="<?= $pesanan_id ?>"
                        >

                        <input
                            type="hidden"
                            name="produk_id"
                            value="<?= $produk_id ?>"
                        >


                        <!-- RATING -->

                        <div class="mb-4">

                            <label class="form-label fw-bold">

                                Rating Produk
                                <span class="text-danger">*</span>

                            </label>


                            <div class="rating">

                                <input
                                    type="radio"
                                    id="star5"
                                    name="rating"
                                    value="5"
                                    <?= (
                                        $ulasan_lama &&
                                        $ulasan_lama['rating'] == 5
                                    )
                                        ? 'checked'
                                        : '' ?>
                                    required
                                >

                                <label
                                    for="star5"
                                    title="Sangat Baik"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star4"
                                    name="rating"
                                    value="4"
                                    <?= (
                                        $ulasan_lama &&
                                        $ulasan_lama['rating'] == 4
                                    )
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    for="star4"
                                    title="Baik"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star3"
                                    name="rating"
                                    value="3"
                                    <?= (
                                        $ulasan_lama &&
                                        $ulasan_lama['rating'] == 3
                                    )
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    for="star3"
                                    title="Cukup"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star2"
                                    name="rating"
                                    value="2"
                                    <?= (
                                        $ulasan_lama &&
                                        $ulasan_lama['rating'] == 2
                                    )
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    for="star2"
                                    title="Kurang"
                                >
                                    ★
                                </label>


                                <input
                                    type="radio"
                                    id="star1"
                                    name="rating"
                                    value="1"
                                    <?= (
                                        $ulasan_lama &&
                                        $ulasan_lama['rating'] == 1
                                    )
                                        ? 'checked'
                                        : '' ?>
                                >

                                <label
                                    for="star1"
                                    title="Sangat Kurang"
                                >
                                    ★
                                </label>

                            </div>

                            <div class="rating-text">

                                Pilih rating dari 1 sampai 5 bintang.

                            </div>

                        </div>


                        <!-- ULASAN -->

                        <div class="mb-4">

                            <label class="form-label fw-bold">

                                Ulasan

                            </label>

                            <textarea
                                name="ulasan"
                                class="form-control"
                                rows="5"
                                maxlength="1000"
                                placeholder="Bagaimana pengalaman Anda dengan produk ini?"
                            ><?= e(
                                $ulasan_lama['ulasan'] ?? ''
                            ) ?></textarea>

                            <div class="form-text">

                                Maksimal 1000 karakter.

                            </div>

                        </div>


                        <!-- BUTTON -->

                        <div class="d-flex justify-content-end gap-2">

                            <a
                                href="detail_pesanan.php?id=<?= $pesanan_id ?>"
                                class="btn btn-secondary"
                            >

                                Batal

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fa-solid fa-paper-plane"></i>

                                <?= $ulasan_lama
                                    ? 'Perbarui Ulasan'
                                    : 'Kirim Ulasan' ?>

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>