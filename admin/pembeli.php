<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

// ======================================================
// FILTER
// ======================================================

$keyword = trim($_GET['keyword'] ?? "");
$status  = $_GET['status'] ?? "";


// ======================================================
// QUERY DATA PEMBELI
// ======================================================

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.nama,
        p.tanggal_lahir,
        p.no_hp,
        p.alamat,
        p.nama_toko,
        p.jenis_kelamin,
        p.lama_usaha,
        u.username,
        u.status
    FROM pembeli p
    INNER JOIN users u
        ON u.id = p.user_id
    WHERE u.role = 'pembeli'
";

$params = [];
$types = "";


// ======================================================
// FILTER KEYWORD
// ======================================================

if ($keyword !== "") {

    $sql .= "
        AND (
            p.nama LIKE ?
            OR u.username LIKE ?
            OR p.no_hp LIKE ?
            OR p.nama_toko LIKE ?
            OR p.alamat LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "sssss";
}


// ======================================================
// FILTER STATUS
// ======================================================

if ($status !== "") {

    $sql .= " AND u.status = ?";

    $params[] = $status;

    $types .= "s";
}


// ======================================================
// URUTKAN
// ======================================================

$sql .= " ORDER BY p.id DESC";


// ======================================================
// EKSEKUSI QUERY
// ======================================================

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Query data pembeli gagal: " .
        $conn->error
    );
}

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result = $stmt->get_result();


// ======================================================
// STATISTIK PEMBELI
// ======================================================

$totalPembeli = 0;
$aktif = 0;
$nonaktif = 0;

$statQuery = $conn->query("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN u.status = 'aktif'
                THEN 1
                ELSE 0
            END
        ) AS aktif,

        SUM(
            CASE
                WHEN u.status = 'nonaktif'
                THEN 1
                ELSE 0
            END
        ) AS nonaktif

    FROM pembeli p

    INNER JOIN users u
        ON u.id = p.user_id

    WHERE u.role = 'pembeli'
");

if ($statQuery) {

    $stat = $statQuery->fetch_assoc();

    $totalPembeli =
        (int)($stat['total'] ?? 0);

    $aktif =
        (int)($stat['aktif'] ?? 0);

    $nonaktif =
        (int)($stat['nonaktif'] ?? 0);
}


// ======================================================
// FUNGSI BADGE STATUS
// ======================================================

function badgeStatusPembeli($status)
{
    if ($status === "aktif") {

        return '
            <span class="badge bg-success">
                <i class="fa-solid fa-circle-check me-1"></i>
                Aktif
            </span>
        ';
    }

    return '
        <span class="badge bg-danger">
            <i class="fa-solid fa-circle-xmark me-1"></i>
            Nonaktif
        </span>
    ';
}


// ======================================================
// FUNGSI HITUNG USIA
// ======================================================

function hitungUsia($tanggalLahir)
{
    if (empty($tanggalLahir)) {
        return "-";
    }

    try {

        $lahir = new DateTime($tanggalLahir);

        $hariIni = new DateTime();

        return $lahir->diff($hariIni)->y . " tahun";

    } catch (Exception $e) {

        return "-";
    }
}


// ======================================================
// FUNGSI FORMAT TANGGAL
// ======================================================

function formatTanggalPembeli($tanggal)
{
    if (empty($tanggal)) {
        return "-";
    }

    $timestamp = strtotime($tanggal);

    if ($timestamp === false) {
        return "-";
    }

    return date(
        "d-m-Y",
        $timestamp
    );
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

    <title>
        Data Pembeli - SIPESTA
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f5f7fb;
        }

        .sidebar {
            min-height: 100vh;
            background: #212529;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            display: block;
            padding: 12px 18px;
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #343a40;
            color: #fff;
        }

        .sidebar-title {
            padding: 18px;
        }

        .stat-card {
            border: 0;
            border-radius: 15px;
        }

        .table th {
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .btn {
            border-radius: 8px;
        }

        .page-title {
            font-weight: 700;
        }

        .card {
            border-radius: 15px;
        }

        .badge {
            font-size: 0.78rem;
        }

        @media (max-width: 767px) {

            .sidebar {
                min-height: auto;
            }

            .table {
                font-size: 13px;
            }

        }

    </style>

</head>


<body>


<div class="container-fluid">

    <div class="row">


        <!-- ==================================================
             SIDEBAR
        ================================================== -->

        <div class="col-md-2 p-0 sidebar">

            <div class="sidebar-title text-white">

                <h4 class="mb-1">

                    <i class="fa-solid fa-store"></i>

                    SIPESTA

                </h4>

                <small>
                    Panel Administrator
                </small>

            </div>


            <hr class="text-secondary">


            <!-- Dashboard -->

            <a href="index.php">

                <i class="fa-solid fa-gauge me-2"></i>

                Dashboard

            </a>


            <!-- Penjual -->

            <a href="penjual.php">

                <i class="fa-solid fa-users me-2"></i>

                Data Penjual

            </a>


            <!-- Pembeli -->

            <a
                href="pembeli.php"
                class="active"
            >

                <i class="fa-solid fa-user-group me-2"></i>

                Data Pembeli

            </a>


            <!-- Produk -->

            <a href="produk.php">

                <i class="fa-solid fa-box me-2"></i>

                Data Produk

            </a>


            <!-- Kategori -->

            <a href="kategori.php">

                <i class="fa-solid fa-layer-group me-2"></i>

                Kategori

            </a>


            <!-- Pesanan -->

            <a href="pesanan.php">

                <i class="fa-solid fa-cart-shopping me-2"></i>

                Pesanan

            </a>


            <hr class="text-secondary">


            <!-- Logout -->

            <a
                href="../logout.php"
                onclick="return confirm('Yakin ingin logout?')"
            >

                <i class="fa-solid fa-right-from-bracket me-2"></i>

                Logout

            </a>

        </div>


        <!-- ==================================================
             CONTENT
        ================================================== -->

        <div class="col-md-10 p-4">


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

                    <h2 class="page-title mb-1">

                        <i class="fa-solid fa-user-group text-success"></i>

                        Data Pembeli

                    </h2>


                    <p class="text-muted mb-0">

                        Kelola data akun dan informasi
                        pembeli SIPESTA.

                    </p>

                </div>


                <!-- TAMBAH PEMBELI -->

                <div class="mt-3 mt-md-0">

                    <a
                        href="tambah_pembeli.php"
                        class="btn btn-success"
                    >

                        <i class="fa-solid fa-user-plus me-1"></i>

                        Tambah Pembeli

                    </a>

                </div>

            </div>


            <!-- ==================================================
                 PESAN SUKSES
            ================================================== -->

            <?php if (!empty($_GET['success'])): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i class="fa-solid fa-circle-check me-2"></i>

                    <?= e($_GET['success']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 PESAN ERROR
            ================================================== -->

            <?php if (!empty($_GET['error'])): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <i class="fa-solid fa-circle-exclamation me-2"></i>

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


                <!-- TOTAL -->

                <div class="col-md-4">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex
                                        justify-content-between
                                        align-items-center">

                                <div>

                                    <small class="text-muted">

                                        Total Pembeli

                                    </small>

                                    <h3 class="mb-0 fw-bold">

                                        <?= $totalPembeli ?>

                                    </h3>

                                </div>


                                <div class="text-primary fs-2">

                                    <i class="fa-solid fa-users"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- AKTIF -->

                <div class="col-md-4">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex
                                        justify-content-between
                                        align-items-center">

                                <div>

                                    <small class="text-muted">

                                        Akun Aktif

                                    </small>

                                    <h3 class="text-success mb-0 fw-bold">

                                        <?= $aktif ?>

                                    </h3>

                                </div>


                                <div class="text-success fs-2">

                                    <i class="fa-solid fa-circle-check"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- NONAKTIF -->

                <div class="col-md-4">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex
                                        justify-content-between
                                        align-items-center">

                                <div>

                                    <small class="text-muted">

                                        Akun Nonaktif

                                    </small>

                                    <h3 class="text-danger mb-0 fw-bold">

                                        <?= $nonaktif ?>

                                    </h3>

                                </div>


                                <div class="text-danger fs-2">

                                    <i class="fa-solid fa-circle-xmark"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 FILTER
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">


                    <form method="GET">


                        <div class="row g-2">


                            <!-- KEYWORD -->

                            <div class="col-md-7">

                                <label class="form-label fw-semibold">

                                    Cari Pembeli

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="fa-solid fa-magnifying-glass"></i>

                                    </span>

                                    <input
                                        type="text"
                                        name="keyword"
                                        class="form-control"
                                        placeholder="Cari nama, username, no HP, toko, atau alamat..."
                                        value="<?= e($keyword) ?>"
                                    >

                                </div>

                            </div>


                            <!-- STATUS -->

                            <div class="col-md-3">

                                <label class="form-label fw-semibold">

                                    Status

                                </label>

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option value="">

                                        Semua Status

                                    </option>


                                    <option
                                        value="aktif"
                                        <?= $status === "aktif"
                                            ? "selected"
                                            : "" ?>
                                    >

                                        Aktif

                                    </option>


                                    <option
                                        value="nonaktif"
                                        <?= $status === "nonaktif"
                                            ? "selected"
                                            : "" ?>
                                    >

                                        Nonaktif

                                    </option>

                                </select>

                            </div>


                            <!-- BUTTON -->

                            <div class="col-md-2">

                                <label class="form-label d-none d-md-block">

                                    &nbsp;

                                </label>

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >

                                    <i class="fa-solid fa-search me-1"></i>

                                    Cari

                                </button>

                            </div>

                        </div>


                        <!-- RESET -->

                        <?php if (
                            $keyword !== "" ||
                            $status !== ""
                        ): ?>

                            <div class="mt-3">

                                <a
                                    href="pembeli.php"
                                    class="btn btn-sm btn-outline-secondary"
                                >

                                    <i class="fa-solid fa-rotate-left me-1"></i>

                                    Reset Filter

                                </a>

                            </div>

                        <?php endif; ?>


                    </form>

                </div>

            </div>


            <!-- ==================================================
                 TABEL
            ================================================== -->

            <div class="card shadow-sm border-0">


                <!-- HEADER -->

                <div class="card-header bg-white py-3">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <strong>

                            <i class="fa-solid fa-table me-1"></i>

                            Daftar Pembeli

                        </strong>


                        <span class="badge bg-success">

                            <?= $result->num_rows ?>

                            Data

                        </span>

                    </div>

                </div>


                <!-- BODY -->

                <div class="card-body p-0">


                    <div class="table-responsive">


                        <table
                            class="table table-hover table-bordered mb-0"
                        >


                            <thead class="table-dark">

                                <tr>

                                    <th>
                                        No
                                    </th>

                                    <th>
                                        Nama
                                    </th>

                                    <th>
                                        Username
                                    </th>

                                    <th>
                                        No. HP
                                    </th>

                                    <th>
                                        Nama Toko
                                    </th>

                                    <th>
                                        Jenis Kelamin
                                    </th>

                                    <th>
                                        Tanggal Lahir
                                    </th>

                                    <th>
                                        Usia
                                    </th>

                                    <th>
                                        Lama Usaha
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th
                                        class="text-center"
                                        style="min-width: 150px;"
                                    >
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if ($result->num_rows > 0): ?>


                                <?php $no = 1; ?>


                                <?php while (
                                    $row = $result->fetch_assoc()
                                ): ?>


                                    <tr>


                                        <!-- NOMOR -->

                                        <td>

                                            <?= $no++ ?>

                                        </td>


                                        <!-- NAMA -->

                                        <td>

                                            <strong>

                                                <?= e(
                                                    $row['nama']
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- USERNAME -->

                                        <td>

                                            <span class="text-muted">

                                                @<?= e(
                                                    $row['username']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- NO HP -->

                                        <td>

                                            <?php if (
                                                !empty($row['no_hp'])
                                            ): ?>

                                                <a
                                                    href="tel:<?= e($row['no_hp']) ?>"
                                                    class="text-decoration-none"
                                                >

                                                    <i
                                                        class="fa-solid fa-phone me-1"
                                                    ></i>

                                                    <?= e(
                                                        $row['no_hp']
                                                    ) ?>

                                                </a>

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>


                                        <!-- TOKO -->

                                        <td>

                                            <?= e(
                                                $row['nama_toko']
                                                ?: '-'
                                            ) ?>

                                        </td>


                                        <!-- JENIS KELAMIN -->

                                        <td>

                                            <?= e(
                                                $row['jenis_kelamin']
                                                ?: '-'
                                            ) ?>

                                        </td>


                                        <!-- TANGGAL LAHIR -->

                                        <td>

                                            <?= formatTanggalPembeli(
                                                $row['tanggal_lahir']
                                            ) ?>

                                        </td>


                                        <!-- USIA -->

                                        <td>

                                            <span class="badge bg-info text-dark">

                                                <?= hitungUsia(
                                                    $row['tanggal_lahir']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- LAMA USAHA -->

                                        <td>

                                            <?php if (
                                                $row['lama_usaha'] !== null &&
                                                $row['lama_usaha'] !== ''
                                            ): ?>

                                                <?= e(
                                                    $row['lama_usaha']
                                                ) ?>

                                                tahun

                                            <?php else: ?>

                                                -

                                            <?php endif; ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?= badgeStatusPembeli(
                                                $row['status']
                                            ) ?>

                                        </td>


                                        <!-- AKSI -->

                                        <td class="text-center">


                                            <div
                                                class="btn-group"
                                                role="group"
                                            >


                                                <!-- DETAIL -->

                                                <a
                                                    href="detail_pembeli.php?id=<?= (int)$row['id'] ?>"
                                                    class="btn btn-sm btn-primary"
                                                    title="Lihat detail"
                                                >

                                                    <i
                                                        class="fa-solid fa-eye"
                                                    ></i>

                                                </a>


                                                <!-- EDIT -->

                                                <a
                                                    href="edit_pembeli.php?id=<?= (int)$row['id'] ?>"
                                                    class="btn btn-sm btn-warning"
                                                    title="Edit pembeli"
                                                >

                                                    <i
                                                        class="fa-solid fa-pen-to-square"
                                                    ></i>

                                                </a>


                                                <!-- STATUS -->

                                                <form
                                                    action="ubah_status_pembeli.php"
                                                    method="POST"
                                                    class="d-inline"
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$row['id'] ?>"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="<?= $row['status'] === 'aktif'
                                                            ? 'nonaktif'
                                                            : 'aktif' ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm <?= $row['status'] === 'aktif'
                                                            ? 'btn-danger'
                                                            : 'btn-success' ?>"
                                                        title="<?= $row['status'] === 'aktif'
                                                            ? 'Nonaktifkan akun'
                                                            : 'Aktifkan akun' ?>"
                                                        onclick="return confirm(
                                                            'Yakin ingin mengubah status akun pembeli ini?'
                                                        )"
                                                    >


                                                        <?php if (
                                                            $row['status'] === 'aktif'
                                                        ): ?>

                                                            <i
                                                                class="fa-solid fa-ban"
                                                            ></i>

                                                        <?php else: ?>

                                                            <i
                                                                class="fa-solid fa-check"
                                                            ></i>

                                                        <?php endif; ?>


                                                    </button>


                                                </form>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="11"
                                        class="text-center py-5"
                                    >

                                        <i
                                            class="fa-solid fa-user-slash fs-1 text-muted"
                                        ></i>


                                        <p class="mt-3 mb-1 fw-semibold">

                                            Data pembeli tidak ditemukan.

                                        </p>


                                        <?php if (
                                            $keyword !== "" ||
                                            $status !== ""
                                        ): ?>

                                            <small class="text-muted">

                                                Coba ubah kata kunci
                                                atau filter status.

                                            </small>

                                        <?php else: ?>

                                            <small class="text-muted">

                                                Belum ada data pembeli
                                                yang terdaftar.

                                            </small>

                                        <?php endif; ?>


                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- FOOTER -->

                <div class="card-footer bg-white">

                    <small class="text-muted">

                        Menampilkan
                        <strong>
                            <?= $result->num_rows ?>
                        </strong>
                        data pembeli.

                    </small>

                </div>


            </div>


        </div>

    </div>

</div>


<!-- ==================================================
        BOOTSTRAP JS
================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>