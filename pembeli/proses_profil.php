<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php");
    exit;
}

$user_id = userId();

$nama = trim($_POST['nama'] ?? '');
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$nama_toko = trim($_POST['nama_toko'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$lama_usaha = trim($_POST['lama_usaha'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDASI
|--------------------------------------------------------------------------
*/

if ($nama === '') {
    header("Location: profil.php?error=" . urlencode("Nama lengkap wajib diisi."));
    exit;
}

if ($tanggal_lahir === '') {
    header("Location: profil.php?error=" . urlencode("Tanggal lahir wajib diisi."));
    exit;
}

if ($no_hp === '') {
    header("Location: profil.php?error=" . urlencode("Nomor HP wajib diisi."));
    exit;
}

if ($alamat === '') {
    header("Location: profil.php?error=" . urlencode("Alamat wajib diisi."));
    exit;
}

if ($jenis_kelamin === '') {
    header("Location: profil.php?error=" . urlencode("Jenis kelamin wajib dipilih."));
    exit;
}

if (
    !in_array(
        $jenis_kelamin,
        ['Laki-laki', 'Perempuan'],
        true
    )
) {
    header("Location: profil.php?error=" . urlencode("Jenis kelamin tidak valid."));
    exit;
}

if ($lama_usaha === '') {
    $lama_usaha = 0;
}

$lama_usaha = (int)$lama_usaha;

if ($lama_usaha < 0) {
    $lama_usaha = 0;
}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE pembeli
    SET
        nama = ?,
        tanggal_lahir = ?,
        no_hp = ?,
        alamat = ?,
        nama_toko = ?,
        jenis_kelamin = ?,
        lama_usaha = ?
    WHERE user_id = ?
");

if (!$stmt) {
    header(
        "Location: profil.php?error=" .
        urlencode("Query gagal diproses.")
    );
    exit;
}

$stmt->bind_param(
    "ssssssii",
    $nama,
    $tanggal_lahir,
    $no_hp,
    $alamat,
    $nama_toko,
    $jenis_kelamin,
    $lama_usaha,
    $user_id
);

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: profil.php?success=" .
        urlencode("Profil berhasil diperbarui.")
    );

    exit;

}

$error = $stmt->error;

$stmt->close();

header(
    "Location: profil.php?error=" .
    urlencode("Gagal memperbarui profil: " . $error)
);

exit;