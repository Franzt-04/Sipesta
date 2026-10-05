<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");

// ======================================================
// SEARCH
// ======================================================

$keyword = trim($_GET['keyword'] ?? '');


// ======================================================
// QUERY DATA PENJUAL
// ======================================================

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.nama_usaha,
        p.alamat,

        u.username,
        u.role,
        u.status,

        COUNT(pr.id) AS jumlah_produk

    FROM penjual p

    INNER JOIN users u
        ON p.user_id = u.id

    LEFT JOIN produk pr
        ON pr.penjual_id = p.id
";


// ======================================================
// FILTER SEARCH
// ======================================================

if ($keyword !== '') {

    $sql .= "
        WHERE
            p.nama_usaha LIKE ?
            OR p.alamat LIKE ?
            OR u.username LIKE ?
    ";
}


// ======================================================
// GROUP BY
// ======================================================

$sql .= "
    GROUP BY
        p.id,
        p.user_id,
        p.nama_usaha,
        p.alamat,
        u.username,
        u.role,
        u.status

    ORDER BY p.id DESC
";


// ======================================================
// PREPARE QUERY
// ======================================================

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Query penjual gagal dipersiapkan: " .
        htmlspecialchars($conn->error)
    );
}


// ======================================================
// BIND SEARCH
// ======================================================

if ($keyword !== '') {

    $search = "%" . $keyword . "%";

    $stmt->bind_param(
        "sss",
        $search,
        $search,
        $search
    );
}


// ======================================================
// EXECUTE
// ======================================================

if (!$stmt->execute()) {
    die(
        "Query penjual gagal dijalankan: " .
        htmlspecialchars($stmt->error)
    );
}

$penjualList = $stmt->get_result();


// ======================================================
// TOTAL PENJUAL
// ======================================================

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM penjual
");

$totalPenjual = 0;

if ($result) {
    $totalPenjual = (int)(
        $result->fetch_assoc()['total'] ?? 0
    );
}


// ======================================================
// TOTAL PRODUK
// ======================================================

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM produk
");

$totalProduk = 0;

if ($result) {
    $totalProduk = (int)(
        $result->fetch_assoc()['total'] ?? 0
    );
}


// ======================================================
// FUNGSI BADGE STATUS AKUN
// ======================================================

function badgeStatusAkunPenjual($status)
{
    $status = strtolower(trim((string)$status));

    if ($status === 'aktif') {

        return '
            <span class="badge bg-success">
                <i class="bi bi-check-circle"></i>
                Aktif
            </span>
        ';

    } elseif ($status === 'nonaktif') {

        return '
            <span class="badge bg-danger">
                <i class="bi bi-x-circle"></i>
                Nonaktif
            </span>
        ';

    }

    return '
        <span class="badge bg-secondary">
            <i class="bi bi-question-circle"></i>
            Tidak diketahui
        </span>
    ';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Data Penjual - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: .5px;
        }

        .dashboard-card {
            border: 0;
            border-radius: 16px;
        }

        .table-card {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .table > :not(caption) > * > * {
            vertical-align: middle;
        }

    </style>

</head>

<body>


<!-- ==================================================
     NAVBAR
================================================== -->

<nav class="navbar navbar-dark bg-success shadow-sm">

    <div class="container-fluid">

        <a
            class="navbar-brand"
            href="index.php"
        >

            <i class="bi bi-shop"></i>
            SIPESTA Admin

        </a>


        <div class="d-flex align-items-center">

            <a
                href="index.php"
                class="btn btn-light btn-sm me-2"
            >

                <i class="bi bi-speedometer2"></i>
                Dashboard

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-light btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>
                Logout

            </a>

        </div>

    </div>

</nav>


<div class="container-fluid py-4">


    <!-- ==================================================
            HEADER
    ================================================== -->

    <div class="d-flex
                flex-column
                flex-md-row
                justify-content-between
                align-items-md-center
                mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="bi bi-shop text-success"></i>

                Data Penjual

            </h3>

            <p class="text-muted mb-0">

                Kelola data penjual yang terdaftar
                di SIPESTA.

            </p>

        </div>


        <div class="mt-3 mt-md-0">

            <a
                href="tambah_penjual.php"
                class="btn btn-success"
            >

                <i class="bi bi-person-plus-fill"></i>

                Tambah Penjual

            </a>

        </div>

    </div>

    <!-- ==================================================
        ALERT SUCCESS
    ================================================== -->

    <?php if (!empty($_GET['success'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle"></i>

            <?= e($_GET['success']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==================================================
            ALERT ERROR
    ================================================== -->

    <?php if (!empty($_GET['error'])): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle"></i>

            <?= e($_GET['error']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>

    <!-- ==================================================
            STATISTIK
    ================================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL PENJUAL -->

        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="stat-icon
                                    bg-success-subtle
                                    text-success
                                    me-3"
                        >

                            <i class="bi bi-shop"></i>

                        </div>

                        <div>

                            <small class="text-muted">
                                Total Penjual
                            </small>

                            <h3 class="fw-bold mb-0">
                                <?= $totalPenjual ?>
                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- TOTAL PRODUK -->

        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="stat-icon
                                    bg-primary-subtle
                                    text-primary
                                    me-3"
                        >

                            <i class="bi bi-box-seam"></i>

                        </div>

                        <div>

                            <small class="text-muted">
                                Total Produk
                            </small>

                            <h3 class="fw-bold mb-0">
                                <?= $totalProduk ?>
                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- ==================================================
            PENCARIAN
    ================================================== -->

    <div class="card table-card shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2"
            >

                <div class="col-md-10">

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-search"></i>

                        </span>

                        <input
                            type="text"
                            name="keyword"
                            class="form-control"
                            placeholder="Cari nama usaha, username, atau alamat..."
                            value="<?= e($keyword) ?>"
                        >

                    </div>

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >

                        <i class="bi bi-search"></i>

                        Cari

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ==================================================
         TABEL PENJUAL
    ================================================== -->

    <div class="card table-card shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex
                        justify-content-between
                        align-items-center">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-list-ul"></i>

                    Daftar Penjual

                </h5>

                <span class="badge bg-success">

                    <?= $penjualList->num_rows ?>

                    Data

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Penjual</th>

                            <th>Nama Usaha</th>

                            <th>Alamat</th>

                            <th>Produk</th>

                            <th>Role</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($penjualList->num_rows > 0): ?>

                        <?php
                        $no = 1;
                        ?>

                        <?php while ($row = $penjualList->fetch_assoc()): ?>

                            <tr>


                                <!-- NOMOR -->

                                <td>
                                    <?= $no++ ?>
                                </td>


                                <!-- PENJUAL -->

                                <td>

                                    <div class="d-flex align-items-center">

                                        <div
                                            class="rounded-circle
                                                    bg-success-subtle
                                                    text-success
                                                    d-flex
                                                    align-items-center
                                                    justify-content-center
                                                    me-2"
                                            style="width:40px;height:40px;"
                                        >

                                            <i class="bi bi-person"></i>

                                        </div>


                                        <div>

                                            <div class="fw-semibold">

                                                <?= e(
                                                    $row['username']
                                                ) ?>

                                            </div>

                                            <small class="text-muted">

                                                User ID:
                                                <?= (int)$row['user_id'] ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- NAMA USAHA -->

                                <td>

                                    <span class="fw-semibold">

                                        <?= e(
                                            $row['nama_usaha']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ALAMAT -->

                                <td>

                                    <span class="text-muted">

                                        <?= e(
                                            $row['alamat'] ?: '-'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PRODUK -->

                                <td>

                                    <span
                                        class="badge
                                               bg-primary-subtle
                                               text-primary"
                                    >

                                        <?= (int)$row['jumlah_produk'] ?>

                                        produk

                                    </span>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <span class="badge bg-success">

                                        <?= e(
                                            $row['role']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?= badgeStatusAkunPenjual(
                                        $row['status']
                                    ) ?>

                                </td>


                                <!-- AKSI -->

                                <td>

                                    <a
                                        href="detail_penjual.php?id=<?= (int)$row['id'] ?>"
                                        class="btn btn-sm btn-outline-success"
                                    >

                                        <i class="bi bi-eye"></i>

                                        Detail

                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5"
                            >

                                <div class="text-muted">

                                    <i class="bi bi-shop fs-1"></i>

                                    <p class="mt-2 mb-0">

                                        Belum ada data penjual.

                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>