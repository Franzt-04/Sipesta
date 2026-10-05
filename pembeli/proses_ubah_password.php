<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajibRole("pembeli");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ubah_password.php");
    exit;

}

$user_id = userId();

$password_lama = $_POST['password_lama'] ?? '';
$password_baru = $_POST['password_baru'] ?? '';
$konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

/*
|--------------------------------------------------------------------------
| VALIDASI
|--------------------------------------------------------------------------
*/

if (
    $password_lama === '' ||
    $password_baru === '' ||
    $konfirmasi_password === ''
) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Semua password wajib diisi.")
    );

    exit;

}


if (strlen($password_baru) < 6) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Password baru minimal 6 karakter.")
    );

    exit;

}


if ($password_baru !== $konfirmasi_password) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Konfirmasi password baru tidak sesuai.")
    );

    exit;

}


if ($password_lama === $password_baru) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Password baru harus berbeda dari password lama.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| AMBIL PASSWORD DARI DATABASE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT password
    FROM users
    WHERE id = ?
      AND role = 'pembeli'
    LIMIT 1
");

if (!$stmt) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Gagal memproses data akun.")
    );

    exit;

}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Akun pembeli tidak ditemukan.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| CEK PASSWORD LAMA
|--------------------------------------------------------------------------
*/

if (!password_verify($password_lama, $user['password'])) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Password lama salah.")
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| HASH PASSWORD BARU
|--------------------------------------------------------------------------
*/

$password_hash = password_hash(
    $password_baru,
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| UPDATE PASSWORD
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE users
    SET password = ?
    WHERE id = ?
      AND role = 'pembeli'
");

if (!$stmt) {

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Gagal menyiapkan perubahan password.")
    );

    exit;

}

$stmt->bind_param(
    "si",
    $password_hash,
    $user_id
);


if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    header(
        "Location: ubah_password.php?error=" .
        urlencode("Gagal mengubah password: " . $error)
    );

    exit;

}

$stmt->close();


/*
|--------------------------------------------------------------------------
| SELESAI
|--------------------------------------------------------------------------
*/

header(
    "Location: ubah_password.php?success=" .
    urlencode("Password berhasil diubah.")
);

exit;