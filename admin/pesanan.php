<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

$keyword = trim($_GET['keyword'] ?? "");
$status = $_GET['status'] ?? "";

/*
|--------------------------------------------------------------------------
| Query Pesanan
|--------------------------------------------------------------------------
|
| Satu pesanan bisa berisi beberapa produk.
| Karena itu kita menggunakan GROUP BY pesanan.
|
*/

$sql = "
    SELECT
        pe.id,
        pe.kode_pesanan,
        pe.pembeli_id,
        pe.nama_penerima,
        pe.no_hp,
        pe.tanggal_pesanan,
        pe.total_harga,
        pe.alamat_pengiriman,
        pe.catatan,
        pe.metode_pembayaran,
        pe.status,
        pb.nama AS nama_pembeli,
        pb.nama_toko,
        COUNT(dp.id) AS jumlah_item
    FROM pesanan pe
    INNER JOIN pembeli pb
        ON pb.id = pe.pembeli_id
    LEFT JOIN detail_pesanan dp
        ON dp.pesanan_id = pe.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($keyword !== "") {

    $sql .= "
        AND (
            pe.kode_pesanan LIKE ?
            OR pe.nama_penerima LIKE ?
            OR pe.no_hp LIKE ?
            OR pb.nama LIKE ?
            OR pb.nama_toko LIKE ?
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

if ($status !== "") {

    $sql .= " AND pe.status = ?";

    $params[] = $status;
    $types .= "s";
}

$sql .= "
    GROUP BY
        pe.id,
        pe.kode_pesanan,
        pe.pembeli_id,
        pe.nama_penerima,
        pe.no_hp,
        pe.tanggal_pesanan,
        pe.total_harga,
        pe.alamat_pengiriman,
        pe.catatan,
        pe.metode_pembayaran,
        pe.status,
        pb.nama,
        pb.nama_toko
    ORDER BY pe.id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Statistik Pesanan
|--------------------------------------------------------------------------
*/

$stat = [
    'total' => 0,
    'menunggu' => 0,
    'diproses' => 0,
    'dikirim' => 0,
    'selesai' => 0,
    'dibatalkan' => 0,
    'nilai_selesai' => 0
];

$statResult = $conn->query("
    SELECT
        COUNT(*) AS total,

        SUM(status = 'menunggu') AS menunggu,

        SUM(status = 'diproses') AS diproses,

        SUM(status = 'dikirim') AS dikirim,

        SUM(status = 'selesai') AS selesai,

        SUM(status = 'dibatalkan') AS dibatalkan,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'selesai'
                    THEN total_harga
                    ELSE 0
                END
            ),
            0
        ) AS nilai_selesai

    FROM pesanan
");

if ($statResult) {
    $stat = $statResult->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| Badge Status
|--------------------------------------------------------------------------
*/

function badgeStatusPesananAdmin($status)
{
    switch ($status) {

        case 'menunggu':

            return '<span class="badge bg-warning text-dark">
                        <i class="fa-solid fa-clock"></i>
                        Menunggu
                    </span>';

        case 'diproses':

            return '<span class="badge bg-primary">
                        <i class="fa-solid fa-gears"></i>
                        Diproses
                    </span>';

        case 'dikirim':

            return '<span class="badge bg-info text-dark">
                        <i class="fa-solid fa-truck"></i>
                        Dikirim
                    </span>';

        case 'selesai':

            return '<span class="badge bg-success">
                        <i class="fa-solid fa-circle-check"></i>
                        Selesai
                    </span>';

        case 'dibatalkan':

            return '<span class="badge bg-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Dibatalkan
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

    <title>Pesanan - SIPESTA</title>

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


            <a href="produk.php">

                <i class="fa-solid fa-box me-2"></i>

                Data Produk

            </a>


            <a href="kategori.php">

                <i class="fa-solid fa-layer-group me-2"></i>

                Kategori

            </a>


            <a href="pesanan.php" class="active">

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

            <div class="mb-4">

                <h2 class="mb-1">

                    <i class="fa-solid fa-cart-shopping"></i>

                    Manajemen Pesanan

                </h2>

                <p class="text-muted mb-0">

                    Pantau seluruh transaksi yang terjadi di SIPESTA.

                </p>

            </div>


            <!-- STATISTIK -->

            <div class="row g-3 mb-4">


                <div class="col-lg-3 col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Total Pesanan
                            </small>

                            <div class="d-flex justify-content-between">

                                <h3>
                                    <?= (int)$stat['total'] ?>
                                </h3>

                                <i class="fa-solid fa-cart-shopping fs-2 text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Menunggu
                            </small>

                            <div class="d-flex justify-content-between">

                                <h3 class="text-warning">
                                    <?= (int)$stat['menunggu'] ?>
                                </h3>

                                <i class="fa-solid fa-clock fs-2 text-warning"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Diproses
                            </small>

                            <div class="d-flex justify-content-between">

                                <h3 class="text-primary">
                                    <?= (int)$stat['diproses'] ?>
                                </h3>

                                <i class="fa-solid fa-gears fs-2 text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <small class="text-muted">
                                Selesai
                            </small>

                            <div class="d-flex justify-content-between">

                                <h3 class="text-success">
                                    <?= (int)$stat['selesai'] ?>
                                </h3>

                                <i class="fa-solid fa-circle-check fs-2 text-success"></i>

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

                            <div class="col-md-7">

                                <input
                                    type="text"
                                    name="keyword"
                                    class="form-control"
                                    placeholder="Cari kode pesanan, pembeli, no HP, atau toko..."
                                    value="<?= e($keyword) ?>"
                                >

                            </div>


                            <div class="col-md-3">

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option value="">
                                        Semua Status
                                    </option>

                                    <option
                                        value="menunggu"
                                        <?= $status === 'menunggu' ? 'selected' : '' ?>
                                    >
                                        Menunggu
                                    </option>

                                    <option
                                        value="diproses"
                                        <?= $status === 'diproses' ? 'selected' : '' ?>
                                    >
                                        Diproses
                                    </option>

                                    <option
                                        value="dikirim"
                                        <?= $status === 'dikirim' ? 'selected' : '' ?>
                                    >
                                        Dikirim
                                    </option>

                                    <option
                                        value="selesai"
                                        <?= $status === 'selesai' ? 'selected' : '' ?>
                                    >
                                        Selesai
                                    </option>

                                    <option
                                        value="dibatalkan"
                                        <?= $status === 'dibatalkan' ? 'selected' : '' ?>
                                    >
                                        Dibatalkan
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


            <!-- RINGKASAN NILAI -->

            <div class="alert alert-success shadow-sm">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <strong>

                            <i class="fa-solid fa-money-bill-wave"></i>

                            Nilai Transaksi Selesai

                        </strong>

                        <br>

                        <small>
                            Total nilai pesanan dengan status selesai.
                        </small>

                    </div>


                    <h4 class="mb-0">

                        <?= rupiah($stat['nilai_selesai']) ?>

                    </h4>

                </div>

            </div>


            <!-- TABLE -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-table"></i>

                        Daftar Pesanan

                    </strong>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered mb-0">

                            <thead class="table-dark">

                                <tr>

                                    <th>No</th>

                                    <th>Kode Pesanan</th>

                                    <th>Tanggal</th>

                                    <th>Pembeli</th>

                                    <th>Nama Toko</th>

                                    <th>Item</th>

                                    <th>Total</th>

                                    <th>Pembayaran</th>

                                    <th>Status</th>

                                    <th>Aksi</th>

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


                                        <td>

                                            <strong>

                                                <?= e($row['kode_pesanan']) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= !empty($row['tanggal_pesanan'])
                                                ? date(
                                                    'd-m-Y H:i',
                                                    strtotime($row['tanggal_pesanan'])
                                                )
                                                : '-'
                                            ?>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= e($row['nama_pembeli']) ?>

                                            </strong>

                                            <br>

                                            <small class="text-muted">

                                                <?= e($row['no_hp'] ?: '-') ?>

                                            </small>

                                        </td>


                                        <td>

                                            <?= e($row['nama_toko'] ?: '-') ?>

                                        </td>


                                        <td>

                                            <span class="badge bg-secondary">

                                                <?= (int)$row['jumlah_item'] ?>

                                                Item

                                            </span>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= rupiah($row['total_harga']) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?php if ($row['metode_pembayaran'] === 'COD'): ?>

                                                <span class="badge bg-warning text-dark">

                                                    COD

                                                </span>

                                            <?php elseif ($row['metode_pembayaran'] === 'TRANSFER'): ?>

                                                <span class="badge bg-primary">

                                                    Transfer

                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary">

                                                    <?= e($row['metode_pembayaran'] ?: '-') ?>

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= badgeStatusPesananAdmin($row['status']) ?>

                                        </td>


                                        <td>

                                            <a
                                                href="detail_pesanan.php?id=<?= (int)$row['id'] ?>"
                                                class="btn btn-sm btn-primary"
                                                title="Lihat Detail"
                                            >

                                                <i class="fa-solid fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="10"
                                        class="text-center py-5"
                                    >

                                        <i class="fa-solid fa-cart-shopping fs-1 text-muted"></i>

                                        <p class="mt-3 mb-0">

                                            Tidak ada pesanan ditemukan.

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