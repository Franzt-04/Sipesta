<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

$keyword = trim($_GET['keyword'] ?? "");

/*
|--------------------------------------------------------------------------
| Proses Tambah Kategori
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | TAMBAH
    |--------------------------------------------------------------------------
    */

    if ($aksi === 'tambah') {

        $nama = trim($_POST['nama_kategori'] ?? '');

        if ($nama === '') {
            header("Location: kategori.php?error=Nama kategori wajib diisi");
            exit;
        }

        $stmt = $conn->prepare("
            SELECT id
            FROM kategori
            WHERE nama_kategori = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $nama);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {

            header(
                "Location: kategori.php?error=Kategori sudah tersedia"
            );

            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO kategori (nama_kategori)
            VALUES (?)
        ");

        $stmt->bind_param("s", $nama);

        if ($stmt->execute()) {

            header(
                "Location: kategori.php?success=Kategori berhasil ditambahkan"
            );

        } else {

            header(
                "Location: kategori.php?error=Gagal menambahkan kategori"
            );
        }

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    if ($aksi === 'edit') {

        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');

        if ($id <= 0 || $nama === '') {

            header(
                "Location: kategori.php?error=Data kategori tidak valid"
            );

            exit;
        }

        /*
         * Cek duplikasi
         */

        $stmt = $conn->prepare("
            SELECT id
            FROM kategori
            WHERE nama_kategori = ?
              AND id != ?
            LIMIT 1
        ");

        $stmt->bind_param("si", $nama, $id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {

            header(
                "Location: kategori.php?error=Nama kategori sudah digunakan"
            );

            exit;
        }

        /*
         * Update
         */

        $stmt = $conn->prepare("
            UPDATE kategori
            SET nama_kategori = ?
            WHERE id = ?
        ");

        $stmt->bind_param("si", $nama, $id);

        if ($stmt->execute()) {

            header(
                "Location: kategori.php?success=Kategori berhasil diperbarui"
            );

        } else {

            header(
                "Location: kategori.php?error=Gagal memperbarui kategori"
            );
        }

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Hapus Kategori
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    if ($id <= 0) {

        header(
            "Location: kategori.php?error=ID kategori tidak valid"
        );

        exit;
    }

    /*
     * Cek apakah kategori masih digunakan produk
     */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS jumlah
        FROM produk
        WHERE kategori_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $jumlahProduk = (int)($data['jumlah'] ?? 0);

    if ($jumlahProduk > 0) {

        header(
            "Location: kategori.php?error=Kategori tidak dapat dihapus karena masih digunakan oleh {$jumlahProduk} produk"
        );

        exit;
    }

    /*
     * Hapus kategori
     */

    $stmt = $conn->prepare("
        DELETE FROM kategori
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        header(
            "Location: kategori.php?success=Kategori berhasil dihapus"
        );

    } else {

        header(
            "Location: kategori.php?error=Gagal menghapus kategori"
        );
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| Query Data Kategori
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.id,
        k.nama_kategori,
        COUNT(p.id) AS jumlah_produk
    FROM kategori k
    LEFT JOIN produk p
        ON p.kategori_id = k.id
";

$params = [];
$types = "";

if ($keyword !== "") {

    $sql .= "
        WHERE k.nama_kategori LIKE ?
    ";

    $search = "%" . $keyword . "%";

    $params[] = $search;
    $types .= "s";
}

$sql .= "
    GROUP BY
        k.id,
        k.nama_kategori
    ORDER BY
        k.nama_kategori ASC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

$totalKategori = 0;
$totalProduk = 0;

$statKategori = $conn->query("
    SELECT COUNT(*) AS total
    FROM kategori
");

if ($statKategori) {

    $data = $statKategori->fetch_assoc();

    $totalKategori = (int)($data['total'] ?? 0);
}


$statProduk = $conn->query("
    SELECT COUNT(*) AS total
    FROM produk
");

if ($statProduk) {

    $data = $statProduk->fetch_assoc();

    $totalProduk = (int)($data['total'] ?? 0);
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

    <title>Kategori - SIPESTA</title>

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


            <a href="kategori.php" class="active">

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

                        <i class="fa-solid fa-layer-group"></i>

                        Kategori Produk

                    </h2>

                    <p class="text-muted mb-0">

                        Kelola kategori produk SIPESTA.

                    </p>

                </div>


                <button
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambah"
                >

                    <i class="fa-solid fa-plus"></i>

                    Tambah Kategori

                </button>

            </div>


            <!-- PESAN -->

            <?php if (!empty($_GET['success'])): ?>

                <div class="alert alert-success alert-dismissible fade show">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= e($_GET['success']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <?php if (!empty($_GET['error'])): ?>

                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= e($_GET['error']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- STATISTIK -->

            <div class="row g-3 mb-4">


                <div class="col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Total Kategori
                                    </small>

                                    <h3 class="mb-0">
                                        <?= $totalKategori ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-layer-group fs-2 text-primary"></i>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="card stat-card shadow-sm">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <small class="text-muted">
                                        Total Produk
                                    </small>

                                    <h3 class="mb-0">
                                        <?= $totalProduk ?>
                                    </h3>

                                </div>

                                <i class="fa-solid fa-box fs-2 text-success"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <form method="GET">

                        <div class="row g-2">

                            <div class="col-md-10">

                                <input
                                    type="text"
                                    name="keyword"
                                    class="form-control"
                                    placeholder="Cari nama kategori..."
                                    value="<?= e($keyword) ?>"
                                >

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


            <!-- TABLE -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <strong>

                        <i class="fa-solid fa-table"></i>

                        Daftar Kategori

                    </strong>

                </div>


                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered mb-0">

                            <thead class="table-dark">

                                <tr>

                                    <th width="80">
                                        No
                                    </th>

                                    <th>
                                        Nama Kategori
                                    </th>

                                    <th width="180">
                                        Jumlah Produk
                                    </th>

                                    <th width="180">
                                        Aksi
                                    </th>

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

                                                <i class="fa-solid fa-folder text-warning me-2"></i>

                                                <?= e($row['nama_kategori']) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span class="badge bg-primary">

                                                <?= (int)$row['jumlah_produk'] ?>

                                                Produk

                                            </span>

                                        </td>


                                        <td>


                                            <!-- EDIT -->

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEdit<?= (int)$row['id'] ?>"
                                            >

                                                <i class="fa-solid fa-pen"></i>

                                            </button>


                                            <!-- HAPUS -->

                                            <?php if ((int)$row['jumlah_produk'] === 0): ?>

                                                <a
                                                    href="kategori.php?hapus=<?= (int)$row['id'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                                                >

                                                    <i class="fa-solid fa-trash"></i>

                                                </a>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-secondary"
                                                    disabled
                                                    title="Kategori masih digunakan produk"
                                                >

                                                    <i class="fa-solid fa-lock"></i>

                                                </button>

                                            <?php endif; ?>

                                        </td>

                                    </tr>


                                    <!-- MODAL EDIT -->

                                    <div
                                        class="modal fade"
                                        id="modalEdit<?= (int)$row['id'] ?>"
                                        tabindex="-1"
                                    >

                                        <div class="modal-dialog">

                                            <div class="modal-content">

                                                <form method="POST">

                                                    <div class="modal-header">

                                                        <h5 class="modal-title">

                                                            <i class="fa-solid fa-pen"></i>

                                                            Edit Kategori

                                                        </h5>

                                                        <button
                                                            type="button"
                                                            class="btn-close"
                                                            data-bs-dismiss="modal"
                                                        ></button>

                                                    </div>


                                                    <div class="modal-body">

                                                        <input
                                                            type="hidden"
                                                            name="aksi"
                                                            value="edit"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int)$row['id'] ?>"
                                                        >


                                                        <div class="mb-3">

                                                            <label class="form-label">

                                                                Nama Kategori

                                                            </label>

                                                            <input
                                                                type="text"
                                                                name="nama_kategori"
                                                                class="form-control"
                                                                value="<?= e($row['nama_kategori']) ?>"
                                                                required
                                                            >

                                                        </div>

                                                    </div>


                                                    <div class="modal-footer">

                                                        <button
                                                            type="button"
                                                            class="btn btn-secondary"
                                                            data-bs-dismiss="modal"
                                                        >
                                                            Batal
                                                        </button>

                                                        <button
                                                            type="submit"
                                                            class="btn btn-warning"
                                                        >

                                                            <i class="fa-solid fa-save"></i>

                                                            Simpan

                                                        </button>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                    </div>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center py-5"
                                    >

                                        <i class="fa-solid fa-folder-open fs-1 text-muted"></i>

                                        <p class="mt-3 mb-0">

                                            Belum ada kategori.

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


<!-- MODAL TAMBAH -->

<div
    class="modal fade"
    id="modalTambah"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-plus"></i>

                        Tambah Kategori

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="aksi"
                        value="tambah"
                    >


                    <div class="mb-3">

                        <label class="form-label">

                            Nama Kategori

                        </label>

                        <input
                            type="text"
                            name="nama_kategori"
                            class="form-control"
                            placeholder="Contoh: Ikan Nila"
                            required
                        >

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-save"></i>

                        Simpan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>