<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("penjual");

$user_id = userId();

/*
|--------------------------------------------------------------------------
| Tandai semua sudah dibaca
|--------------------------------------------------------------------------
*/

if (isset($_GET['baca_semua'])) {

    $stmt = $conn->prepare("
        UPDATE notifications
        SET dibaca = 1
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| Jumlah belum dibaca
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
    AND dibaca = 0
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$unread = (int)$result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| Ambil notifikasi
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
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
    LIMIT 50
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$notifications = $stmt->get_result();


function iconNotifPenjual($tipe)
{
    switch ($tipe) {

        case 'pesanan':
            return '🛒';

        case 'pengiriman':
            return '🚚';

        case 'sukses':
            return '✅';

        case 'peringatan':
            return '⚠️';

        default:
            return '🔔';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Notifikasi Penjual - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body {
            background: #f5f7fb;
        }

        .notification-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 12px;
        }

        .notification-unread {
            background: #eef5ff;
            border-left: 5px solid #0d6efd;
        }

        .notification-read {
            background: white;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold">

            SIPESTA

        </a>

        <div>

            <a
                href="index.php"
                class="btn btn-sm btn-outline-light">

                Dashboard

            </a>

            <a
                href="pesanan.php"
                class="btn btn-sm btn-outline-light">

                Pesanan

            </a>

            <a
                href="../logout.php"
                class="btn btn-sm btn-danger">

                Keluar

            </a>

        </div>

    </div>

</nav>


<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold">
                🔔 Notifikasi
            </h3>

            <p class="text-muted mb-0">
                Informasi pesanan dan aktivitas toko.
            </p>

        </div>


        <?php if ($unread > 0): ?>

            <a
                href="notifikasi.php?baca_semua=1"
                class="btn btn-outline-primary">

                Tandai Semua Dibaca

            </a>

        <?php endif; ?>

    </div>


    <?php if ($notifications->num_rows > 0): ?>

        <?php while ($notif = $notifications->fetch_assoc()): ?>

            <div class="
                card
                shadow-sm
                notification-card
                <?= $notif['dibaca']
                    ? 'notification-read'
                    : 'notification-unread'
                ?>
            ">

                <div class="card-body">

                    <div class="d-flex gap-3">

                        <div class="notification-icon">

                            <?= iconNotifPenjual(
                                $notif['tipe']
                            ) ?>

                        </div>


                        <div class="flex-grow-1">

                            <div class="d-flex justify-content-between">

                                <strong>

                                    <?= e($notif['judul']) ?>

                                    <?php if (!$notif['dibaca']): ?>

                                        <span class="badge bg-primary">
                                            Baru
                                        </span>

                                    <?php endif; ?>

                                </strong>


                                <small class="text-muted">

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $notif['created_at']
                                        )
                                    ) ?>

                                </small>

                            </div>


                            <p class="mt-2 mb-2">

                                <?= nl2br(
                                    e($notif['pesan'])
                                ) ?>

                            </p>


                            <?php if (!empty($notif['link'])): ?>

                                <a
                                    href="<?= e($notif['link']) ?>"
                                    class="btn btn-sm btn-outline-primary">

                                    Lihat Pesanan

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body text-center py-5">

                <div style="font-size:50px;">
                    🔔
                </div>

                <h5 class="mt-3">
                    Belum ada notifikasi
                </h5>

                <p class="text-muted">
                    Notifikasi pesanan baru akan muncul di sini.
                </p>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>
</html>