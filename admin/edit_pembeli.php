<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");

$pembeli_id = (int)($_GET['id'] ?? 0);

if ($pembeli_id <= 0) {
    header("Location: pembeli.php?error=" . urlencode("ID pembeli tidak valid."));
    exit;
}

// ======================================================
// AMBIL DATA PEMBELI
// ======================================================

$stmt = $conn->prepare("
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
        u.role,
        u.status

    FROM pembeli p

    INNER JOIN users u
        ON u.id = p.user_id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->bind_param("i", $pembeli_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pembeli.php?error=" . urlencode("Data pembeli tidak ditemukan."));
    exit;
}

$data = $result->fetch_assoc();

$user_id = (int)$data['user_id'];

$error = "";


// ======================================================
// PROSES UPDATE
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $nama = trim($_POST['nama'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $lama_usaha = trim($_POST['lama_usaha'] ?? '');

    $status = trim($_POST['status'] ?? 'aktif');


    // ==================================================
    // VALIDASI
    // ==================================================

    if ($username === '') {

        $error = "Username wajib diisi.";

    } elseif ($nama === '') {

        $error = "Nama pembeli wajib diisi.";

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

    } elseif ($lama_usaha === '' || !is_numeric($lama_usaha) || $lama_usaha < 0) {

        $error = "Lama usaha harus berupa angka dan tidak boleh negatif.";

    } elseif (!in_array(
        $status,
        ['aktif', 'nonaktif'],
        true
    )) {

        $error = "Status akun tidak valid.";

    } elseif ($password !== '' && strlen($password) < 6) {

        $error = "Password baru minimal 6 karakter.";
    }


    // ==================================================
    // SIMPAN
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
                AND id != ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "si",
                $username,
                $user_id
            );

            $stmt->execute();

            $usernameResult = $stmt->get_result();

            if ($usernameResult->num_rows > 0) {

                throw new Exception(
                    "Username sudah digunakan oleh pengguna lain."
                );
            }


            // ==========================================
            // UPDATE USERS
            // ==========================================

            if ($password !== '') {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        password = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "sssi",
                    $username,
                    $passwordHash,
                    $status,
                    $user_id
                );

            } else {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssi",
                    $username,
                    $status,
                    $user_id
                );
            }


            if (!$stmt->execute()) {

                throw new Exception(
                    "Gagal memperbarui akun pembeli."
                );
            }


            // ==========================================
            // UPDATE PEMBELI
            // ==========================================

            $stmt = $conn->prepare("
                UPDATE pembeli
                SET
                    nama = ?,
                    tanggal_lahir = ?,
                    no_hp = ?,
                    alamat = ?,
                    nama_toko = ?,
                    jenis_kelamin = ?,
                    lama_usaha = ?
                WHERE id = ?
            ");

            $lama_usaha_value = (int)$lama_usaha;

            $stmt->bind_param(
                "ssssssii",
                $nama,
                $tanggal_lahir,
                $no_hp,
                $alamat,
                $nama_toko,
                $jenis_kelamin,
                $lama_usaha_value,
                $pembeli_id
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Gagal memperbarui data pembeli."
                );
            }


            // ==========================================
            // COMMIT
            // ==========================================

            $conn->commit();

            header(
                "Location: pembeli.php?success=" .
                urlencode("Data pembeli berhasil diperbarui.")
            );

            exit;


        } catch (Exception $e) {

            try {
                $conn->rollback();
            } catch (Exception $rollbackError) {
                // Abaikan
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

    <title>Edit Pembeli - SIPESTA</title>

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

        .form-label {
            font-weight: 600;
        }

    </style>

</head>

<body>


<nav class="navbar navbar-dark bg-success shadow-sm">

    <div class="container-fluid">

        <a
            href="index.php"
            class="navbar-brand"
        >

            <i class="bi bi-shop"></i>

            SIPESTA Admin

        </a>


        <div>

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


<div class="container py-4">

    <div class="mb-4">

        <a
            href="pembeli.php"
            class="btn btn-outline-secondary btn-sm mb-3"
        >

            <i class="bi bi-arrow-left"></i>

            Kembali

        </a>


        <h3 class="fw-bold mb-1">

            <i class="bi bi-pencil-square text-success"></i>

            Edit Pembeli

        </h3>


        <p class="text-muted mb-0">

            Perbarui akun dan informasi pembeli.

        </p>

    </div>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <!-- ==================================
                             AKUN
                        ================================== -->

                        <h5 class="fw-bold mb-3">

                            <i class="bi bi-person-circle text-success"></i>

                            Akun Login

                        </h5>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">

                                    Username
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="username"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['username']
                                        ?? $data['username']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Password Baru

                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Kosongkan jika tidak diubah"
                                    minlength="6"
                                >

                            </div>


                            <!-- ==================================
                                 DATA PRIBADI
                            ================================== -->

                            <div class="col-12 mt-4">

                                <hr>

                                <h5 class="fw-bold mb-3">

                                    <i class="bi bi-person text-success"></i>

                                    Data Pembeli

                                </h5>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Nama Lengkap
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="nama"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['nama']
                                        ?? $data['nama']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Tanggal Lahir
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="date"
                                    name="tanggal_lahir"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['tanggal_lahir']
                                        ?? $data['tanggal_lahir']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    No. HP
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="no_hp"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['no_hp']
                                        ?? $data['no_hp']
                                    ) ?>"
                                    placeholder="08xxxxxxxxxx"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Jenis Kelamin
                                    <span class="text-danger">*</span>

                                </label>

                                <?php
                                $jkValue =
                                    $_POST['jenis_kelamin']
                                    ?? $data['jenis_kelamin'];
                                ?>

                                <select
                                    name="jenis_kelamin"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih --
                                    </option>

                                    <option
                                        value="Laki-laki"
                                        <?= $jkValue === 'Laki-laki'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Laki-laki
                                    </option>

                                    <option
                                        value="Perempuan"
                                        <?= $jkValue === 'Perempuan'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Perempuan
                                    </option>

                                </select>

                            </div>


                            <div class="col-12">

                                <label class="form-label">

                                    Alamat
                                    <span class="text-danger">*</span>

                                </label>

                                <textarea
                                    name="alamat"
                                    class="form-control"
                                    rows="3"
                                    required
                                ><?= e(
                                    $_POST['alamat']
                                    ?? $data['alamat']
                                ) ?></textarea>

                            </div>


                            <!-- ==================================
                                 DATA USAHA
                            ================================== -->

                            <div class="col-12 mt-4">

                                <hr>

                                <h5 class="fw-bold mb-3">

                                    <i class="bi bi-shop text-success"></i>

                                    Data Toko / Usaha

                                </h5>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Nama Toko
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="nama_toko"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['nama_toko']
                                        ?? $data['nama_toko']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Lama Usaha (Tahun)
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="number"
                                    name="lama_usaha"
                                    class="form-control"
                                    min="0"
                                    value="<?= e(
                                        $_POST['lama_usaha']
                                        ?? $data['lama_usaha']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- ==================================
                                 STATUS
                            ================================== -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Status Akun

                                </label>

                                <?php
                                $statusValue =
                                    $_POST['status']
                                    ?? $data['status'];
                                ?>

                                <select
                                    name="status"
                                    class="form-select"
                                >

                                    <option
                                        value="aktif"
                                        <?= $statusValue === 'aktif'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="nonaktif"
                                        <?= $statusValue === 'nonaktif'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Nonaktif
                                    </option>

                                </select>

                                <small class="text-muted">

                                    Pembeli nonaktif tidak dapat login.

                                </small>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">

                                    Role

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Pembeli"
                                    readonly
                                >

                            </div>


                            <!-- ==================================
                                 BUTTON
                            ================================== -->

                            <div class="col-12 mt-4">

                                <hr>

                                <div class="d-flex
                                            justify-content-end
                                            gap-2">

                                    <a
                                        href="pembeli.php"
                                        class="btn btn-secondary"
                                    >

                                        <i class="bi bi-x-circle"></i>

                                        Batal

                                    </a>


                                    <button
                                        type="submit"
                                        class="btn btn-success px-4"
                                    >

                                        <i class="bi bi-save"></i>

                                        Simpan Perubahan

                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>