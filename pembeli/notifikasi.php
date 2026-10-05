<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";
require_once "../config/notifikasi.php";

wajibRole('pembeli');

$user_id = userId();


/*
|--------------------------------------------------------------------------
| TANDAI SEMUA SUDAH DIBACA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['tandai_semua'])
) {

    $stmt = $conn->prepare("
        UPDATE notifications
        SET dibaca = 1
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $stmt->close();

    header(
        "Location: notifikasi.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| TANDAI SATU NOTIFIKASI SUDAH DIBACA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['notification_id'])
) {

    $notification_id =
        (int)$_POST['notification_id'];

    $stmt = $conn->prepare("
        UPDATE notifications
        SET dibaca = 1
        WHERE id = ?
          AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $notification_id,
        $user_id
    );

    $stmt->execute();

    $stmt->close();

    header(
        "Location: notifikasi.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL NOTIFIKASI
|--------------------------------------------------------------------------
*/

$notifikasi = ambilNotifikasi(
    $conn,
    $user_id,
    50
);


/*
|--------------------------------------------------------------------------
| JUMLAH BELUM DIBACA
|--------------------------------------------------------------------------
*/

$totalBelumDibaca =
    jumlahNotifikasiBelumDibaca(
        $conn,
        $user_id
    );

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifikasi - SIPESTA</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f5f7fb;
        }

        .notification-card {
            border: none;
            border-radius: 16px;
            box-shadow:
                0 4px 18px rgba(0,0,0,.06);
        }

        .notification-item {
            border-bottom: 1px solid #eee;
            padding: 18px;
            transition: .2s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-unread {
            background: #eef8f1;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .empty-icon {
            font-size: 55px;
            color: #adb5bd;
        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold text-success"
        >

            <i class="fa-solid fa-store"></i>

            SIPESTA

        </a>


        <div class="d-flex align-items-center gap-2">

            <a
                href="index.php"
                class="btn btn-outline-success"
            >

                <i class="fa-solid fa-house"></i>

                Beranda

            </a>

        </div>

    </div>

</nav>



<div class="container py-4">


    <!-- HEADER -->

    <div class="d-flex
                justify-content-between
                align-items-center
                mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="fa-solid fa-bell text-success"></i>

                Notifikasi

            </h3>

            <p class="text-muted mb-0">

                Informasi terbaru mengenai pesanan Anda.

            </p>

        </div>


        <?php if ($totalBelumDibaca > 0): ?>

            <form
                method="POST"
                action="notifikasi.php"
            >

                <input
                    type="hidden"
                    name="tandai_semua"
                    value="1"
                >

                <button
                    type="submit"
                    class="btn btn-outline-success"
                >

                    <i class="fa-solid fa-check-double"></i>

                    Tandai Semua Dibaca

                </button>

            </form>

        <?php endif; ?>

    </div>



    <!-- JUMLAH BELUM DIBACA -->

    <?php if ($totalBelumDibaca > 0): ?>

        <div class="alert alert-success">

            <i class="fa-solid fa-circle-info"></i>

            Anda memiliki

            <strong>
                <?= $totalBelumDibaca ?>
            </strong>

            notifikasi yang belum dibaca.

        </div>

    <?php endif; ?>



    <!-- NOTIFIKASI -->

    <div class="card notification-card">

        <div class="card-body p-0">


            <?php if (empty($notifikasi)): ?>


                <div class="text-center py-5">

                    <i
                        class="fa-regular
                               fa-bell-slash
                               empty-icon"
                    ></i>

                    <h5 class="mt-3">

                        Belum Ada Notifikasi

                    </h5>

                    <p class="text-muted">

                        Notifikasi pesanan Anda akan
                        muncul di sini.

                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($notifikasi as $item): ?>


                    <?php

                    $isUnread =
                        (int)$item['dibaca'] === 0;

                    $icon =
                        'fa-bell';

                    $iconClass =
                        'bg-success-subtle text-success';

                    if (
                        $item['tipe'] === 'pesanan'
                    ) {

                        $icon =
                            'fa-box';

                    }

                    ?>


                    <div
                        class="
                            notification-item
                            <?= $isUnread
                                ? 'notification-unread'
                                : ''
                            ?>
                        "
                    >

                        <div class="d-flex gap-3">


                            <!-- ICON -->

                            <div
                                class="
                                    notification-icon
                                    <?= $iconClass ?>
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        <?= $icon ?>
                                    "
                                ></i>

                            </div>


                            <!-- CONTENT -->

                            <div class="flex-grow-1">


                                <div
                                    class="
                                        d-flex
                                        justify-content-between
                                        gap-2
                                    "
                                >

                                    <div>

                                        <h6
                                            class="fw-bold mb-1"
                                        >

                                            <?= e(
                                                $item['judul']
                                            ) ?>

                                            <?php if ($isUnread): ?>

                                                <span
                                                    class="
                                                        badge
                                                        bg-success
                                                        ms-1
                                                    "
                                                >
                                                    Baru
                                                </span>

                                            <?php endif; ?>

                                        </h6>


                                        <p class="mb-1">

                                            <?= nl2br(
                                                e(
                                                    $item['pesan']
                                                )
                                            ) ?>

                                        </p>


                                        <small
                                            class="text-muted"
                                        >

                                            <i
                                                class="
                                                    fa-regular
                                                    fa-clock
                                                "
                                            ></i>

                                            <?= e(
                                                date(
                                                    'd/m/Y H:i',
                                                    strtotime(
                                                        $item[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </small>

                                    </div>


                                    <!-- ACTION -->

                                    <div>

                                        <?php
                                        $link =
                                            trim(
                                                $item['link']
                                                ?? ''
                                            );
                                        ?>


                                        <?php if ($link !== ''): ?>

                                            <a
                                                href="<?= e($link) ?>"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-success
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-arrow-right
                                                    "
                                                ></i>

                                                Lihat

                                            </a>

                                        <?php endif; ?>


                                    </div>

                                </div>


                                <?php if ($isUnread): ?>


                                    <form
                                        method="POST"
                                        action="notifikasi.php"
                                        class="mt-2"
                                    >

                                        <input
                                            type="hidden"
                                            name="notification_id"
                                            value="<?= (int)$item['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-sm
                                                btn-light
                                            "
                                        >

                                            <i
                                                class="
                                                    fa-solid
                                                    fa-check
                                                "
                                            ></i>

                                            Tandai sudah dibaca

                                        </button>

                                    </form>


                                <?php endif; ?>


                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>