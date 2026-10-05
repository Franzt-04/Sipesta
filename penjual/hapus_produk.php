<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("penjual");

// ======================================================
// VALIDASI ID PRODUK
// ======================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php?error=ID produk tidak valid.");
    exit;
}

$produk_id = (int) $_GET['id'];

if ($produk_id <= 0) {
    header("Location: produk.php?error=ID produk tidak valid.");
    exit;
}

// ======================================================
// AMBIL ID PENJUAL DARI USER LOGIN
// ======================================================

$user_id = userId();

$stmt = $conn->prepare("
    SELECT id, nama_usaha
    FROM penjual
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$penjual = $result->fetch_assoc();
$stmt->close();

if (!$penjual) {
    header("Location: ../index.php?error=Data penjual tidak ditemukan.");
    exit;
}

$penjual_id = (int) $penjual['id'];

// ======================================================
// AMBIL DATA PRODUK DAN PASTIKAN MILIK PENJUAL
// ======================================================

$stmt = $conn->prepare("
    SELECT
        id,
        nama_produk,
        foto,
        status
    FROM produk
    WHERE id = ?
      AND penjual_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $produk_id, $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    header("Location: produk.php?error=Produk tidak ditemukan atau bukan milik Anda.");
    exit;
}

// ======================================================
// MULAI TRANSAKSI
// ======================================================

$conn->begin_transaction();

try {

    // ==================================================
    // CEK APAKAH PRODUK PERNAH DIGUNAKAN DALAM PESANAN
    // ==================================================

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS jumlah
        FROM detail_pesanan
        WHERE produk_id = ?
    ");

    $stmt->bind_param("i", $produk_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $dataPesanan = $result->fetch_assoc();

    $stmt->close();

    $pernah_dipesan = (int) $dataPesanan['jumlah'] > 0;


    // ==================================================
    // CEK APAKAH PRODUK MASIH ADA DI KERANJANG
    // ==================================================

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS jumlah
        FROM keranjang_detail
        WHERE produk_id = ?
    ");

    $stmt->bind_param("i", $produk_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $dataKeranjang = $result->fetch_assoc();

    $stmt->close();

    $ada_di_keranjang = (int) $dataKeranjang['jumlah'] > 0;


    // ==================================================
    // JIKA PERNAH DIGUNAKAN DALAM TRANSAKSI
    // JANGAN HAPUS PERMANEN
    // ==================================================

    if ($pernah_dipesan || $ada_di_keranjang) {

        $stmt = $conn->prepare("
            UPDATE produk
            SET
                status = 'nonaktif',
                updated_at = NOW()
            WHERE id = ?
              AND penjual_id = ?
        ");

        $stmt->bind_param("ii", $produk_id, $penjual_id);

        if (!$stmt->execute()) {
            throw new Exception("Gagal menonaktifkan produk.");
        }

        $stmt->close();

        $conn->commit();

        header(
            "Location: produk.php?success=" .
            urlencode(
                "Produk \"" . $produk['nama_produk'] .
                "\" tidak dihapus permanen karena sudah digunakan. Produk telah dinonaktifkan."
            )
        );
        exit;
    }


    // ==================================================
    // PRODUK BELUM PERNAH DIGUNAKAN
    // BOLEH DIHAPUS PERMANEN
    // ==================================================

    $stmt = $conn->prepare("
        DELETE FROM produk
        WHERE id = ?
          AND penjual_id = ?
    ");

    $stmt->bind_param("ii", $produk_id, $penjual_id);

    if (!$stmt->execute()) {
        throw new Exception("Gagal menghapus produk.");
    }

    if ($stmt->affected_rows <= 0) {
        throw new Exception("Produk gagal dihapus.");
    }

    $stmt->close();


    // ==================================================
    // HAPUS FOTO PRODUK JIKA ADA
    // ==================================================

    if (!empty($produk['foto'])) {

        $fileFoto = "../uploads/produk/" . basename($produk['foto']);

        if (file_exists($fileFoto)) {
            @unlink($fileFoto);
        }
    }


    // ==================================================
    // COMMIT
    // ==================================================

    $conn->commit();

    header(
        "Location: produk.php?success=" .
        urlencode(
            "Produk \"" . $produk['nama_produk'] . "\" berhasil dihapus."
        )
    );

    exit;


} catch (Throwable $e) {

    // ==================================================
    // ROLLBACK JIKA TERJADI ERROR
    // ==================================================

    $conn->rollback();

    header(
        "Location: produk.php?error=" .
        urlencode("Produk gagal dihapus: " . $e->getMessage())
    );

    exit;
}