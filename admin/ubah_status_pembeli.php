<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajibRole("admin");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pembeli.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0) {
    header("Location: pembeli.php?error=ID pembeli tidak valid");
    exit;
}

if (!in_array($status, ['aktif', 'nonaktif'], true)) {
    header("Location: pembeli.php?error=Status tidak valid");
    exit;
}

/*
 * Cari user_id berdasarkan pembeli.id
 */
$stmt = $conn->prepare("
    SELECT user_id
    FROM pembeli
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: pembeli.php?error=Data pembeli tidak ditemukan");
    exit;
}

$userId = (int)$data['user_id'];

/*
 * Update status akun
 */
$stmtUpdate = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ?
        AND role = 'pembeli'
");

$stmtUpdate->bind_param("si", $status, $userId);

if ($stmtUpdate->execute()) {

    header(
        "Location: pembeli.php?success=Status akun pembeli berhasil diubah"
    );

} else {

    header(
        "Location: pembeli.php?error=Gagal mengubah status akun pembeli"
    );
}

exit;