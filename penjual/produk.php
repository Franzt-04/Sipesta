<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("penjual");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil data penjual
|--------------------------------------------------------------------------
*/
$stmtPenjual = $conn->prepare("
    SELECT id, nama_usaha
    FROM penjual
    WHERE user_id = ?
    LIMIT 1
");

$stmtPenjual->bind_param("i", $user_id);
$stmtPenjual->execute();

$penjual = $stmtPenjual
    ->get_result()
    ->fetch_assoc();

if (!$penjual) {
    die("Data penjual tidak ditemukan.");
}

$penjual_id = (int) $penjual['id'];


/*
|--------------------------------------------------------------------------
| Filter pencarian
|--------------------------------------------------------------------------
*/
$keyword = trim($_GET['keyword'] ?? '');
$status = trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| Query produk
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
        k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k
        ON p.kategori_id = k.id
    WHERE p.penjual_id = ?
";

$params = [$penjual_id];
$types = "i";

if ($keyword !== '') {

    $sql .= "
        AND (
            p.nama_produk LIKE ?
            OR p.deskripsi LIKE ?
            OR k.nama_kategori LIKE ?
        )
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "sss";
}

if ($status !== '') {

    $sql .= " AND p.status = ?";

    $params[] = $status;
    $types .= "s";
}

$sql .= "
    ORDER BY p.created_at DESC, p.id DESC
";


$stmtProduk = $conn->prepare($sql);

$stmtProduk->bind_param($types, ...$params);

$stmtProduk->execute();

$produkResult = $stmtProduk->get_result();


/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) AS tersedia,
        SUM(CASE WHEN status = 'habis' THEN 1 ELSE 0 END) AS habis,
        SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) AS nonaktif
    FROM produk
    WHERE penjual_id = ?
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$statistik = $stmt
    ->get_result()
    ->fetch_assoc();

$totalProduk = (int) ($statistik['total'] ?? 0);
$produkTersedia = (int) ($statistik['tersedia'] ?? 0);
$produkHabis = (int) ($statistik['habis'] ?? 0);
$produkNonaktif = (int) ($statistik['nonaktif'] ?? 0);


/*
|--------------------------------------------------------------------------
| Helper status
|--------------------------------------------------------------------------
*/
function badgeStatusProduk($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'tersedia':
            return '<span class="badge text-bg-success">
                        Tersedia
                    </span>';

        case 'habis':
            return '<span class="badge text-bg-danger">
                        Habis
                    </span>';

        case 'nonaktif':
            return '<span class="badge text-bg-secondary">
                        Nonaktif
                    </span>';

        default:
            return '<span class="badge text-bg-warning">'
                . e($status)
                . '</span>';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Kelola Produk - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 16px;
        }

        .stat-card {
            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            background: #f1f3f5;
        }

        .no-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            background: #f1f3f5;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #adb5bd;
            font-size: 24px;
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container-fluid px-4">

        <a
            href="index.php"
            class="navbar-brand text-success">

            <i class="bi bi-shop"></i>
            SIPESTA

        </a>


        <div class="d-flex align-items-center gap-2">

            <span class="text-muted d-none d-md-inline">

                <?= e($penjual['nama_usaha']) ?>

            </span>

            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm">

                <i class="bi bi-box-arrow-right"></i>
                Logout

            </a>

        </div>

    </div>

</nav>


<div class="container-fluid px-4 py-4">


    <!-- HEADER -->

    <div class="d-flex flex-column flex-md-row
                justify-content-between
                align-items-md-center
                gap-3 mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                Kelola Produk

            </h2>

            <p class="text-muted mb-0">

                Kelola produk yang dijual di toko Anda.

            </p>

        </div>


        <a
            href="tambah_produk.php"
            class="btn btn-success">

            <i class="bi bi-plus-lg"></i>

            Tambah Produk

        </a>

    </div>


    <!-- ALERT -->

    <?php if (!empty($_GET['success'])): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle"></i>

            <?= e($_GET['success']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if (!empty($_GET['error'])): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-circle"></i>

            <?= e($_GET['error']) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- STATISTIK -->

    <div class="row g-3 mb-4">


        <div class="col-6 col-md-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Semua Produk
                    </small>

                    <h3 class="fw-bold mt-2 mb-0">
                        <?= $totalProduk ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Tersedia
                    </small>

                    <h3 class="fw-bold text-success mt-2 mb-0">
                        <?= $produkTersedia ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Habis
                    </small>

                    <h3 class="fw-bold text-danger mt-2 mb-0">
                        <?= $produkHabis ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-6 col-md-3">

            <div class="card stat-card shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Nonaktif
                    </small>

                    <h3 class="fw-bold text-secondary mt-2 mb-0">
                        <?= $produkNonaktif ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- FILTER -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-2">

                <div class="col-12 col-md-6">

                    <label class="form-label">

                        Cari Produk

                    </label>

                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Nama produk..."
                        value="<?= e($keyword) ?>">

                </div>


                <div class="col-12 col-md-4">

                    <label class="form-label">

                        Status

                    </label>

                    <select
                        name="status"
                        class="form-select">

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="tersedia"
                            <?= $status === 'tersedia' ? 'selected' : '' ?>>

                            Tersedia

                        </option>

                        <option
                            value="habis"
                            <?= $status === 'habis' ? 'selected' : '' ?>>

                            Habis

                        </option>

                        <option
                            value="nonaktif"
                            <?= $status === 'nonaktif' ? 'selected' : '' ?>>

                            Nonaktif

                        </option>

                    </select>

                </div>


                <div class="col-12 col-md-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-success w-100">

                        <i class="bi bi-search"></i>
                        Cari

                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- TABEL PRODUK -->

    <div class="card shadow-sm">

        <div class="card-header bg-white py-3">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-box-seam"></i>

                Daftar Produk

            </h5>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="px-3">
                                Produk
                            </th>

                            <th>
                                Kategori
                            </th>

                            <th>
                                Harga
                            </th>

                            <th>
                                Stok
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="text-center">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($produkResult->num_rows > 0): ?>

                        <?php while ($produk = $produkResult->fetch_assoc()): ?>

                            <tr>

                                <!-- PRODUK -->

                                <td class="px-3">

                                    <div class="d-flex align-items-center gap-3">


                                        <?php

                                        $foto = trim(
                                            $produk['foto'] ?? ''
                                        );

                                        $pathFoto =
                                            "../uploads/produk/"
                                            . $foto;

                                        ?>


                                        <?php if ($foto !== ''): ?>

                                            <img
                                                src="<?= e($pathFoto) ?>"
                                                class="product-img"
                                                alt="<?= e($produk['nama_produk']) ?>">

                                        <?php else: ?>

                                            <div class="no-image">

                                                <i class="bi bi-image"></i>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <div class="fw-semibold">

                                                <?= e(
                                                    $produk['nama_produk']
                                                ) ?>

                                            </div>

                                            <small class="text-muted">

                                                ID:
                                                <?= (int)$produk['id'] ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- KATEGORI -->

                                <td>

                                    <?= e(
                                        $produk['nama_kategori']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <!-- HARGA -->

                                <td>

                                    <?= rupiah(
                                        $produk['harga']
                                    ) ?>

                                    <small class="text-muted">

                                        /
                                        <?= e(
                                            $produk['satuan']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- STOK -->

                                <td>

                                    <strong>

                                        <?= e(
                                            rtrim(
                                                rtrim(
                                                    number_format(
                                                        (float)$produk['stok'],
                                                        2,
                                                        ',',
                                                        '.'
                                                    ),
                                                    '0'
                                                ),
                                                ','
                                            )
                                        ) ?>

                                    </strong>

                                    <small class="text-muted">

                                        <?= e(
                                            $produk['satuan']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?= badgeStatusProduk(
                                        $produk['status']
                                    ) ?>

                                </td>


                                <!-- AKSI -->

                                <td class="text-center">

                                    <div class="d-flex
                                                justify-content-center
                                                gap-1">

                                        <a
                                            href="edit_produk.php?id=<?= (int)$produk['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit">

                                            <i class="bi bi-pencil"></i>

                                        </a>


                                        <a
                                            href="hapus_produk.php?id=<?= (int)$produk['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus"
                                            onclick="return confirm(
                                                'Yakin ingin menghapus produk ini?'
                                            );">

                                            <i class="bi bi-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5 text-muted">

                                <i
                                    class="bi bi-box-seam fs-1 d-block mb-2">
                                </i>

                                Belum ada produk.

                                <div class="mt-3">

                                    <a
                                        href="tambah_produk.php"
                                        class="btn btn-success">

                                        <i class="bi bi-plus-lg"></i>

                                        Tambah Produk Pertama

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <div class="mt-3">

        <a
            href="index.php"
            class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left"></i>

            Kembali ke Dashboard

        </a>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>