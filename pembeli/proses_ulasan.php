<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajibRole("pembeli");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: riwayat_pesanan.php");
    exit;
}

$user_id = userId();

$pesanan_id = (int)($_POST['pesanan_id'] ?? 0);
$produk_id  = (int)($_POST['produk_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$ulasan     = trim($_POST['ulasan'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDASI DASAR
|--------------------------------------------------------------------------
*/

if ($pesanan_id <= 0 || $produk_id <= 0) {

    header(
        "Location: riwayat_pesanan.php?error=" .
        urlencode("Data ulasan tidak valid.")
    );

    exit;
}


if ($rating < 1 || $rating > 5) {

    header(
        "Location: ulasan.php?pesanan_id=" .
        $pesanan_id .
        "&produk_id=" .
        $produk_id .
        "&error=" .
        urlencode("Rating harus antara 1 sampai 5.")
    );

    exit;
}


if (strlen($ulasan) > 1000) {

    header(
        "Location: ulasan.php?pesanan_id=" .
        $pesanan_id .
        "&produk_id=" .
        $produk_id .
        "&error=" .
        urlencode("Ulasan maksimal 1000 karakter.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL ID PEMBELI
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

    header(
        "Location: riwayat_pesanan.php?error=" .
        urlencode("Data pembeli tidak ditemukan.")
    );

    exit;
}

$pembeli_id = (int)$pembeli['id'];


/*
|--------------------------------------------------------------------------
| VERIFIKASI PESANAN
|--------------------------------------------------------------------------
|
| Syarat:
| - milik pembeli
| - status selesai
| - produk ada dalam pesanan
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        ps.id,
        ps.kode_pesanan,
        ps.status,
        dp.produk_id,
        p.nama_produk

    FROM pesanan ps

    INNER JOIN detail_pesanan dp
        ON dp.pesanan_id = ps.id

    INNER JOIN produk p
        ON p.id = dp.produk_id

    WHERE ps.id = ?
      AND ps.pembeli_id = ?
      AND dp.produk_id = ?
      AND ps.status = 'selesai'

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

    header(
        "Location: riwayat_pesanan.php?error=" .
        urlencode(
            "Produk tidak dapat diulas. Pastikan pesanan sudah selesai."
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK APAKAH SUDAH ADA ULASAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
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


/*
|--------------------------------------------------------------------------
| UPDATE JIKA SUDAH ADA
|--------------------------------------------------------------------------
*/

if ($ulasan_lama) {

    $ulasan_id = (int)$ulasan_lama['id'];

    $stmt = $conn->prepare("
        UPDATE ulasan
        SET
            rating = ?,
            ulasan = ?
        WHERE id = ?
          AND pembeli_id = ?
    ");

    $stmt->bind_param(
        "isii",
        $rating,
        $ulasan,
        $ulasan_id,
        $pembeli_id
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        header(
            "Location: ulasan.php?pesanan_id=" .
            $pesanan_id .
            "&produk_id=" .
            $produk_id .
            "&error=" .
            urlencode("Gagal memperbarui ulasan: " . $error)
        );

        exit;
    }

    $stmt->close();

    header(
        "Location: detail_pesanan.php?id=" .
        $pesanan_id .
        "&success=" .
        urlencode("Ulasan berhasil diperbarui.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| INSERT ULASAN BARU
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO ulasan
    (
        pesanan_id,
        produk_id,
        pembeli_id,
        rating,
        ulasan
    )
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiiis",
    $pesanan_id,
    $produk_id,
    $pembeli_id,
    $rating,
    $ulasan
);


if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    header(
        "Location: ulasan.php?pesanan_id=" .
        $pesanan_id .
        "&produk_id=" .
        $produk_id .
        "&error=" .
        urlencode("Gagal menyimpan ulasan: " . $error)
    );

    exit;
}

$stmt->close();


header(
    "Location: detail_pesanan.php?id=" .
    $pesanan_id .
    "&success=" .
    urlencode("Ulasan berhasil dikirim.")
);

exit;