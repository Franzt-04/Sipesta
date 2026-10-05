<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

$keyword = trim($_GET['keyword'] ?? "");
$kategori = (int)($_GET['kategori'] ?? 0);
$status = $_GET['status'] ?? "";

/*
|--------------------------------------------------------------------------
| Query Produk
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.nama_produk,
        p.deskripsi,
        p.harga,
        p.satuan,
        p.stok,
        p.foto,
        p.status,
        p.created_at,
        k.nama_kategori,
        pen.nama_usaha
    FROM produk p
    LEFT JOIN kategori k
        ON k.id = p.kategori_id
    LEFT JOIN penjual pen
        ON pen.id = p.penjual_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($keyword !== "") {

    $sql .= "
        AND (
            p.nama_produk LIKE ?
            OR p.deskripsi LIKE ?
            OR pen.nama_usaha LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "sss";
}

if ($kategori > 0) {

    $sql .= " AND p.kategori_id = ?";

    $params[] = $kategori;
    $types .= "i";
}

if ($status !== "") {

    $sql .= " AND p.status = ?";

    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Ambil Kategori
|--------------------------------------------------------------------------
*/

$kategoriResult = $conn->query("
    SELECT id, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");


/*
|--------------------------------------------------------------------------
| Statistik Produk
|--------------------------------------------------------------------------
*/

$stat = [
    'total' => 0,
    'tersedia' => 0,
    'habis' => 0,
    'nonaktif' => 0
];

$statResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'tersedia') AS tersedia,
        SUM(status = 'habis') AS habis,
        SUM(status = 'nonaktif') AS nonaktif
    FROM produk
");

if ($statResult) {
    $stat = $statResult->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| Badge Status Produk
|--------------------------------------------------------------------------
*/

function badgeStatusProdukAdmin($status)
{
    switch ($status) {

        case 'tersedia':
            return '<span class="badge bg-success">
                        <i class="fa-solid fa-check"></i> Tersedia
                    </span>';

        case 'habis':
            return '<span class="badge bg-warning text-dark">
                        <i class="fa-solid fa-box-open"></i> Habis
                    </span>';

        case 'nonaktif':
            return '<span class="badge bg-danger">
                        <i class="fa-solid fa-ban"></i> Nonaktif
                    </span>';

        default:
            return '<span class="badge bg-secondary">
                        Tidak diketahui
                    </span>';
    }
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

    <title>Data Produk - SIPESTA</title>

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

        .sidebar {
            min-height: 100vh;
            background: #212529;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            display: block;
            padding: 12px 18px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #343a40;
            color: #fff;
        }

        .stat-card {
            border: 0;
            border-radius: 15px;
        }

        .product-img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        .no-image {
            width: 65px;
            height: 65px;
            border-radius: 10px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .table td {
            vertical-align: middle;
        }

        .table th {
            white-space: nowrap;
        }

    </style>

</head>

<body>

<div class="container-fluid">

    <div class="row">

        <!-- SIDEBAR -->

        <div class="col-md-2 p-0 sidebar">

            <div class="p-3 text-white">

                <h4 class="mb-1">
                    <i class="fa-solid fa-store"></i>
                    SIPESTA
                </h4>

                <small>
                    Panel Administrator
                </small>

            </div>

            <hr class="text-secondary">

            <a href="index.php">
                <i class="fa-solid fa-gauge me-2"></i>
                Dashboard
            </a>

            <a href="penjual.php">
                <i class="fa-solid fa-users me-2"></i>
                Data Penjual
            </a>

            <a href="pembeli.php">
                <i class="fa-solid fa-user-group me-2"></i>
                Data Pembeli
            </a>

            <a href="produk.php" class="active">
                <i class="fa-solid fa-box me-2"></i>
                Data Produk
            </a>

            <a href="kategori.php">
                <i class="fa-solid fa-layer-group me-2"></i>
                Kategori
            </a>

            <a href="pesanan.php">
                <i class="fa-solid fa-cart-shopping me-2"></i>
                Pesanan
            </a>

            <hr class="text-secondary">

            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket me-2"></i>
                Logout
            </a>

        </div>


        <!-- CONTENT -->

        <div class="col-md-10 p-4">

            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="mb-1">

                        <i class="fa-solid fa-box"></i>

                        Data Produk

                    </h2>

                    <p class="text-muted mb-0">

                        Kelola seluruh produk yang terdaftar di SIPESTA.

                    </p>

                </div>

            </div>


            <!-- STATISTIK -->

            <div class="row g-3 mb-4">

                <!-- TOTAL -->

                <div class="col-md-3">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Total Produk
                                    </small>

                                    <h3 class="mb-0">
                                        <?= (int)$stat['total'] ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-boxes-stacked fs-2 text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TERSEDIA -->

                <div class="col-md-3">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Tersedia
                                    </small>

                                    <h3 class="text-success mb-0">
                                        <?= (int)$stat['tersedia'] ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-circle-check fs-2 text-success"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- HABIS -->

                <div class="col-md-3">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Stok Habis
                                    </small>

                                    <h3 class="text-warning mb-0">
                                        <?= (int)$stat['habis'] ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-box-open fs-2 text-warning"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- NONAKTIF -->

                <div class="col-md-3">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Nonaktif
                                    </small>

                                    <h3 class="text-danger mb-0">
                                        <?= (int)$stat['nonaktif'] ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-ban fs-2 text-danger"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- FILTER -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <form method="GET">

                        <div class="row g-2">

                            <div class="col-md-5">

                                <input
                                    type="text"
                                    name="keyword"
                                    class="form-control"
                                    placeholder="Cari nama produk atau penjual..."
                                    value="<?= e($keyword) ?>"
                                >

                            </div>


                            <div class="col-md-3">

                                <select
                                    name="kategori"
                                    class="form-select"
                                >

                                    <option value="0">
                                        Semua Kategori
                                    </option>

                                    <?php while ($k = $kategoriResult->fetch_assoc()): ?>

                                        <option
                                            value="<?= (int)$k['id'] ?>"
                                            <?= $kategori == $k['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($k['nama_kategori']) ?>
                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>


                            <div class="col-md-2">

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option value="">
                                        Semua Status
                                    </option>

                                    <option
                                        value="tersedia"
                                        <?= $status === 'tersedia' ? 'selected' : '' ?>
                                    >
                                        Tersedia
                                    </option>

                                    <option
                                        value="habis"
                                        <?= $status === 'habis' ? 'selected' : '' ?>
                                    >
                                        Habis
                                    </option>

                                    <option
                                        value="nonaktif"
                                        <?= $status === 'nonaktif' ? 'selected' : '' ?>
                                    >
                                        Nonaktif
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-2">

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >

                                    <i class="fa-solid fa-search"></i>

                                    Cari

                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            <!-- TABEL -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-table"></i>

                        Daftar Produk

                    </strong>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered mb-0">

                            <thead class="table-dark">

                                <tr>

                                    <th>No</th>

                                    <th>Produk</th>

                                    <th>Penjual</th>

                                    <th>Kategori</th>

                                    <th>Harga</th>

                                    <th>Satuan</th>

                                    <th>Stok</th>

                                    <th>Status</th>

                                    <th>Tanggal</th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php if ($result->num_rows > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($row = $result->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            <?= $no++ ?>
                                        </td>


                                        <!-- PRODUK -->

                                        <td>

                                            <div class="d-flex align-items-center gap-2">

                                                <?php if (!empty($row['foto'])): ?>

                                                    <img
                                                        src="../uploads/produk/<?= e($row['foto']) ?>"
                                                        class="product-img"
                                                        alt="<?= e($row['nama_produk']) ?>"
                                                    >

                                                <?php else: ?>

                                                    <div class="no-image">

                                                        <i class="fa-solid fa-image"></i>

                                                    </div>

                                                <?php endif; ?>


                                                <div>

                                                    <strong>
                                                        <?= e($row['nama_produk']) ?>
                                                    </strong>

                                                    <?php if (!empty($row['deskripsi'])): ?>

                                                        <br>

                                                        <small class="text-muted">

                                                            <?= e(mb_strimwidth($row['deskripsi'], 0, 50, '...')) ?>

                                                        </small>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </td>


                                        <!-- PENJUAL -->

                                        <td>
                                            <?= e($row['nama_usaha'] ?: '-') ?>
                                        </td>


                                        <!-- KATEGORI -->

                                        <td>
                                            <?= e($row['nama_kategori'] ?: '-') ?>
                                        </td>


                                        <!-- HARGA -->

                                        <td>
                                            <?= rupiah($row['harga']) ?>
                                        </td>


                                        <!-- SATUAN -->

                                        <td>
                                            <?= e($row['satuan']) ?>
                                        </td>


                                        <!-- STOK -->

                                        <td>

                                            <strong>
                                                <?= e($row['stok']) ?>
                                            </strong>

                                        </td>


                                        <!-- STATUS -->

                                        <td>
                                            <?= badgeStatusProdukAdmin($row['status']) ?>
                                        </td>


                                        <!-- TANGGAL -->

                                        <td>

                                            <?= !empty($row['created_at'])
                                                ? date('d-m-Y', strtotime($row['created_at']))
                                                : '-'
                                            ?>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="9"
                                        class="text-center py-5"
                                    >

                                        <i class="fa-solid fa-box-open fs-1 text-muted"></i>

                                        <p class="mt-3 mb-0">

                                            Data produk tidak ditemukan.

                                        </p>

                                    </td>

                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>