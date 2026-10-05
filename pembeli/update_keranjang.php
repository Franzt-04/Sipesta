<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}

$pembeli_id = $_SESSION['pembeli_id'] ?? $_SESSION['user_id'] ?? null;

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$jumlah = isset($_POST['jumlah'])
    ? (float)$_POST['jumlah']
    : 0;

if (!$pembeli_id || $id <= 0 || $jumlah <= 0) {

    header("Location: keranjang.php?error=Data tidak valid");
    exit;
}


/*
|--------------------------------------------------------------------------
| Pastikan item milik pembeli
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        dk.id,
        dk.produk_id,
        p.stok,
        p.status
    FROM detail_keranjang dk

    INNER JOIN keranjang k
        ON k.id = dk.keranjang_id

    INNER JOIN produk p
        ON p.id = dk.produk_id

    WHERE dk.id = ?
      AND k.pembeli_id = ?

    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $id,
    $pembeli_id
);

$stmt->execute();

$result = $stmt->get_result();

$item = $result->fetch_assoc();

$stmt->close();


if (!$item) {

    header(
        "Location: keranjang.php?error=Item keranjang tidak ditemukan"
    );

    exit;
}


if ($item['status'] !== 'tersedia') {

    header(
        "Location: keranjang.php?error=Produk sudah tidak tersedia"
    );

    exit;
}


if ($jumlah > (float)$item['stok']) {

    header(
        "Location: keranjang.php?error=Jumlah melebihi stok"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Ambil harga terbaru
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT harga
    FROM produk
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $item['produk_id']
);

$stmt->execute();

$result = $stmt->get_result();

$produk = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

$harga = (float)$produk['harga'];

$stmt = $conn->prepare("
    UPDATE detail_keranjang
    SET
        jumlah = ?,
        harga = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ddi",
    $jumlah,
    $harga,
    $id
);

$stmt->execute();

$stmt->close();


header(
    "Location: keranjang.php?success=Keranjang berhasil diperbarui"
);

exit;