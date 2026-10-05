<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    }

    if ($_SESSION['role'] === 'penjual') {
        header("Location: penjual/index.php");
        exit;
    }

    if ($_SESSION['role'] === 'pembeli') {
        header("Location: pembeli/index.php");
        exit;
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {

        $stmt = $conn->prepare("
            SELECT id, nama, username, password, role, status
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user['status'] !== 'aktif') {

                $error = "Akun Anda tidak aktif.";

            } elseif (password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                    exit;
                }

                if ($user['role'] === 'penjual') {
                    header("Location: penjual/index.php");
                    exit;
                }

                if ($user['role'] === 'pembeli') {
                    header("Location: pembeli/index.php");
                    exit;
                }

            } else {

                $error = "Username atau password salah.";

            }

        } else {

            $error = "Username atau password salah.";

        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - SIPESTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            background: linear-gradient(
                135deg,
                #0d6efd,
                #198754
            );
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,.2);
        }

        .login-header {
            padding: 30px;
            text-align: center;
            color: white;
            background: rgba(0,0,0,.15);
        }

        .login-body {
            padding: 30px;
            background: white;
        }

        .form-control {
            padding: 12px;
            border-radius: 10px;
        }

        .btn-login {
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="card login-card">

    <div class="login-header">

        <h2 class="mb-1">SIPESTA</h2>

        <p class="mb-0">
            Sistem Pemesanan Produk
        </p>

    </div>

    <div class="login-body">

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Masukkan username"
                    required
                    autocomplete="username"
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary btn-login w-100"
            >
                Login
            </button>

        </form>

        <div class="text-center mt-3">

            <small class="text-muted">
                Belum memiliki akun?
            </small>

            <a href="register.php">
                Daftar sebagai pembeli
            </a>

        </div>

    </div>

</div>

</body>
</html>