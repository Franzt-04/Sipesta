<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: riwayat_pesanan.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL ID PESANAN
|--------------------------------------------------------------------------
*/

$pesanan_id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

if ($pesanan_id <= 0) {
    header("Location: riwayat_pesanan.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBELI
|--------------------------------------------------------------------------
*/

$stmtPembeli = $conn->prepare("
    SELECT id
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmtPembeli->bind_param("i", $user_id);
$stmtPembeli->execute();

$pembeli = $stmtPembeli->get_result()->fetch_assoc();

if (!$pembeli) {
    die("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int) $pembeli['id'];

/*
|--------------------------------------------------------------------------
| MULAI TRANSAKSI
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | AMBIL PESANAN
    |--------------------------------------------------------------------------
    */

    $stmtPesanan = $conn->prepare("
        SELECT
            id,
            pembeli_id,
            status
        FROM pesanan
        WHERE id = ?
          AND pembeli_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmtPesanan->bind_param(
        "ii",
        $pesanan_id,
        $pembeli_id
    );

    $stmtPesanan->execute();

    $pesanan = $stmtPesanan
        ->get_result()
        ->fetch_assoc();

    if (!$pesanan) {
        throw new Exception(
            "Pesanan tidak ditemukan atau bukan milik Anda."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CEK STATUS
    |--------------------------------------------------------------------------
    */

    if ($pesanan['status'] !== 'menunggu') {

        throw new Exception(
            "Pesanan tidak dapat dibatalkan karena status pesanan sudah berubah menjadi "
            . $pesanan['status'] . "."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL DETAIL PESANAN
    |--------------------------------------------------------------------------
    */

    $stmtDetail = $conn->prepare("
        SELECT
            dp.produk_id,
            dp.jumlah
        FROM detail_pesanan dp
        WHERE dp.pesanan_id = ?
    ");

    $stmtDetail->bind_param(
        "i",
        $pesanan_id
    );

    $stmtDetail->execute();

    $detail = $stmtDetail->get_result();

    /*
    |--------------------------------------------------------------------------
    | KEMBALIKAN STOK
    |--------------------------------------------------------------------------
    */

    $stmtStok = $conn->prepare("
        UPDATE produk
        SET
            stok = stok + ?,
            status = 'tersedia'
        WHERE id = ?
    ");

    while ($item = $detail->fetch_assoc()) {

        $produk_id = (int) $item['produk_id'];

        $jumlah = (float) $item['jumlah'];

        if ($jumlah <= 0) {
            continue;
        }

        $stmtStok->bind_param(
            "di",
            $jumlah,
            $produk_id
        );

        if (!$stmtStok->execute()) {

            throw new Exception(
                "Gagal mengembalikan stok produk."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UBAH STATUS PESANAN
    |--------------------------------------------------------------------------
    */

    $stmtUpdate = $conn->prepare("
        UPDATE pesanan
        SET status = 'dibatalkan'
        WHERE id = ?
          AND pembeli_id = ?
          AND status = 'menunggu'
    ");

    $stmtUpdate->bind_param(
        "ii",
        $pesanan_id,
        $pembeli_id
    );

    $stmtUpdate->execute();

    if ($stmtUpdate->affected_rows !== 1) {

        throw new Exception(
            "Pesanan gagal dibatalkan."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    header(
        "Location: detail_pesanan.php?id="
        . $pesanan_id
        . "&success="
        . urlencode("Pesanan berhasil dibatalkan.")
    );

    exit;

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();

    header(
        "Location: detail_pesanan.php?id="
        . $pesanan_id
        . "&error="
        . urlencode($e->getMessage())
    );

    exit;
}