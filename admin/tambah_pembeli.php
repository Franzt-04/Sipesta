<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("admin");

$error = "";


// ======================================================
// PROSES TAMBAH PEMBELI
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==================================================
    // DATA AKUN
    // ==================================================

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ==================================================
    // DATA PEMBELI
    // ==================================================

    $nama = trim($_POST['nama'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $lama_usaha = trim($_POST['lama_usaha'] ?? '');

    // ==================================================
    // VALIDASI
    // ==================================================

    if ($username === '') {

        $error = "Username wajib diisi.";

    } elseif (strlen($username) < 4) {

        $error = "Username minimal 4 karakter.";

    } elseif ($password === '') {

        $error = "Password wajib diisi.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($nama === '') {

        $error = "Nama lengkap wajib diisi.";

    } elseif ($tanggal_lahir === '') {

        $error = "Tanggal lahir wajib diisi.";

    } elseif ($no_hp === '') {

        $error = "Nomor HP wajib diisi.";

    } elseif ($alamat === '') {

        $error = "Alamat wajib diisi.";

    } elseif ($nama_toko === '') {

        $error = "Nama toko wajib diisi.";

    } elseif (!in_array(
        $jenis_kelamin,
        ['Laki-laki', 'Perempuan'],
        true
    )) {

        $error = "Jenis kelamin tidak valid.";

    } elseif (
        $lama_usaha === '' ||
        !is_numeric($lama_usaha) ||
        $lama_usaha < 0
    ) {

        $error = "Lama usaha harus berupa angka dan tidak boleh negatif.";
    }


    // ==================================================
    // JIKA VALID
    // ==================================================

    if ($error === '') {

        try {

            $conn->begin_transaction();


            // ==========================================
            // CEK USERNAME
            // ==========================================

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            if (!$stmt) {
                throw new Exception(
                    "Query pengecekan username gagal: " .
                    $conn->error
                );
            }

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $resultUsername = $stmt->get_result();

            if ($resultUsername->num_rows > 0) {

                throw new Exception(
                    "Username sudah digunakan. Silakan gunakan username lain."
                );
            }


            // ==========================================
            // HASH PASSWORD
            // ==========================================

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // ==========================================
            // INSERT USERS
            // ==========================================

            $role = "pembeli";
            $status = "aktif";

            $stmt = $conn->prepare("
                INSERT INTO users
                    (
                        username,
                        password,
                        role,
                        status
                    )
                VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
            ");

            if (!$stmt) {
                throw new Exception(
                    "Query akun gagal: " .
                    $conn->error
                );
            }

            $stmt->bind_param(
                "ssss",
                $username,
                $passwordHash,
                $role,
                $status
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "Gagal membuat akun pembeli: " .
                    $stmt->error
                );
            }


            // ==========================================
            // AMBIL USER ID
            // ==========================================

            $user_id = $conn->insert_id;


            if ($user_id <= 0) {

                throw new Exception(
                    "ID akun pembeli tidak berhasil dibuat."
                );
            }


            // ==========================================
            // INSERT DATA PEMBELI
            // ==========================================

            $lama_usaha_int = (int)$lama_usaha;

            $stmt = $conn->prepare("
                INSERT INTO pembeli
                    (
                        user_id,
                        nama,
                        tanggal_lahir,
                        no_hp,
                        alamat,
                        nama_toko,
                        jenis_kelamin,
                        lama_usaha
                    )
                VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
            ");

            if (!$stmt) {
                throw new Exception(
                    "Query data pembeli gagal: " .
                    $conn->error
                );
            }

            $stmt->bind_param(
                "issssssi",
                $user_id,
                $nama,
                $tanggal_lahir,
                $no_hp,
                $alamat,
                $nama_toko,
                $jenis_kelamin,
                $lama_usaha_int
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Gagal menyimpan data pembeli: " .
                    $stmt->error
                );
            }


            // ==========================================
            // COMMIT
            // ==========================================

            $conn->commit();


            // ==========================================
            // REDIRECT
            // ==========================================

            header(
                "Location: pembeli.php?success=" .
                urlencode("Pembeli berhasil ditambahkan.")
            );

            exit;


        } catch (Exception $e) {

            try {
                $conn->rollback();
            } catch (Exception $rollbackError) {
                // Abaikan error rollback
            }

            $error = $e->getMessage();
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Tambah Pembeli - SIPESTA
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
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

        .navbar-brand {
            font-weight: 700;
        }

        .card {
            border: 0;
            border-radius: 16px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 11px 13px;
        }

        textarea.form-control {
            min-height: 100px;
        }

        .form-label {
            font-weight: 600;
        }

        .section-title {
            font-weight: 700;
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
            href="index.php"
            class="navbar-brand"
        >

            <i class="fa-solid fa-store me-1"></i>

            SIPESTA Admin

        </a>


        <div>

            <a
                href="index.php"
                class="btn btn-light btn-sm me-2"
            >

                <i class="fa-solid fa-gauge me-1"></i>

                Dashboard

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-light btn-sm"
            >

                <i class="fa-solid fa-right-from-bracket me-1"></i>

                Logout

            </a>

        </div>

    </div>

</nav>


<!-- ==================================================
     CONTENT
================================================== -->

<div class="container py-4">


    <!-- ==================================================
         HEADER
    ================================================== -->

    <div class="mb-4">


        <a
            href="pembeli.php"
            class="btn btn-outline-secondary btn-sm mb-3"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Kembali

        </a>


        <h3 class="fw-bold mb-1">

            <i class="fa-solid fa-user-plus text-success me-1"></i>

            Tambah Pembeli

        </h3>


        <p class="text-muted mb-0">

            Tambahkan akun dan data pembeli baru ke SIPESTA.

        </p>

    </div>


    <!-- ==================================================
         ERROR
    ================================================== -->

    <?php if ($error !== ''): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            <?= e($error) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         FORM
    ================================================== -->

    <div class="row justify-content-center">

        <div class="col-lg-9">


            <div class="card shadow-sm">

                <div class="card-body p-4">


                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <!-- ==================================
                             AKUN LOGIN
                        ================================== -->

                        <div class="mb-4">


                            <h5 class="section-title mb-3">

                                <i class="fa-solid fa-lock text-success me-2"></i>

                                Akun Login

                            </h5>


                            <div class="row g-3">


                                <!-- USERNAME -->

                                <div class="col-md-6">

                                    <label
                                        for="username"
                                        class="form-label"
                                    >

                                        Username
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="text"
                                        id="username"
                                        name="username"
                                        class="form-control"
                                        minlength="4"
                                        maxlength="100"
                                        value="<?= e(
                                            $_POST['username'] ?? ''
                                        ) ?>"
                                        placeholder="Contoh: farhan123"
                                        required
                                    >


                                    <small class="text-muted">

                                        Minimal 4 karakter.

                                    </small>

                                </div>


                                <!-- PASSWORD -->

                                <div class="col-md-6">

                                    <label
                                        for="password"
                                        class="form-label"
                                    >

                                        Password
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control"
                                        minlength="6"
                                        maxlength="255"
                                        placeholder="Masukkan password"
                                        required
                                    >


                                    <small class="text-muted">

                                        Minimal 6 karakter.

                                    </small>

                                </div>


                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                             DATA PRIBADI
                        ================================== -->

                        <div class="my-4">


                            <h5 class="section-title mb-3">

                                <i class="fa-solid fa-user text-success me-2"></i>

                                Data Pembeli

                            </h5>


                            <div class="row g-3">


                                <!-- NAMA -->

                                <div class="col-md-6">

                                    <label
                                        for="nama"
                                        class="form-label"
                                    >

                                        Nama Lengkap
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="text"
                                        id="nama"
                                        name="nama"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= e(
                                            $_POST['nama'] ?? ''
                                        ) ?>"
                                        placeholder="Masukkan nama lengkap"
                                        required
                                    >

                                </div>


                                <!-- TANGGAL LAHIR -->

                                <div class="col-md-6">

                                    <label
                                        for="tanggal_lahir"
                                        class="form-label"
                                    >

                                        Tanggal Lahir
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="date"
                                        id="tanggal_lahir"
                                        name="tanggal_lahir"
                                        class="form-control"
                                        value="<?= e(
                                            $_POST['tanggal_lahir'] ?? ''
                                        ) ?>"
                                        required
                                    >

                                </div>


                                <!-- NO HP -->

                                <div class="col-md-6">

                                    <label
                                        for="no_hp"
                                        class="form-label"
                                    >

                                        No. HP
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="text"
                                        id="no_hp"
                                        name="no_hp"
                                        class="form-control"
                                        maxlength="30"
                                        value="<?= e(
                                            $_POST['no_hp'] ?? ''
                                        ) ?>"
                                        placeholder="08xxxxxxxxxx"
                                        required
                                    >

                                </div>


                                <!-- JENIS KELAMIN -->

                                <div class="col-md-6">

                                    <label
                                        for="jenis_kelamin"
                                        class="form-label"
                                    >

                                        Jenis Kelamin
                                        <span class="text-danger">*</span>

                                    </label>


                                    <select
                                        id="jenis_kelamin"
                                        name="jenis_kelamin"
                                        class="form-select"
                                        required
                                    >

                                        <option value="">

                                            -- Pilih Jenis Kelamin --

                                        </option>


                                        <option
                                            value="Laki-laki"
                                            <?= (
                                                ($_POST['jenis_kelamin'] ?? '')
                                                === 'Laki-laki'
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >

                                            Laki-laki

                                        </option>


                                        <option
                                            value="Perempuan"
                                            <?= (
                                                ($_POST['jenis_kelamin'] ?? '')
                                                === 'Perempuan'
                                            )
                                                ? 'selected'
                                                : '' ?>
                                        >

                                            Perempuan

                                        </option>

                                    </select>

                                </div>


                                <!-- ALAMAT -->

                                <div class="col-12">

                                    <label
                                        for="alamat"
                                        class="form-label"
                                    >

                                        Alamat
                                        <span class="text-danger">*</span>

                                    </label>


                                    <textarea
                                        id="alamat"
                                        name="alamat"
                                        class="form-control"
                                        maxlength="500"
                                        rows="3"
                                        placeholder="Masukkan alamat lengkap"
                                        required
                                    ><?= e(
                                        $_POST['alamat'] ?? ''
                                    ) ?></textarea>

                                </div>


                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                             DATA TOKO
                        ================================== -->

                        <div class="my-4">


                            <h5 class="section-title mb-3">

                                <i class="fa-solid fa-shop text-success me-2"></i>

                                Data Toko / Usaha

                            </h5>


                            <div class="row g-3">


                                <!-- NAMA TOKO -->

                                <div class="col-md-6">

                                    <label
                                        for="nama_toko"
                                        class="form-label"
                                    >

                                        Nama Toko
                                        <span class="text-danger">*</span>

                                    </label>


                                    <input
                                        type="text"
                                        id="nama_toko"
                                        name="nama_toko"
                                        class="form-control"
                                        maxlength="150"
                                        value="<?= e(
                                            $_POST['nama_toko'] ?? ''
                                        ) ?>"
                                        placeholder="Contoh: Toko Berkah"
                                        required
                                    >

                                </div>


                                <!-- LAMA USAHA -->

                                <div class="col-md-6">

                                    <label
                                        for="lama_usaha"
                                        class="form-label"
                                    >

                                        Lama Usaha
                                        <span class="text-danger">*</span>

                                    </label>


                                    <div class="input-group">

                                        <input
                                            type="number"
                                            id="lama_usaha"
                                            name="lama_usaha"
                                            class="form-control"
                                            min="0"
                                            max="100"
                                            value="<?= e(
                                                $_POST['lama_usaha'] ?? ''
                                            ) ?>"
                                            placeholder="0"
                                            required
                                        >


                                        <span class="input-group-text">

                                            Tahun

                                        </span>

                                    </div>

                                </div>


                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                             INFO
                        ================================== -->

                        <div class="alert alert-info">


                            <i class="fa-solid fa-circle-info me-2"></i>


                            <strong>Informasi:</strong>

                            Akun pembeli baru akan otomatis dibuat
                            dengan status

                            <strong>Aktif</strong>.


                        </div>


                        <!-- ==================================
                             BUTTON
                        ================================== -->

                        <div class="d-flex
                                    justify-content-end
                                    gap-2
                                    mt-4">


                            <a
                                href="pembeli.php"
                                class="btn btn-secondary"
                            >

                                <i class="fa-solid fa-xmark me-1"></i>

                                Batal

                            </a>


                            <button
                                type="reset"
                                class="btn btn-outline-warning"
                            >

                                <i class="fa-solid fa-rotate-left me-1"></i>

                                Reset

                            </button>


                            <button
                                type="submit"
                                class="btn btn-success px-4"
                            >

                                <i class="fa-solid fa-user-plus me-1"></i>

                                Simpan Pembeli

                            </button>


                        </div>


                    </form>


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