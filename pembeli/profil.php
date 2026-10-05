<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/helper.php";

wajibRole("pembeli");

$user_id = userId();

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
        u.username
    FROM pembeli p
    INNER JOIN users u
        ON u.id = p.user_id
    WHERE p.user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$pembeli = $result->fetch_assoc();

$stmt->close();

if (!$pembeli) {
    die("Data profil pembeli tidak ditemukan.");
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Profil Pembeli - SIPESTA</title>

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

        .profile-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,.07);
        }

        .profile-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #198754
            );

            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 35px;
        }

        .form-label {
            font-weight: 600;
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
                href="index.php"
                class="btn btn-outline-primary"
            >

                <i class="fa-solid fa-arrow-left"></i>
                Kembali

            </a>

        </div>

    </div>

</nav>


<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card profile-card">


                <!-- HEADER -->

                <div class="profile-header">

                    <div class="d-flex align-items-center gap-3">

                        <div class="profile-icon">

                            <i class="fa-solid fa-user"></i>

                        </div>


                        <div>

                            <h3 class="mb-1">
                                Profil Saya
                            </h3>

                            <p class="mb-0">
                                Kelola informasi akun dan data toko Anda
                            </p>

                        </div>

                    </div>

                </div>


                <!-- BODY -->

                <div class="card-body p-4">


                    <?php if ($success !== ''): ?>

                        <div class="alert alert-success">

                            <i class="fa-solid fa-circle-check"></i>

                            <?= e($success) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($error !== ''): ?>

                        <div class="alert alert-danger">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <?= e($error) ?>

                        </div>

                    <?php endif; ?>


                    <form
                        action="proses_profil.php"
                        method="POST"
                    >

                        <div class="row g-3">


                            <!-- USERNAME -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Username

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= e($pembeli['username']) ?>"
                                    disabled
                                >

                                <small class="text-muted">

                                    Username tidak dapat diubah.

                                </small>

                            </div>


                            <!-- NAMA -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Nama Lengkap
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="nama"
                                    class="form-control"
                                    value="<?= e($pembeli['nama']) ?>"
                                    required
                                >

                            </div>


                            <!-- TANGGAL LAHIR -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Tanggal Lahir
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="date"
                                    name="tanggal_lahir"
                                    class="form-control"
                                    value="<?= e($pembeli['tanggal_lahir']) ?>"
                                    required
                                >

                            </div>


                            <!-- NO HP -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    No. HP
                                    <span class="text-danger">*</span>

                                </label>

                                <input
                                    type="text"
                                    name="no_hp"
                                    class="form-control"
                                    value="<?= e($pembeli['no_hp']) ?>"
                                    required
                                >

                            </div>


                            <!-- JENIS KELAMIN -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Jenis Kelamin
                                    <span class="text-danger">*</span>

                                </label>

                                <select
                                    name="jenis_kelamin"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Jenis Kelamin --
                                    </option>

                                    <option
                                        value="Laki-laki"
                                        <?= $pembeli['jenis_kelamin'] === 'Laki-laki'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Laki-laki
                                    </option>

                                    <option
                                        value="Perempuan"
                                        <?= $pembeli['jenis_kelamin'] === 'Perempuan'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Perempuan
                                    </option>

                                </select>

                            </div>


                            <!-- NAMA TOKO -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Nama Toko

                                </label>

                                <input
                                    type="text"
                                    name="nama_toko"
                                    class="form-control"
                                    value="<?= e($pembeli['nama_toko']) ?>"
                                >

                            </div>


                            <!-- LAMA USAHA -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    Lama Usaha

                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        name="lama_usaha"
                                        class="form-control"
                                        min="0"
                                        value="<?= e($pembeli['lama_usaha']) ?>"
                                    >

                                    <span class="input-group-text">
                                        Tahun
                                    </span>

                                </div>

                            </div>


                            <!-- ALAMAT -->

                            <div class="col-12">

                                <label class="form-label">

                                    Alamat
                                    <span class="text-danger">*</span>

                                </label>

                                <textarea
                                    name="alamat"
                                    class="form-control"
                                    rows="4"
                                    required
                                ><?= e($pembeli['alamat']) ?></textarea>

                            </div>


                            <!-- BUTTON -->

                            <div class="col-12">

                                <hr>

                                <div class="d-flex justify-content-end gap-2">

                                    <a
                                        href="ubah_password.php"
                                        class="btn btn-warning"
                                    >

                                        <i class="fa-solid fa-key"></i>
                                        Ubah Password

                                    </a>

                                    <a
                                        href="index.php"
                                        class="btn btn-secondary"
                                    >

                                        <i class="fa-solid fa-xmark"></i>
                                        Batal

                                    </a>

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >

                                        <i class="fa-solid fa-save"></i>
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


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>