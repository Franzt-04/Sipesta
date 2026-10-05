<?php

/*
|--------------------------------------------------------------------------
| TAMBAH NOTIFIKASI
|--------------------------------------------------------------------------
*/

function tambahNotifikasi(
    $conn,
    $user_id,
    $judul,
    $pesan,
    $tipe = 'umum',
    $link = null
) {

    $stmt = $conn->prepare("
        INSERT INTO notifications
        (
            user_id,
            judul,
            pesan,
            tipe,
            link
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "issss",
        $user_id,
        $judul,
        $pesan,
        $tipe,
        $link
    );

    $hasil = $stmt->execute();

    $stmt->close();

    return $hasil;
}


/*
|--------------------------------------------------------------------------
| JUMLAH NOTIFIKASI BELUM DIBACA
|--------------------------------------------------------------------------
*/

function jumlahNotifikasiBelumDibaca(
    $conn,
    $user_id
) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ?
          AND dibaca = 0
    ");

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = $result->fetch_assoc();

    $stmt->close();

    return (int)($data['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| AMBIL NOTIFIKASI TERBARU
|--------------------------------------------------------------------------
*/

function ambilNotifikasi(
    $conn,
    $user_id,
    $limit = 10
) {

    $limit = max(
        1,
        min(50, (int)$limit)
    );

    $sql = "
        SELECT
            id,
            judul,
            pesan,
            tipe,
            link,
            dibaca,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT {$limit}
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    return $data;
}