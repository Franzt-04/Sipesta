<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");


// ======================================================
// AMBIL ID PENJUAL
// ======================================================

$penjual_id = (int)($_GET['id'] ?? 0);

if ($penjual_id <= 0) {
    header("Location: penjual.php?error=" . urlencode("ID penjual tidak valid."));
    exit;
}


// ======================================================
// AMBIL DATA PENJUAL
// ======================================================

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.user_id,
        p.nama_usaha,
        p.alamat,

        u.username,
        u.role,
        u.status

    FROM penjual p

    INNER JOIN users u
        ON u.id = p.user_id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->bind_param("i", $penjual_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: penjual.php?error=" . urlencode("Data penjual tidak ditemukan."));
    exit;
}

$data = $result->fetch_assoc();

$user_id = (int)$data['user_id'];


// ======================================================
// PROSES UPDATE
// ======================================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_usaha = trim($_POST['nama_usaha'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');


    // ==================================================
    // VALIDASI
    // ==================================================

    if ($username === '') {

        $error = "Username wajib diisi.";

    } elseif ($nama_usaha === '') {

        $error = "Nama usaha wajib diisi.";

    } elseif ($alamat === '') {

        $error = "Alamat usaha wajib diisi.";

    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {

        $error = "Status akun tidak valid.";

    } elseif ($password !== '' && strlen($password) < 6) {

        $error = "Password baru minimal 6 karakter.";
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
                AND id != ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "si",
                $username,
                $user_id
            );

            $stmt->execute();

            $username_result = $stmt->get_result();

            if ($username_result->num_rows > 0) {

                throw new Exception(
                    "Username sudah digunakan oleh pengguna lain."
                );
            }


            // ==========================================
            // UPDATE USERS
            // ==========================================

            if ($password !== '') {

                $password_hash = password_hash(
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
                    $password_hash,
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
                    "Gagal memperbarui akun penjual."
                );
            }


            // ==========================================
            // UPDATE PENJUAL
            // ==========================================

            $stmt = $conn->prepare("
                UPDATE penjual
                SET
                    nama_usaha = ?,
                    alamat = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssi",
                $nama_usaha,
                $alamat,
                $penjual_id
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Gagal memperbarui data usaha penjual."
                );
            }


            // ==========================================
            // COMMIT
            // ==========================================

            $conn->commit();


            header(
                "Location: penjual.php?success=" .
                urlencode("Data penjual berhasil diperbarui.")
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

    <title>Edit Penjual - SIPESTA</title>

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


<!-- ==================================================
     NAVBAR
================================================== -->

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


    <!-- ==================================================
         HEADER
    ================================================== -->

    <div class="mb-4">

        <a
            href="penjual.php"
            class="btn btn-outline-secondary btn-sm mb-3"
        >

            <i class="bi bi-arrow-left"></i>

            Kembali

        </a>


        <h3 class="fw-bold mb-1">

            <i class="bi bi-pencil-square text-success"></i>

            Edit Penjual

        </h3>


        <p class="text-muted mb-0">

            Perbarui akun dan informasi usaha penjual.

        </p>

    </div>


    <!-- ==================================================
         ERROR
    ================================================== -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-body p-4">


                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <!-- ==================================
                             AKUN LOGIN
                        ================================== -->

                        <h5 class="fw-bold mb-3">

                            <i class="bi bi-person-circle text-success"></i>

                            Akun Login

                        </h5>


                        <div class="row g-3">


                            <!-- USERNAME -->

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


                            <!-- PASSWORD -->

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

                                <small class="text-muted">

                                    Isi hanya jika ingin mengganti
                                    password.

                                </small>

                            </div>


                            <!-- ==================================
                                 DATA USAHA
                            ================================== -->

                            <div class="col-12 mt-4">

                                <hr>

                                <h5 class="fw-bold mb-3">

                                    <i class="bi bi-shop text-success"></i>

                                    Data Usaha

                                </h5>

                            </div>


                            <!-- NAMA USAHA -->

                            <div class="col-12">

                                <label class="form-label">

                                    Nama Usaha
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="nama_usaha"
                                    class="form-control"
                                    value="<?= e(
                                        $_POST['nama_usaha']
                                        ?? $data['nama_usaha']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- ALAMAT -->

                            <div class="col-12">

                                <label class="form-label">

                                    Alamat Usaha
                                    <span class="text-danger">*</span>

                                </label>

                                <textarea
                                    name="alamat"
                                    class="form-control"
                                    rows="4"
                                    required
                                ><?= e(
                                    $_POST['alamat']
                                    ?? $data['alamat']
                                ) ?></textarea>

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

                                    Akun nonaktif tidak dapat login.

                                </small>

                            </div>


                            <!-- ROLE -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Role

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Penjual"
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
                                        href="penjual.php"
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