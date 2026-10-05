<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ubah Password - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .navbar {
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }

        .password-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,.07);
            overflow: hidden;
        }

        .password-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #198754
            );

            color: white;
            padding: 30px;
        }

        .password-icon {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32px;
        }

        .form-label {
            font-weight: 600;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #6c757d;
            cursor: pointer;
        }

        .password-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg bg-white">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold text-primary"
        >

            <i class="fa-solid fa-store"></i>
            SIPESTA

        </a>

        <div class="ms-auto">

            <a
                href="profil.php"
                class="btn btn-outline-primary"
            >

                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Profil

            </a>

        </div>

    </div>

</nav>


<!-- CONTENT -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7 col-md-9">

            <div class="card password-card">


                <!-- HEADER -->

                <div class="password-header">

                    <div class="d-flex align-items-center gap-3">

                        <div class="password-icon">

                            <i class="fa-solid fa-lock"></i>

                        </div>

                        <div>

                            <h3 class="mb-1">
                                Ubah Password
                            </h3>

                            <p class="mb-0">
                                Perbarui password akun SIPESTA Anda
                            </p>

                        </div>

                    </div>

                </div>


                <!-- BODY -->

                <div class="card-body p-4">


                    <?php if ($success !== ''): ?>

                        <div class="alert alert-success">

                            <i class="fa-solid fa-circle-check me-2"></i>

                            <?= e($success) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($error !== ''): ?>

                        <div class="alert alert-danger">

                            <i class="fa-solid fa-circle-exclamation me-2"></i>

                            <?= e($error) ?>

                        </div>

                    <?php endif; ?>


                    <!-- INFORMASI -->

                    <div class="password-info mb-4">

                        <div class="fw-bold mb-2">

                            <i class="fa-solid fa-shield-halved text-primary"></i>

                            Keamanan Password

                        </div>

                        <ul class="mb-0 text-muted">

                            <li>
                                Gunakan minimal 6 karakter.
                            </li>

                            <li>
                                Jangan gunakan password yang mudah ditebak.
                            </li>

                            <li>
                                Password baru harus sama dengan konfirmasi password.
                            </li>

                        </ul>

                    </div>


                    <!-- FORM -->

                    <form
                        action="proses_ubah_password.php"
                        method="POST"
                    >

                        <!-- PASSWORD LAMA -->

                        <div class="mb-3">

                            <label class="form-label">

                                Password Lama
                                <span class="text-danger">*</span>

                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="password_lama"
                                    id="password_lama"
                                    class="form-control"
                                    required
                                    autocomplete="current-password"
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword('password_lama', this)"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- PASSWORD BARU -->

                        <div class="mb-3">

                            <label class="form-label">

                                Password Baru
                                <span class="text-danger">*</span>

                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="password_baru"
                                    id="password_baru"
                                    class="form-control"
                                    minlength="6"
                                    required
                                    autocomplete="new-password"
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword('password_baru', this)"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- KONFIRMASI -->

                        <div class="mb-4">

                            <label class="form-label">

                                Konfirmasi Password Baru
                                <span class="text-danger">*</span>

                            </label>

                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="konfirmasi_password"
                                    id="konfirmasi_password"
                                    class="form-control"
                                    minlength="6"
                                    required
                                    autocomplete="new-password"
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    onclick="togglePassword('konfirmasi_password', this)"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- BUTTON -->

                        <div class="d-flex justify-content-end gap-2">

                            <a
                                href="profil.php"
                                class="btn btn-secondary"
                            >

                                <i class="fa-solid fa-xmark"></i>
                                Batal

                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fa-solid fa-key"></i>
                                Ubah Password

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

function togglePassword(id, button)
{
    const input = document.getElementById(id);
    const icon = button.querySelector("i");

    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");

    }
}

</script>

</body>

</html>