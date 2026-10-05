    <?php

    require_once "config/database.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }

    $error = "";
    $success = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
        $no_hp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $nama_toko = trim($_POST['nama_toko'] ?? '');
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
        $lama_usaha = (int)($_POST['lama_usaha'] ?? 0);

        if (
            $nama === '' ||
            $username === '' ||
            $password === '' ||
            $konfirmasi_password === '' ||
            $tanggal_lahir === '' ||
            $no_hp === '' ||
            $alamat === '' ||
            $nama_toko === '' ||
            $jenis_kelamin === ''
        ) {

            $error = "Semua data wajib diisi.";

        } elseif (strlen($username) < 4) {

            $error = "Username minimal 4 karakter.";

        } elseif (strlen($password) < 6) {

            $error = "Password minimal 6 karakter.";

        } elseif ($password !== $konfirmasi_password) {

            $error = "Konfirmasi password tidak sesuai.";

        } elseif ($lama_usaha < 0) {

            $error = "Lama usaha tidak valid.";

        } else {

            // Cek username
            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "Username sudah digunakan.";

            } else {

                $conn->begin_transaction();

                try {

                    // Hash password
                    $password_hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Simpan user
                    $stmtUser = $conn->prepare("
                        INSERT INTO users
                        (
                            nama,
                            username,
                            password,
                            no_hp,
                            role,
                            status
                        )
                        VALUES (?, ?, ?, ?, 'pembeli', 'aktif')
                    ");

                    $stmtUser->bind_param(
                        "ssss",
                        $nama,
                        $username,
                        $password_hash,
                        $no_hp
                    );

                    $stmtUser->execute();

                    $user_id = $conn->insert_id;

                    // Simpan profil pembeli
                    $stmtPembeli = $conn->prepare("
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
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmtPembeli->bind_param(
                        "issssssi",
                        $user_id,
                        $nama,
                        $tanggal_lahir,
                        $no_hp,
                        $alamat,
                        $nama_toko,
                        $jenis_kelamin,
                        $lama_usaha
                    );

                    $stmtPembeli->execute();

                    // Buat keranjang otomatis
                    $pembeli_id = $conn->insert_id;

                    $stmtKeranjang = $conn->prepare("
                        INSERT INTO keranjang (pembeli_id)
                        VALUES (?)
                    ");

                    $stmtKeranjang->bind_param(
                        "i",
                        $pembeli_id
                    );

                    $stmtKeranjang->execute();

                    $conn->commit();

                    $success = "Pendaftaran berhasil. Silakan login.";

                } catch (Exception $e) {

                    $conn->rollback();

                    $error = "Pendaftaran gagal. Silakan coba lagi.";

                }
            }

            $stmt->close();
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

        <title>Daftar Pembeli - SIPESTA</title>

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
                padding: 30px 15px;
                font-family: Arial, sans-serif;
            }

            .register-card {
                max-width: 700px;
                margin: auto;
                border: none;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 15px 40px rgba(0,0,0,.2);
            }

            .register-header {
                background: rgba(0,0,0,.15);
                color: white;
                padding: 30px;
                text-align: center;
            }

            .register-body {
                background: white;
                padding: 30px;
            }

            .form-control,
            .form-select {
                border-radius: 10px;
                padding: 11px;
            }

            .btn-register {
                padding: 12px;
                border-radius: 10px;
                font-weight: bold;
            }

        </style>

    </head>

    <body>

    <div class="card register-card">

        <div class="register-header">

            <h2>SIPESTA</h2>

            <p class="mb-0">
                Pendaftaran Pembeli
            </p>

        </div>

        <div class="register-body">

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="alert alert-success">

                    <?= htmlspecialchars($success); ?>

                    <br><br>

                    <a
                        href="login.php"
                        class="btn btn-success btn-sm"
                    >
                        Login Sekarang
                    </a>

                </div>

            <?php endif; ?>

            <?php if (!$success): ?>

            <form method="POST">

                <h5 class="mb-3">
                    Data Akun
                </h5>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            name="nama"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            minlength="4"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="6"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Konfirmasi Password
                        </label>

                        <input
                            type="password"
                            name="konfirmasi_password"
                            class="form-control"
                            required
                        >

                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    Data Pembeli
                </h5>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Tanggal Lahir
                        </label>

                        <input
                            type="date"
                            name="tanggal_lahir"
                            id="tanggal_lahir"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Usia
                        </label>

                        <input
                            type="text"
                            id="usia"
                            class="form-control"
                            placeholder="Otomatis"
                            readonly
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            No. HP
                        </label>

                        <input
                            type="text"
                            name="no_hp"
                            class="form-control"
                            placeholder="08xxxxxxxxxx"
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Jenis Kelamin
                        </label>

                        <select
                            name="jenis_kelamin"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih --
                            </option>

                            <option value="Laki-laki">
                                Laki-laki
                            </option>

                            <option value="Perempuan">
                                Perempuan
                            </option>

                        </select>

                    </div>

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Nama Toko
                        </label>

                        <input
                            type="text"
                            name="nama_toko"
                            class="form-control"
                            placeholder="Contoh: Toko Makmur"
                            required
                        >

                    </div>

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Alamat
                        </label>

                        <textarea
                            name="alamat"
                            class="form-control"
                            rows="3"
                            required
                        ></textarea>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Lama Usaha
                        </label>

                        <div class="input-group">

                            <input
                                type="number"
                                name="lama_usaha"
                                class="form-control"
                                min="0"
                                value="0"
                                required
                            >

                            <span class="input-group-text">
                                Tahun
                            </span>

                        </div>

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-register w-100"
                >
                    Daftar sebagai Pembeli
                </button>

            </form>

            <div class="text-center mt-3">

                Sudah punya akun?

                <a href="login.php">
                    Login
                </a>

            </div>

            <?php endif; ?>

        </div>

    </div>

    <script>

    const tanggalLahir =
        document.getElementById('tanggal_lahir');

    const usia =
        document.getElementById('usia');

    tanggalLahir.addEventListener('change', function () {

        const lahir = new Date(this.value);
        const hariIni = new Date();

        let umur =
            hariIni.getFullYear() -
            lahir.getFullYear();

        const bulan =
            hariIni.getMonth() -
            lahir.getMonth();

        if (
            bulan < 0 ||
            (
                bulan === 0 &&
                hariIni.getDate() < lahir.getDate()
            )
        ) {
            umur--;
        }

        if (umur >= 0) {
            usia.value = umur + " tahun";
        } else {
            usia.value = "";
        }

    });

    </script>

    </body>
    </html>