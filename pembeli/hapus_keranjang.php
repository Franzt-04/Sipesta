<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole('pembeli');

$pembeli_id = $_SESSION['pembeli_id'] ?? 0;

$id = (int)($_GET['id'] ?? 0);

if (!$pembeli_id || $id <= 0) {

    header("Location: keranjang.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HAPUS HANYA ITEM MILIK PEMBELI YANG LOGIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    DELETE kd

    FROM keranjang_detail kd

    INNER JOIN keranjang k
        ON kd.keranjang_id = k.id

    WHERE kd.id = ?
      AND k.pembeli_id = ?
");


$stmt->bind_param(
    "ii",
    $id,
    $pembeli_id
);


$stmt->execute();

$stmt->close();


header(
    "Location: keranjang.php?success=" .
    urlencode("Produk berhasil dihapus dari keranjang.")
);

exit;