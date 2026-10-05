<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$user_id = userId();

/*
|--------------------------------------------------------------------------
| Tandai semua sebagai sudah dibaca
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tandai_semua'])) {

    $stmt = $conn->prepare("
        UPDATE notifications
        SET dibaca = 1
        WHERE user_id = ?
          AND dibaca = 0
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notifikasi.php");
    exit;
}

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

$result = $stmt->get_result();

$notifikasi = [];

while ($row = $result->fetch_assoc()) {
    $notifikasi[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Hitung belum dibaca
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

$unread = $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();

function iconNotifikasi($tipe)
{
    switch ($tipe) {
        case 'pesanan':
            return 'fa-shopping-cart';

        case 'status':
            return 'fa-truck';

        case 'success':
            return 'fa-check-circle';

        case 'warning':
            return 'fa-exclamation-triangle';

        default:
            return 'fa-bell';
    }
}

function badgeNotifikasi($tipe)
{
    switch ($tipe) {
        case 'pesanan':
            return 'primary';

        case 'status':
            return 'info';

        case 'success':
            return 'success';

        case 'warning':
            return 'warning';

        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifikasi - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .notification-card {
            border: 0;
            border-radius: 14px;
            transition: .2s;
        }

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .notification-unread {
            background: #eef6ff;
            border-left: 4px solid #0d6efd;
        }

        .notification-read {
            background: #ffffff;
        }

        .icon-notification {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .empty-notification {
            padding: 70px 20px;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                <i class="fa-solid fa-bell text-primary"></i>
                Notifikasi
            </h3>

            <p class="text-muted mb-0">
                Informasi terbaru mengenai pesanan Anda.
            </p>
        </div>

        <?php if ($unread > 0): ?>

            <form method="POST">

                <button
                    type="submit"
                    name="tandai_semua"
                    class="btn btn-outline-primary">

                    <i class="fa-solid fa-check-double"></i>
                    Tandai Semua Dibaca

                </button>

            </form>

        <?php endif; ?>

    </div>


    <?php if ($unread > 0): ?>

        <div class="alert alert-primary">
            <i class="fa-solid fa-bell"></i>

            Anda memiliki
            <strong><?= (int)$unread ?></strong>
            notifikasi yang belum dibaca.
        </div>

    <?php endif; ?>


    <?php if (empty($notifikasi)): ?>

        <div class="card notification-card">

            <div class="empty-notification">

                <i
                    class="fa-regular fa-bell-slash fa-4x text-muted mb-3">
                </i>

                <h5>Belum Ada Notifikasi</h5>

                <p class="text-muted">
                    Notifikasi pesanan Anda akan muncul di halaman ini.
                </p>

                <a
                    href="index.php"
                    class="btn btn-primary">

                    <i class="fa-solid fa-store"></i>
                    Belanja Sekarang

                </a>

            </div>

        </div>

    <?php else: ?>


        <?php foreach ($notifikasi as $item): ?>

            <?php

            $badge = badgeNotifikasi($item['tipe']);

            $icon = iconNotifikasi($item['tipe']);

            $class = $item['dibaca']
                ? 'notification-read'
                : 'notification-unread';

            ?>

            <div class="card notification-card <?= $class ?> mb-3">

                <div class="card-body">

                    <div class="d-flex gap-3">

                        <div
                            class="icon-notification bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?>">

                            <i class="fa-solid <?= $icon ?>"></i>

                        </div>


                        <div class="flex-grow-1">

                            <div class="d-flex justify-content-between">

                                <h6 class="fw-bold mb-1">

                                    <?= e($item['judul']) ?>

                                    <?php if (!$item['dibaca']): ?>

                                        <span class="badge bg-primary ms-2">
                                            Baru
                                        </span>

                                    <?php endif; ?>

                                </h6>

                                <small class="text-muted">
                                    <?= date(
                                        'd M Y H:i',
                                        strtotime($item['created_at'])
                                    ) ?>
                                </small>

                            </div>


                            <p class="text-muted mb-2">

                                <?= nl2br(
                                    e($item['pesan'])
                                ) ?>

                            </p>


                            <?php if (!empty($item['link'])): ?>

                                <a
                                    href="<?= e($item['link']) ?>"
                                    class="btn btn-sm btn-outline-primary">

                                    Lihat Detail
                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>

</div>

</body>
</html>