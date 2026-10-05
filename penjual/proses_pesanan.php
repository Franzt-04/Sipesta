<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";
require_once "../config/notifikasi.php";

wajibRole("penjual");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HANYA POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA
|--------------------------------------------------------------------------
*/

$pesanan_id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

$aksi = isset($_POST['aksi'])
    ? trim($_POST['aksi'])
    : '';

if ($pesanan_id <= 0) {
    header("Location: pesanan.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDASI AKSI
|--------------------------------------------------------------------------
*/

$daftarAksi = [
    'proses',
    'kirim',
    'selesai'
];

if (!in_array($aksi, $daftarAksi, true)) {

    header(
        "Location: detail_pesanan.php?id="
        . $pesanan_id
        . "&error="
        . urlencode("Tindakan pesanan tidak valid.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PENJUAL
|--------------------------------------------------------------------------
*/

$stmtPenjual = $conn->prepare("
    SELECT id
    FROM penjual
    WHERE user_id = ?
    LIMIT 1
");

$stmtPenjual->bind_param(
    "i",
    $user_id
);

$stmtPenjual->execute();

$penjual = $stmtPenjual
    ->get_result()
    ->fetch_assoc();

if (!$penjual) {

    header(
        "Location: pesanan.php?error="
        . urlencode("Data penjual tidak ditemukan.")
    );

    exit;
}

$penjual_id = (int) $penjual['id'];


/*
|--------------------------------------------------------------------------
| TENTUKAN STATUS BARU
|--------------------------------------------------------------------------
*/

$statusBaru = '';

if ($aksi === 'proses') {
    $statusBaru = 'diproses';
}

if ($aksi === 'kirim') {
    $statusBaru = 'dikirim';
}

if ($aksi === 'selesai') {
    $statusBaru = 'selesai';
}


/*
|--------------------------------------------------------------------------
| TRANSAKSI
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {


    /*
    |--------------------------------------------------------------------------
    | CEK PESANAN MILIK PENJUAL
    |--------------------------------------------------------------------------
    */

    $stmtPesanan = $conn->prepare("
        SELECT
            p.id,
            p.status

        FROM pesanan p

        INNER JOIN detail_pesanan dp
            ON p.id = dp.pesanan_id

        INNER JOIN produk pr
            ON dp.produk_id = pr.id

        WHERE p.id = ?
          AND pr.penjual_id = ?

        LIMIT 1

        FOR UPDATE
    ");

    $stmtPesanan->bind_param(
        "ii",
        $pesanan_id,
        $penjual_id
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
    | CEK PERPINDAHAN STATUS
    |--------------------------------------------------------------------------
    */

    $statusSekarang = strtolower(
        trim($pesanan['status'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | MENUNGGU → DIPROSES
    |--------------------------------------------------------------------------
    */

    if (
        $aksi === 'proses' &&
        $statusSekarang !== 'menunggu'
    ) {

        throw new Exception(
            "Pesanan hanya dapat diproses jika statusnya Menunggu."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DIPROSES → DIKIRIM
    |--------------------------------------------------------------------------
    */

    if (
        $aksi === 'kirim' &&
        $statusSekarang !== 'diproses'
    ) {

        throw new Exception(
            "Pesanan hanya dapat dikirim jika statusnya Diproses."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DIKIRIM → SELESAI
    |--------------------------------------------------------------------------
    */

    if (
        $aksi === 'selesai' &&
        $statusSekarang !== 'dikirim'
    ) {

        throw new Exception(
            "Pesanan hanya dapat diselesaikan jika statusnya Dikirim."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PESANAN DIBATALKAN
    |--------------------------------------------------------------------------
    */

    if ($statusSekarang === 'dibatalkan') {

        throw new Exception(
            "Pesanan yang sudah dibatalkan tidak dapat diproses."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */

    $stmtUpdate = $conn->prepare("
        UPDATE pesanan
        SET status = ?
        WHERE id = ?
    ");

    $stmtUpdate->bind_param(
        "si",
        $statusBaru,
        $pesanan_id
    );

    if (!$stmtUpdate->execute()) {

        throw new Exception(
            "Gagal memperbarui status pesanan."
        );
    }


    if ($stmtUpdate->affected_rows !== 1) {

        throw new Exception(
            "Status pesanan tidak berubah."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | PESAN SUKSES
    |--------------------------------------------------------------------------
    */

    $pesanSukses = '';

    if ($statusBaru === 'diproses') {

        $pesanSukses =
            'Pesanan berhasil diproses.';

    } elseif ($statusBaru === 'dikirim') {

        $pesanSukses =
            'Pesanan berhasil ditandai sebagai dikirim.';

    } elseif ($statusBaru === 'selesai') {

        $pesanSukses =
            'Pesanan berhasil diselesaikan.';
    }


    header(
        "Location: detail_pesanan.php?id="
        . $pesanan_id
        . "&success="
        . urlencode($pesanSukses)
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