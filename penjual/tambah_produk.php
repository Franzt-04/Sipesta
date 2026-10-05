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
    SELECT
        id,
        nama_usaha,
        alamat
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
| Ambil kategori
|--------------------------------------------------------------------------
*/
$kategoriResult = $conn->query("
    SELECT
        id,
        nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

if (!$kategoriResult) {
    die("Gagal mengambil data kategori.");
}


/*
|--------------------------------------------------------------------------
| Proses form
|--------------------------------------------------------------------------
*/
$error = '';

$nama_produk = '';
$kategori_id = '';
$deskripsi = '';
$harga = '';
$satuan = '';
$stok = '';
$status = 'tersedia';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Ambil input
    |--------------------------------------------------------------------------
    */

    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = trim($_POST['harga'] ?? '');
    $satuan = trim($_POST['satuan'] ?? '');
    $stok = trim($_POST['stok'] ?? '');
    $status = trim($_POST['status'] ?? 'tersedia');


    /*
    |--------------------------------------------------------------------------
    | Validasi
    |--------------------------------------------------------------------------
    */

    if ($nama_produk === '') {

        $error = "Nama produk wajib diisi.";

    } elseif ($kategori_id <= 0) {

        $error = "Silakan pilih kategori produk.";

    } elseif ($harga === '' || !is_numeric($harga)) {

        $error = "Harga produk harus berupa angka.";

    } elseif ((float)$harga < 0) {

        $error = "Harga produk tidak boleh negatif.";

    } elseif ($satuan === '') {

        $error = "Satuan produk wajib diisi.";

    } elseif ($stok === '' || !is_numeric($stok)) {

        $error = "Stok harus berupa angka.";

    } elseif ((float)$stok < 0) {

        $error = "Stok tidak boleh negatif.";

    } elseif (!in_array(
        $status,
        ['tersedia', 'habis', 'nonaktif'],
        true
    )) {

        $error = "Status produk tidak valid.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validasi kategori
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmtKategori = $conn->prepare("
            SELECT id
            FROM kategori
            WHERE id = ?
            LIMIT 1
        ");

        $stmtKategori->bind_param(
            "i",
            $kategori_id
        );

        $stmtKategori->execute();

        $kategori = $stmtKategori
            ->get_result()
            ->fetch_assoc();

        if (!$kategori) {
            $error = "Kategori yang dipilih tidak ditemukan.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Tentukan status otomatis berdasarkan stok
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stokFloat = (float)$stok;

        if ($stokFloat <= 0 && $status === 'tersedia') {
            $status = 'habis';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Upload foto
    |--------------------------------------------------------------------------
    */

    $namaFoto = '';


    if (
        $error === ''
        && isset($_FILES['foto'])
        && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {

            $error = "Foto produk gagal diupload.";

        } else {

            $tmpFile = $_FILES['foto']['tmp_name'];

            $namaFileAsli = $_FILES['foto']['name'];

            $ukuranFile = (int)$_FILES['foto']['size'];


            /*
            |--------------------------------------------------------------------------
            | Batas ukuran 2 MB
            |--------------------------------------------------------------------------
            */

            if ($ukuranFile > 2 * 1024 * 1024) {

                $error = "Ukuran foto maksimal 2 MB.";

            }


            /*
            |--------------------------------------------------------------------------
            | Cek MIME
            |--------------------------------------------------------------------------
            */

            if ($error === '') {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                $mime = finfo_file(
                    $finfo,
                    $tmpFile
                );

                finfo_close($finfo);


                $mimeDiizinkan = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                if (!in_array(
                    $mime,
                    $mimeDiizinkan,
                    true
                )) {

                    $error = "Format foto harus JPG, PNG, atau WEBP.";

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Buat folder jika belum ada
            |--------------------------------------------------------------------------
            */

            if ($error === '') {

                $folderUpload = "../uploads/produk/";


                if (!is_dir($folderUpload)) {

                    if (!mkdir(
                        $folderUpload,
                        0755,
                        true
                    )) {

                        $error = "Folder upload produk tidak dapat dibuat.";
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Nama file baru
            |--------------------------------------------------------------------------
            */

            if ($error === '') {

                $extension = strtolower(
                    pathinfo(
                        $namaFileAsli,
                        PATHINFO_EXTENSION
                    )
                );


                $namaFoto =
                    'produk_'
                    . $penjual_id
                    . '_'
                    . date('YmdHis')
                    . '_'
                    . bin2hex(random_bytes(4))
                    . '.'
                    . $extension;


                $targetFile =
                    $folderUpload
                    . $namaFoto;


                if (!move_uploaded_file(
                    $tmpFile,
                    $targetFile
                )) {

                    $error = "Foto produk gagal disimpan.";

                    $namaFoto = '';
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Simpan produk
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $hargaFloat = (float)$harga;
        $stokFloat = (float)$stok;


        $stmtInsert = $conn->prepare("
            INSERT INTO produk (
                penjual_id,
                kategori_id,
                nama_produk,
                deskripsi,
                harga,
                satuan,
                stok,
                foto,
                status,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");


        $stmtInsert->bind_param(
            "iissdssss",
            $penjual_id,
            $kategori_id,
            $nama_produk,
            $deskripsi,
            $hargaFloat,
            $satuan,
            $stokFloat,
            $namaFoto,
            $status
        );


        if ($stmtInsert->execute()) {

            header(
                "Location: produk.php?success="
                . urlencode("Produk berhasil ditambahkan.")
            );

            exit;

        } else {

            /*
            |--------------------------------------------------------------------------
            | Jika database gagal, hapus foto yang sudah terupload
            |--------------------------------------------------------------------------
            */

            if (
                $namaFoto !== ''
                && isset($targetFile)
                && file_exists($targetFile)
            ) {

                unlink($targetFile);
            }


            $error =
                "Gagal menyimpan produk: "
                . $stmtInsert->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Tambah Produk - SIPESTA</title>


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
            border-radius: 18px;
        }

        .form-label {
            font-weight: 600;
        }

        .preview-box {
            width: 180px;
            height: 180px;

            border-radius: 15px;

            background: #f1f3f5;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            color: #adb5bd;
        }

        .preview-box img {
            width: 100%;
            height: 100%;

            object-fit: cover;
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


        <div class="d-flex align-items-center gap-3">

            <span class="text-muted d-none d-md-block">

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


<div class="container py-4">


    <!-- HEADER -->

    <div class="mb-4">

        <h2 class="fw-bold">

            <i class="bi bi-plus-circle text-success"></i>

            Tambah Produk

        </h2>

        <p class="text-muted mb-0">

            Tambahkan produk baru yang akan dijual di SIPESTA.

        </p>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle"></i>

            <?= e($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- FORM -->

    <div class="card shadow-sm">

        <div class="card-body p-4">


            <form
                method="POST"
                enctype="multipart/form-data">


                <div class="row g-4">


                    <!-- DATA PRODUK -->

                    <div class="col-12 col-lg-8">

                        <div class="row g-3">


                            <!-- NAMA -->

                            <div class="col-12">

                                <label class="form-label">

                                    Nama Produk
                                    <span class="text-danger">*</span>

                                </label>


                                <input
                                    type="text"
                                    name="nama_produk"
                                    class="form-control"
                                    placeholder="Contoh: Ikan Nila Segar"
                                    value="<?= e($nama_produk) ?>"
                                    maxlength="150"
                                    required>

                            </div>


                            <!-- KATEGORI -->

                            <div class="col-12 col-md-6">

                                <label class="form-label">

                                    Kategori
                                    <span class="text-danger">*</span>

                                </label>


                                <select
                                    name="kategori_id"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        -- Pilih Kategori --
                                    </option>


                                    <?php while (
                                        $kategori =
                                        $kategoriResult->fetch_assoc()
                                    ): ?>

                                        <option
                                            value="<?= (int)$kategori['id'] ?>"
                                            <?= $kategori_id == $kategori['id']
                                                ? 'selected'
                                                : '' ?>>

                                            <?= e(
                                                $kategori['nama_kategori']
                                            ) ?>

                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>


                            <!-- SATUAN -->

                            <div class="col-12 col-md-6">

                                <label class="form-label">

                                    Satuan
                                    <span class="text-danger">*</span>

                                </label>


                                <select
                                    name="satuan"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        -- Pilih Satuan --
                                    </option>

                                    <option
                                        value="kg"
                                        <?= $satuan === 'kg'
                                            ? 'selected'
                                            : '' ?>>

                                        Kilogram (kg)

                                    </option>

                                    <option
                                        value="ekor"
                                        <?= $satuan === 'ekor'
                                            ? 'selected'
                                            : '' ?>>

                                        Ekor

                                    </option>

                                    <option
                                        value="butir"
                                        <?= $satuan === 'butir'
                                            ? 'selected'
                                            : '' ?>>

                                        Butir

                                    </option>

                                    <option
                                        value="ikat"
                                        <?= $satuan === 'ikat'
                                            ? 'selected'
                                            : '' ?>>

                                        Ikat

                                    </option>

                                    <option
                                        value="karung"
                                        <?= $satuan === 'karung'
                                            ? 'selected'
                                            : '' ?>>

                                        Karung

                                    </option>

                                    <option
                                        value="pack"
                                        <?= $satuan === 'pack'
                                            ? 'selected'
                                            : '' ?>>

                                        Pack

                                    </option>

                                    <option
                                        value="pcs"
                                        <?= $satuan === 'pcs'
                                            ? 'selected'
                                            : '' ?>>

                                        Pcs

                                    </option>

                                </select>

                            </div>


                            <!-- HARGA -->

                            <div class="col-12 col-md-6">

                                <label class="form-label">

                                    Harga
                                    <span class="text-danger">*</span>

                                </label>


                                <div class="input-group">

                                    <span class="input-group-text">
                                        Rp
                                    </span>


                                    <input
                                        type="number"
                                        name="harga"
                                        class="form-control"
                                        placeholder="35000"
                                        value="<?= e($harga) ?>"
                                        min="0"
                                        step="0.01"
                                        required>

                                </div>


                                <small class="text-muted">

                                    Harga per satuan.

                                </small>

                            </div>


                            <!-- STOK -->

                            <div class="col-12 col-md-6">

                                <label class="form-label">

                                    Stok
                                    <span class="text-danger">*</span>

                                </label>


                                <input
                                    type="number"
                                    name="stok"
                                    class="form-control"
                                    placeholder="100"
                                    value="<?= e($stok) ?>"
                                    min="0"
                                    step="0.01"
                                    required>


                                <small class="text-muted">

                                    Jumlah stok yang tersedia.

                                </small>

                            </div>


                            <!-- STATUS -->

                            <div class="col-12 col-md-6">

                                <label class="form-label">

                                    Status

                                </label>


                                <select
                                    name="status"
                                    class="form-select">

                                    <option
                                        value="tersedia"
                                        <?= $status === 'tersedia'
                                            ? 'selected'
                                            : '' ?>>

                                        Tersedia

                                    </option>

                                    <option
                                        value="habis"
                                        <?= $status === 'habis'
                                            ? 'selected'
                                            : '' ?>>

                                        Habis

                                    </option>

                                    <option
                                        value="nonaktif"
                                        <?= $status === 'nonaktif'
                                            ? 'selected'
                                            : '' ?>>

                                        Nonaktif

                                    </option>

                                </select>

                            </div>


                            <!-- DESKRIPSI -->

                            <div class="col-12">

                                <label class="form-label">

                                    Deskripsi Produk

                                </label>


                                <textarea
                                    name="deskripsi"
                                    class="form-control"
                                    rows="5"
                                    maxlength="1000"
                                    placeholder="Jelaskan kondisi, kualitas, ukuran, atau informasi lainnya tentang produk..."><?= e($deskripsi) ?></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- FOTO -->

                    <div class="col-12 col-lg-4">

                        <label class="form-label">

                            Foto Produk

                        </label>


                        <div
                            class="preview-box mb-3"
                            id="previewBox">

                            <i class="bi bi-image fs-1"></i>

                        </div>


                        <input
                            type="file"
                            name="foto"
                            id="foto"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp">


                        <div class="form-text">

                            Format: JPG, PNG, WEBP.<br>

                            Maksimal ukuran: 2 MB.

                        </div>

                    </div>


                </div>


                <hr class="my-4">


                <!-- BUTTON -->

                <div class="d-flex flex-column
                            flex-md-row
                            justify-content-between
                            gap-2">


                    <a
                        href="produk.php"
                        class="btn btn-outline-secondary">

                        <i class="bi bi-arrow-left"></i>

                        Kembali

                    </a>


                    <button
                        type="submit"
                        class="btn btn-success px-4">

                        <i class="bi bi-save"></i>

                        Simpan Produk

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<script>

const fotoInput = document.getElementById('foto');

const previewBox = document.getElementById('previewBox');


fotoInput.addEventListener('change', function () {

    const file = this.files[0];

    if (!file) {

        previewBox.innerHTML =
            '<i class="bi bi-image fs-1"></i>';

        return;
    }


    const reader = new FileReader();


    reader.onload = function (event) {

        previewBox.innerHTML =
            '<img src="' +
            event.target.result +
            '" alt="Preview Foto">';

    };


    reader.readAsDataURL(file);

});

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>