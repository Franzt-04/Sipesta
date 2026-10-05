<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole('pembeli');


/*
|--------------------------------------------------------------------------
| USER YANG LOGIN
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

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$pembeli = $result->fetch_assoc();

$stmt->close();


if (!$pembeli) {

    header(
        "Location: index.php?error=" .
        urlencode("Data pembeli tidak ditemukan.")
    );

    exit;
}


$pembeli_id = (int)$pembeli['id'];


/*
|--------------------------------------------------------------------------
| DATA PRODUK
|--------------------------------------------------------------------------
*/

$produk_id = (int)($_POST['produk_id'] ?? 0);

$jumlah = (float)($_POST['jumlah'] ?? 0);


if ($produk_id <= 0 || $jumlah <= 0) {

    header(
        "Location: index.php?error=" .
        urlencode("Produk atau jumlah tidak valid.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK PRODUK
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        nama_produk,
        harga,
        stok,
        status
    FROM produk
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $produk_id
);

$stmt->execute();

$result = $stmt->get_result();

$produk = $result->fetch_assoc();

$stmt->close();


if (!$produk) {

    header(
        "Location: index.php?error=" .
        urlencode("Produk tidak ditemukan.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK STATUS
|--------------------------------------------------------------------------
*/

if ($produk['status'] !== 'tersedia') {

    header(
        "Location: detail_produk.php?id=" .
        $produk_id .
        "&error=" .
        urlencode("Produk tidak tersedia.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK STOK
|--------------------------------------------------------------------------
*/

if ($jumlah > (float)$produk['stok']) {

    header(
        "Location: detail_produk.php?id=" .
        $produk_id .
        "&error=" .
        urlencode("Jumlah melebihi stok.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CARI KERANJANG PEMBELI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM keranjang
    WHERE pembeli_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $pembeli_id
);

$stmt->execute();

$result = $stmt->get_result();

$keranjang = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| BUAT KERANJANG JIKA BELUM ADA
|--------------------------------------------------------------------------
*/

if ($keranjang) {

    $keranjang_id = (int)$keranjang['id'];

} else {

    $stmt = $conn->prepare("
        INSERT INTO keranjang
        (pembeli_id)
        VALUES (?)
    ");

    $stmt->bind_param(
        "i",
        $pembeli_id
    );

    $stmt->execute();

    $keranjang_id = $stmt->insert_id;

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| CEK PRODUK SUDAH ADA
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        jumlah
    FROM keranjang_detail
    WHERE keranjang_id = ?
      AND produk_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $keranjang_id,
    $produk_id
);

$stmt->execute();

$result = $stmt->get_result();

$detail = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| PRODUK SUDAH ADA → UPDATE
|--------------------------------------------------------------------------
*/

if ($detail) {

    $jumlah_baru =
        (float)$detail['jumlah'] + $jumlah;


    if ($jumlah_baru > (float)$produk['stok']) {

        header(
            "Location: detail_produk.php?id=" .
            $produk_id .
            "&error=" .
            urlencode("Jumlah melebihi stok yang tersedia.")
        );

        exit;
    }


    $harga = (float)$produk['harga'];

    $subtotal = $jumlah_baru * $harga;

    $detail_id = (int)$detail['id'];


    $stmt = $conn->prepare("
        UPDATE keranjang_detail
        SET
            jumlah = ?,
            harga = ?,
            subtotal = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "dddi",
        $jumlah_baru,
        $harga,
        $subtotal,
        $detail_id
    );

    $stmt->execute();

    $stmt->close();


} else {


    /*
    |--------------------------------------------------------------------------
    | PRODUK BELUM ADA → INSERT
    |--------------------------------------------------------------------------
    */

    $harga = (float)$produk['harga'];

    $subtotal = $jumlah * $harga;


    $stmt = $conn->prepare("
        INSERT INTO keranjang_detail
        (
            keranjang_id,
            produk_id,
            jumlah,
            harga,
            subtotal
        )
        VALUES
        (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiddd",
        $keranjang_id,
        $produk_id,
        $jumlah,
        $harga,
        $subtotal
    );

    $stmt->execute();

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| SELESAI
|--------------------------------------------------------------------------
*/

header(
    "Location: keranjang.php?success=" .
    urlencode("Produk berhasil ditambahkan ke keranjang.")
);

exit;