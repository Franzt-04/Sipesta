<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("admin");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_usaha = trim($_POST['nama_usaha'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    // ==================================================
    // VALIDASI
    // ==================================================

    if ($username === '') {
        $error = "Username wajib diisi.";

    } elseif ($password === '') {
        $error = "Password wajib diisi.";

    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";

    } elseif ($nama_usaha === '') {
        $error = "Nama usaha wajib diisi.";

    } elseif ($alamat === '') {
        $error = "Alamat usaha wajib diisi.";
    }

    // ==================================================
    // PROSES SIMPAN
    // ==================================================

    if ($error === '') {

        try {

            $conn->begin_transaction();

            // ------------------------------------------
            // CEK USERNAME
            // ------------------------------------------

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception(
                    "Username sudah digunakan. Silakan gunakan username lain."
                );
            }

            // ------------------------------------------
            // HASH PASSWORD
            // ------------------------------------------

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // ------------------------------------------
            // SIMPAN USERS
            // ------------------------------------------

            $role = "penjual";
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
                (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssss",
                $username,
                $password_hash,
                $role,
                $status
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    "Gagal membuat akun penjual."
                );
            }

            $user_id = $conn->insert_id;

            // ------------------------------------------
            // SIMPAN DATA PENJUAL
            // ------------------------------------------

            $stmt = $conn->prepare("
                INSERT INTO penjual
                (
                    user_id,
                    nama_usaha,
                    alamat
                )
                VALUES
                (?, ?, ?)
            ");

            $stmt->bind_param(
                "iss",
                $user_id,
                $nama_usaha,
                $alamat
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    "Gagal menyimpan data penjual."
                );
            }

            // ------------------------------------------
            // COMMIT
            // ------------------------------------------

            $conn->commit();

            header(
                "Location: penjual.php?success=" .
                urlencode("Penjual berhasil ditambahkan.")
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

    <title>Tambah Penjual - SIPESTA</title>

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

            <i class="bi bi-person-plus text-success"></i>

            Tambah Penjual

        </h3>

        <p class="text-muted mb-0">

            Tambahkan akun dan data usaha penjual baru
            ke dalam SIPESTA.

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


                    <!-- ==================================
                         AKUN LOGIN
                    ================================== -->

                    <h5 class="section-title mb-3">

                        <i class="bi bi-person-circle text-success"></i>

                        Akun Login

                    </h5>


                    <div class="row g-3 mb-4">

                        <div class="col-md-6">

                            <label class="form-label">

                                Username

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="dummy"
                                style="display:none;"
                            >

                        </div>

                    </div>


                    <form
                        method="POST"
                        autocomplete="off"
                    >

                        <div class="row g-3">

                            <!-- USERNAME -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Username

                                    <span class="text-danger">*</span>

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="bi bi-person"></i>

                                    </span>

                                    <input
                                        type="text"
                                        name="username"
                                        class="form-control"
                                        value="<?= e($_POST['username'] ?? '') ?>"
                                        placeholder="Contoh: penjual01"
                                        required
                                    >

                                </div>

                                <small class="text-muted">

                                    Username digunakan untuk login.

                                </small>

                            </div>


                            <!-- PASSWORD -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Password

                                    <span class="text-danger">*</span>

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="bi bi-lock"></i>

                                    </span>

                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-control"
                                        placeholder="Minimal 6 karakter"
                                        minlength="6"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="togglePassword()"
                                    >

                                        <i
                                            class="bi bi-eye"
                                            id="iconPassword"
                                        ></i>

                                    </button>

                                </div>

                            </div>


                            <!-- ==================================
                                 DATA USAHA
                            ================================== -->

                            <div class="col-12 mt-4">

                                <hr>

                                <h5 class="section-title mb-3">

                                    <i class="bi bi-shop text-success"></i>

                                    Data Usaha

                                </h5>

                            </div>


                            <!-- NAMA USAHA -->

                            <div class="col-md-12">

                                <label class="form-label">

                                    Nama Usaha

                                    <span class="text-danger">*</span>

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="bi bi-shop"></i>

                                    </span>

                                    <input
                                        type="text"
                                        name="nama_usaha"
                                        class="form-control"
                                        value="<?= e($_POST['nama_usaha'] ?? '') ?>"
                                        placeholder="Contoh: Toko Ikan Kuyang"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- ALAMAT -->

                            <div class="col-md-12">

                                <label class="form-label">

                                    Alamat Usaha

                                    <span class="text-danger">*</span>

                                </label>

                                <textarea
                                    name="alamat"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Masukkan alamat lengkap usaha..."
                                    required
                                ><?= e($_POST['alamat'] ?? '') ?></textarea>

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

                                <small class="text-muted">

                                    Role otomatis menjadi penjual.

                                </small>

                            </div>


                            <!-- STATUS -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Status Akun

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Aktif"
                                    readonly
                                >

                                <small class="text-muted">

                                    Akun langsung dapat digunakan untuk login.

                                </small>

                            </div>


                            <!-- BUTTON -->

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

                                        Simpan Penjual

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


<script>

function togglePassword()
{
    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("iconPassword");

    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }
}

</script>

</body>
</html>