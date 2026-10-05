    <?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");


// ======================================================
// HANYA POST
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: penjual.php?error=" .
        urlencode("Metode permintaan tidak valid.")
    );

    exit;
}


// ======================================================
// VALIDASI ID PENJUAL
// ======================================================

$penjual_id = (int)($_POST['id'] ?? 0);
$status = strtolower(trim($_POST['status'] ?? ''));

if ($penjual_id <= 0) {

    header(
        "Location: penjual.php?error=" .
        urlencode("ID penjual tidak valid.")
    );

    exit;
}


// ======================================================
// VALIDASI STATUS
// ======================================================

$statusDiizinkan = [
    'aktif',
    'nonaktif'
];

if (!in_array($status, $statusDiizinkan, true)) {

    header(
        "Location: penjual.php?error=" .
        urlencode("Status tidak valid.")
    );

    exit;
}


// ======================================================
// AMBIL USER ID PENJUAL
// ======================================================

$stmt = $conn->prepare("
    SELECT
        p.user_id,
        p.nama_usaha
    FROM penjual p
    WHERE p.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();
$penjual = $result->fetch_assoc();

$stmt->close();


if (!$penjual) {

    header(
        "Location: penjual.php?error=" .
        urlencode("Penjual tidak ditemukan.")
    );

    exit;
}


$user_id = (int)$penjual['user_id'];


// ======================================================
// UPDATE STATUS
// ======================================================

$stmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ?
      AND role = 'penjual'
");

$stmt->bind_param(
    "si",
    $status,
    $user_id
);


if (!$stmt->execute()) {

    $stmt->close();

    header(
        "Location: penjual.php?error=" .
        urlencode("Gagal mengubah status penjual.")
    );

    exit;
}

$stmt->close();


// ======================================================
// PESAN BERHASIL
// ======================================================

if ($status === 'aktif') {

    $pesan =
        'Penjual "' .
        $penjual['nama_usaha'] .
        '" berhasil diaktifkan.';

} else {

    $pesan =
        'Penjual "' .
        $penjual['nama_usaha'] .
        '" berhasil dinonaktifkan.';
}


header(
    "Location: penjual.php?success=" .
    urlencode($pesan)
);

exit;