<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";

wajibRole("pembeli");


/*
|--------------------------------------------------------------------------
| USER LOGIN
|--------------------------------------------------------------------------
*/

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CARI DATA PEMBELI BERDASARKAN USER LOGIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        nama,
        tanggal_lahir,
        no_hp,
        alamat,
        latitude,
        longitude,
        nama_toko,
        jenis_kelamin,
        lama_usaha
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$pembeli = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| CEK DATA PEMBELI
|--------------------------------------------------------------------------
*/

if (!$pembeli) {

    die(
        "Data pembeli tidak ditemukan untuk user_id: "
        . $user_id
    );

}

$pembeli_id = (int)$pembeli['id'];


/*
|--------------------------------------------------------------------------
| HITUNG USIA OTOMATIS
|--------------------------------------------------------------------------
*/

$usia = null;

if (!empty($pembeli['tanggal_lahir'])) {

    try {

        $tanggal_lahir = new DateTime($pembeli['tanggal_lahir']);
        $hari_ini = new DateTime();

        $usia = $tanggal_lahir->diff($hari_ini)->y;

    } catch (Exception $e) {

        $usia = null;

    }

}


/*
|--------------------------------------------------------------------------
| AMBIL KERANJANG
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM keranjang
    WHERE pembeli_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $pembeli_id);
$stmt->execute();

$result = $stmt->get_result();
$keranjang = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| JIKA KERANJANG TIDAK ADA
|--------------------------------------------------------------------------
*/

if (!$keranjang) {

    header(
        "Location: keranjang.php?error=" .
        urlencode("Keranjang Anda masih kosong.")
    );

    exit;
}


$keranjang_id = (int)$keranjang['id'];


/*
|--------------------------------------------------------------------------
| AMBIL DETAIL KERANJANG
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        kd.id,
        kd.produk_id,
        kd.jumlah,
        kd.harga,
        kd.subtotal,

        p.nama_produk,
        p.harga AS harga_produk,
        p.stok,
        p.satuan,
        p.status,
        p.foto,
        p.penjual_id,

        pen.nama_usaha,
        pen.latitude AS penjual_latitude,
        pen.longitude AS penjual_longitude

    FROM keranjang_detail kd

    INNER JOIN produk p
        ON p.id = kd.produk_id

    INNER JOIN penjual pen
        ON pen.id = p.penjual_id

    WHERE kd.keranjang_id = ?

    ORDER BY kd.id ASC
");

$stmt->bind_param("i", $keranjang_id);
$stmt->execute();

$result = $stmt->get_result();


$items = [];
$total_harga = 0;


/*
|--------------------------------------------------------------------------
| PROSES PRODUK
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {


    /*
    |--------------------------------------------------------------------------
    | CEK STATUS PRODUK
    |--------------------------------------------------------------------------
    */

    if ($row['status'] !== 'tersedia') {

        header(
            "Location: keranjang.php?error=" .
            urlencode(
                "Produk " .
                $row['nama_produk'] .
                " sudah tidak tersedia."
            )
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CEK STOK
    |--------------------------------------------------------------------------
    */

    if ((float)$row['jumlah'] > (float)$row['stok']) {

        header(
            "Location: keranjang.php?error=" .
            urlencode(
                "Stok " .
                $row['nama_produk'] .
                " tidak mencukupi."
            )
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | GUNAKAN HARGA TERBARU DARI PRODUK
    |--------------------------------------------------------------------------
    */

    $harga = (float)$row['harga_produk'];

    $jumlah = (float)$row['jumlah'];

    $subtotal = $harga * $jumlah;


    /*
    |--------------------------------------------------------------------------
    | SIMPAN NILAI FINAL
    |--------------------------------------------------------------------------
    */

    $row['harga_final'] = $harga;

    $row['subtotal'] = $subtotal;


    /*
    |--------------------------------------------------------------------------
    | HITUNG TOTAL
    |--------------------------------------------------------------------------
    */

    $total_harga += $subtotal;


    $items[] = $row;
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| CEK KERANJANG KOSONG
|--------------------------------------------------------------------------
*/

if (empty($items)) {

    header(
        "Location: keranjang.php?error=" .
        urlencode("Keranjang Anda masih kosong.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| JUMLAH TOTAL ITEM
|--------------------------------------------------------------------------
*/

$total_qty = 0;

foreach ($items as $item) {

    $total_qty += (float)$item['jumlah'];

}

/*
|--------------------------------------------------------------------------
| DATA LOKASI PENJUAL
|--------------------------------------------------------------------------
| Digunakan oleh JavaScript untuk menghitung jarak pembeli ke setiap
| penjual. Jika ada beberapa penjual, jarak terjauh digunakan sebagai
| dasar estimasi ongkos kirim satu pesanan.
|--------------------------------------------------------------------------
*/

$sellerLocations = [];

foreach ($items as $item) {

    $sellerId = (int)($item['penjual_id'] ?? 0);

    if ($sellerId <= 0) {
        continue;
    }

    if (!isset($sellerLocations[$sellerId])) {
        $sellerLocations[$sellerId] = [
            'id' => $sellerId,
            'nama' => $item['nama_usaha'] ?? 'Penjual',
            'latitude' => is_numeric($item['penjual_latitude'] ?? null)
                ? (float)$item['penjual_latitude']
                : null,
            'longitude' => is_numeric($item['penjual_longitude'] ?? null)
                ? (float)$item['penjual_longitude']
                : null,
        ];
    }
}

/*
|--------------------------------------------------------------------------
| PENGATURAN ONGKOS KIRIM
|--------------------------------------------------------------------------
*/

$ongkirSetting = null;

$stmt = $conn->prepare("
    SELECT
        tarif_dasar,
        tarif_per_km,
        minimal_gratis,
        maksimal_jarak_km
    FROM pengaturan_ongkir
    WHERE status = 'aktif'
    ORDER BY id ASC
    LIMIT 1
");

if ($stmt) {
    $stmt->execute();
    $resultOngkir = $stmt->get_result();
    $ongkirSetting = $resultOngkir->fetch_assoc();
    $stmt->close();
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

    <title>Checkout - SIPESTA</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
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


        .checkout-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }


        .summary-card {
            position: sticky;
            top: 20px;
        }


        .product-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }


        .product-item:last-child {
            border-bottom: none;
        }


        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
        }


        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }


        .form-label {
            margin-bottom: 6px;
        }


        .location-card {
            border: 1px solid #e8ecef;
            border-radius: 12px;
            background: #fbfffc;
        }

        .location-status {
            border-radius: 10px;
            font-size: .9rem;
        }

        .coordinate-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 12px;
        }

        @media (max-width: 768px) {

            .summary-card {
                position: static;
            }

            .product-image {
                width: 60px;
                height: 60px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
        NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand fw-bold text-success"
        >

            <i class="fa-solid fa-store"></i>

            SIPESTA

        </a>


        <a
            href="keranjang.php"
            class="btn btn-outline-success"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Keranjang

        </a>

    </div>

</nav>



<!-- =========================================================
        CONTENT
========================================================= -->

<div class="container py-4">


    <!-- HEADER -->

    <div class="mb-4">

        <h3 class="fw-bold">

            <i class="fa-solid fa-credit-card text-success"></i>

            Checkout

        </h3>


        <p class="text-muted mb-0">

            Periksa data dan pesanan sebelum melakukan pemesanan.

        </p>

    </div>



    <!-- ERROR -->

    <?php if (!empty($_GET['error'])): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= e($_GET['error']) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
            FORM CHECKOUT
    ====================================================== -->

    <form
        action="proses_checkout.php"
        method="POST"
    >

        <!-- =================================================
                DATA PENERIMA
                Data ini dikirim ke proses_checkout.php
        ================================================== -->

        <input
            type="hidden"
            name="nama_penerima"
            value="<?= e($pembeli['nama']) ?>"
        >

        <input
            type="hidden"
            name="no_hp"
            value="<?= e($pembeli['no_hp']) ?>"
        >


        <div class="row g-4">


            <!-- =================================================
                    DATA PEMBELI
            ================================================== -->

            <div class="col-lg-7">


                <div class="card checkout-card mb-4">

                    <div class="card-body p-4">


                        <h5 class="fw-bold mb-4">

                            <i class="fa-solid fa-user text-success"></i>

                            Data Pembeli

                        </h5>



                        <div class="info-box mb-3">


                            <div class="row g-3">


                                <!-- NAMA -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Nama
                                    </small>

                                    <div class="fw-semibold">

                                        <?= e($pembeli['nama']) ?>

                                    </div>

                                </div>



                                <!-- NO HP -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        No. HP
                                    </small>

                                    <div class="fw-semibold">

                                        <?= e($pembeli['no_hp']) ?>

                                    </div>

                                </div>



                                <!-- TANGGAL LAHIR -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Tanggal Lahir
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($pembeli['tanggal_lahir'])
                                            ? e($pembeli['tanggal_lahir'])
                                            : "-"
                                        ?>

                                    </div>

                                </div>



                                <!-- USIA -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Usia
                                    </small>

                                    <div class="fw-semibold">

                                        <?= $usia !== null
                                            ? e($usia) . " tahun"
                                            : "-"
                                        ?>

                                    </div>

                                </div>



                                <!-- JENIS KELAMIN -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Jenis Kelamin
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($pembeli['jenis_kelamin'])
                                            ? e($pembeli['jenis_kelamin'])
                                            : "-"
                                        ?>

                                    </div>

                                </div>



                                <!-- LAMA USAHA -->

                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Lama Usaha
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($pembeli['lama_usaha'])
                                            ? e($pembeli['lama_usaha']) . " tahun"
                                            : "-"
                                        ?>

                                    </div>

                                </div>



                                <!-- NAMA TOKO -->

                                <div class="col-12">

                                    <small class="text-muted">
                                        Nama Toko
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($pembeli['nama_toko'])
                                            ? e($pembeli['nama_toko'])
                                            : "-"
                                        ?>

                                    </div>

                                </div>


                            </div>

                        </div>



                        <!-- ALAMAT -->

                        <div class="mb-3">

                            <label
                                class="form-label fw-semibold"
                                for="alamat_pengiriman"
                            >

                                Alamat Pengiriman

                            </label>


                            <textarea
                                name="alamat_pengiriman"
                                id="alamat_pengiriman"
                                class="form-control"
                                rows="4"
                                required
                            ><?= e($pembeli['alamat']) ?></textarea>

                        </div>

                        <!-- LOKASI PENGIRIMAN / GPS -->

                        <div class="location-card p-3 mb-3">

                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="text-danger fs-4">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Lokasi Pengiriman</h6>
                                    <small class="text-muted">
                                        Gunakan lokasi perangkat untuk membantu
                                        menghitung jarak dan ongkos kirim.
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnLokasi">
                                    <i class="fa-solid fa-crosshairs"></i>
                                    Gunakan Lokasi Saya
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="btnHapusLokasi">
                                    <i class="fa-solid fa-xmark"></i>
                                    Hapus Lokasi
                                </button>
                            </div>

                            <div
                                id="statusLokasi"
                                class="alert alert-secondary location-status mb-3">
                                <i class="fa-solid fa-circle-info"></i>
                                Lokasi pengiriman belum dipilih.
                            </div>

                            <input
                                type="hidden"
                                name="latitude_pengiriman"
                                id="latitude_pengiriman"
                                value="<?= e($pembeli['latitude'] ?? '') ?>">

                            <input
                                type="hidden"
                                name="longitude_pengiriman"
                                id="longitude_pengiriman"
                                value="<?= e($pembeli['longitude'] ?? '') ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="coordinate-box">
                                        <small class="text-muted d-block mb-1">
                                            Latitude
                                        </small>
                                        <div
                                            id="latitude_tampil"
                                            class="fw-semibold text-break">
                                            <?= e($pembeli['latitude'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="coordinate-box">
                                        <small class="text-muted d-block mb-1">
                                            Longitude
                                        </small>
                                        <div
                                            id="longitude_tampil"
                                            class="fw-semibold text-break">
                                            <?= e($pembeli['longitude'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 mb-0 small text-muted">
                                <i class="fa-solid fa-shield-halved"></i>
                                Koordinat digunakan untuk lokasi pengiriman
                                dan perhitungan ongkos kirim.
                            </div>

                        </div>



                        <!-- METODE PEMBAYARAN -->

                        <div class="mb-3">

                            <label
                                class="form-label fw-semibold"
                                for="metode_pembayaran"
                            >

                                Metode Pembayaran

                            </label>


                            <select
                                name="metode_pembayaran"
                                id="metode_pembayaran"
                                class="form-select"
                                required
                            >

                                <option value="COD">

                                    COD - Bayar di Tempat

                                </option>


                                <option value="TRANSFER">

                                    Transfer Bank

                                </option>

                            </select>

                        </div>



                        <!-- CATATAN -->

                        <div>

                            <label
                                class="form-label fw-semibold"
                                for="catatan"
                            >

                                Catatan Pesanan

                            </label>


                            <textarea
                                name="catatan"
                                id="catatan"
                                class="form-control"
                                rows="3"
                                placeholder="Catatan untuk penjual..."
                            ></textarea>

                        </div>


                    </div>

                </div>



                <!-- =================================================
                        PRODUK YANG DIPESAN
                ================================================== -->

                <div class="card checkout-card">


                    <div class="card-body p-4">


                        <h5 class="fw-bold mb-3">

                            <i class="fa-solid fa-box text-success"></i>

                            Produk yang Dipesan

                        </h5>



                        <?php foreach ($items as $item): ?>


                            <div class="product-item">


                                <div class="d-flex gap-3 align-items-center">


                                    <!-- FOTO -->

                                    <?php

                                    $foto = trim($item['foto'] ?? '');
                                    $foto = str_replace('\\', '/', $foto);
                                    $namaFileFoto = basename($foto);

                                    ?>


                                    <?php if ($namaFileFoto !== ''): ?>

                                        <img
                                            src="../uploads/produk/<?= rawurlencode($namaFileFoto) ?>"
                                            class="product-image"
                                            alt="<?= e($item['nama_produk']) ?>"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >

                                        <div
                                            class="product-image bg-light align-items-center justify-content-center"
                                            style="display:none;"
                                        >

                                            <i class="fa-solid fa-image text-muted"></i>

                                        </div>

                                    <?php else: ?>

                                        <div
                                            class="product-image bg-light d-flex align-items-center justify-content-center"
                                        >

                                            <i class="fa-solid fa-image text-muted"></i>

                                        </div>

                                    <?php endif; ?>



                                    <!-- INFO PRODUK -->

                                    <div class="flex-grow-1">


                                        <div class="fw-bold">

                                            <?= e($item['nama_produk']) ?>

                                        </div>


                                        <small class="text-muted">

                                            <i class="fa-solid fa-store"></i>

                                            <?= e($item['nama_usaha']) ?>

                                        </small>


                                        <div class="mt-1">

                                            <?= e($item['jumlah']) ?>

                                            <?= e($item['satuan']) ?>

                                            ×

                                            <?= rupiah($item['harga_final']) ?>

                                        </div>


                                    </div>



                                    <!-- SUBTOTAL -->

                                    <div class="fw-bold text-success text-end">

                                        <?= rupiah($item['subtotal']) ?>

                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>

                </div>


            </div>



            <!-- =================================================
                    RINGKASAN
            ================================================== -->

            <div class="col-lg-5">


                <div class="card checkout-card summary-card">


                    <div class="card-body p-4">


                        <h5 class="fw-bold mb-4">

                            <i class="fa-solid fa-receipt text-success"></i>

                            Ringkasan Pesanan

                        </h5>



                        <!-- JUMLAH PRODUK -->

                        <div class="d-flex justify-content-between mb-3">

                            <span>

                                Jumlah Produk

                            </span>


                            <strong>

                                <?= count($items) ?>

                            </strong>

                        </div>



                        <!-- TOTAL QTY -->

                        <div class="d-flex justify-content-between mb-3">

                            <span>

                                Total Jumlah

                            </span>


                            <strong>

                                <?= rtrim(
                                    rtrim(
                                        number_format(
                                            $total_qty,
                                            2,
                                            ',',
                                            '.'
                                        ),
                                        '0'
                                    ),
                                    ','
                                ) ?>

                            </strong>

                        </div>



                        <!-- TOTAL HARGA -->

                        <div class="d-flex justify-content-between mb-3">

                            <span>

                                Total Harga

                            </span>


                            <strong>

                                <?= rupiah($total_harga) ?>

                            </strong>

                        </div>



                        <hr>



                        <!-- ONGKOS KIRIM -->

                        <div class="d-flex justify-content-between mb-3">
                            <span>Ongkos Kirim</span>
                            <strong id="ongkosKirimTampil" class="text-muted">
                                Rp0
                            </strong>
                        </div>

                        <hr>

                        <div
                            class="d-flex justify-content-between mb-2"
                            id="jarakRow"
                            style="display: none !important;"
                        >
                            <span>
                                <i class="fa-solid fa-route text-success"></i>
                                Jarak Pengiriman
                            </span>

                            <strong id="jarakDisplay">
                                0 km
                            </strong>
                        </div>

                        <!-- TOTAL PEMBAYARAN -->

                        <div class="d-flex justify-content-between align-items-center">

                            <span class="fw-bold">

                                Total Pembayaran

                            </span>


                            <strong
                                id="totalPembayaran"
                                class="text-success fs-4"
                            >

                                <?= rupiah($total_harga) ?>

                            </strong>

                        </div>



                        <!-- BUTTON -->

                        <button
                            type="submit"
                            class="btn btn-success btn-lg w-100 mt-4"
                        >

                        <input
                            type="hidden"
                            name="jarak_km"
                            id="jarak_km"
                            value="0">

                        <input
                            type="hidden"
                            name="ongkos_kirim"
                            id="ongkos_kirim"
                            value="0">

                            <i class="fa-solid fa-check-circle"></i>

                            Buat Pesanan

                        </button>



                        <div class="text-center mt-3">

                            <small class="text-muted">

                                Pastikan alamat dan produk
                                sudah benar sebelum membuat pesanan.

                            </small>

                        </div>


                    </div>

                </div>


            </div>


        </div>


    </form>


</div>



<!-- GPS / LOKASI PENGIRIMAN + ESTIMASI ONGKOS KIRIM -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const btnLokasi = document.getElementById('btnLokasi');
    const btnHapusLokasi = document.getElementById('btnHapusLokasi');

    const latitudeInput = document.getElementById('latitude_pengiriman');
    const longitudeInput = document.getElementById('longitude_pengiriman');

    const latitudeTampil = document.getElementById('latitude_tampil');
    const longitudeTampil = document.getElementById('longitude_tampil');

    const statusLokasi = document.getElementById('statusLokasi');
    const ongkosKirimTampil = document.getElementById('ongkosKirimTampil');
    const jarakDisplay = document.getElementById('jarakDisplay');
    const jarakRow = document.getElementById('jarakRow');
    const totalPembayaran = document.getElementById('totalPembayaran');
    const jarakInput = document.getElementById('jarak_km');
    const ongkirInput = document.getElementById('ongkos_kirim');
    const formCheckout = document.querySelector('form[action="proses_checkout.php"]');

    const totalProduk = <?= json_encode((float)$total_harga) ?>;

    const lokasiPenjual = <?= json_encode(
        array_values($sellerLocations),
        JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
    ) ?>;

    const pengaturanOngkir = <?= json_encode(
        $ongkirSetting,
        JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
    ) ?>;

    function formatRupiah(angka) {
        return 'Rp' + Math.round(Number(angka) || 0).toLocaleString('id-ID');
    }

    function setStatus(type, icon, message) {
        if (!statusLokasi) return;

        statusLokasi.className =
            'alert alert-' + type + ' location-status mb-3';

        statusLokasi.innerHTML =
            '<i class="fa-solid ' + icon + '"></i> ' + message;
    }

    function hitungJarakKm(lat1, lon1, lat2, lon2) {
        const R = 6371;

        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;

        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) *
            Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c;
    }

    function resetOngkir() {
        if (ongkosKirimTampil) {
            ongkosKirimTampil.textContent = 'Rp0';
            ongkosKirimTampil.classList.remove('text-success');
            ongkosKirimTampil.classList.add('text-muted');
        }

        if (jarakDisplay) {
            jarakDisplay.textContent = '0 km';
        }

        if (jarakRow) {
            jarakRow.style.setProperty('display', 'none', 'important');
        }

        if (jarakInput) jarakInput.value = '0';
        if (ongkirInput) ongkirInput.value = '0';

        if (totalPembayaran) {
            totalPembayaran.textContent = formatRupiah(totalProduk);
        }
    }

    function hitungOngkirRealtime() {
        const lat = parseFloat(latitudeInput.value);
        const lng = parseFloat(longitudeInput.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            resetOngkir();
            return false;
        }

        if (!Array.isArray(lokasiPenjual) || lokasiPenjual.length === 0) {
            resetOngkir();
            setStatus(
                'danger',
                'fa-triangle-exclamation',
                'Lokasi penjual belum tersedia untuk menghitung ongkos kirim.'
            );
            return false;
        }

        const jarakPenjual = [];

        for (const penjual of lokasiPenjual) {
            const latPenjual = parseFloat(penjual.latitude);
            const lngPenjual = parseFloat(penjual.longitude);

            if (!Number.isFinite(latPenjual) || !Number.isFinite(lngPenjual)) {
                resetOngkir();
                setStatus(
                    'warning',
                    'fa-location-dot',
                    'Lokasi penjual <strong>' +
                    (penjual.nama || 'Penjual') +
                    '</strong> belum diatur. Silakan lengkapi lokasi penjual terlebih dahulu.'
                );
                return false;
            }

            const jarak = hitungJarakKm(
                lat,
                lng,
                latPenjual,
                lngPenjual
            );

            jarakPenjual.push({
                nama: penjual.nama || 'Penjual',
                jarak: jarak
            });
        }

        const jarakMaksimal = Math.max(
            ...jarakPenjual.map(item => item.jarak)
        );

        const tarifDasar = Number(pengaturanOngkir?.tarif_dasar || 0);
        const tarifPerKm = Number(pengaturanOngkir?.tarif_per_km || 0);
        const minimalGratis = Number(pengaturanOngkir?.minimal_gratis || 0);
        const maksimalJarak = Number(pengaturanOngkir?.maksimal_jarak_km || 0);

        if (
            maksimalJarak > 0 &&
            jarakMaksimal > maksimalJarak
        ) {
            resetOngkir();

            if (jarakDisplay) {
                jarakDisplay.textContent = jarakMaksimal.toFixed(2) + ' km';
            }

            if (jarakRow) {
                jarakRow.style.setProperty('display', 'flex', 'important');
            }

            setStatus(
                'danger',
                'fa-triangle-exclamation',
                'Jarak pengiriman <strong>' +
                jarakMaksimal.toFixed(2) +
                ' km</strong> melebihi batas layanan ' +
                maksimalJarak.toFixed(2) +
                ' km. Pesanan belum dapat dibuat.'
            );

            return false;
        }

        let ongkir = 0;

        if (minimalGratis > 0 && totalProduk >= minimalGratis) {
            ongkir = 0;
        } else {
            ongkir = tarifDasar + (jarakMaksimal * tarifPerKm);
        }

        ongkir = Math.round(ongkir);

        if (jarakDisplay) {
            jarakDisplay.textContent = jarakMaksimal.toFixed(2) + ' km';
        }

        if (jarakRow) {
            jarakRow.style.setProperty('display', 'flex', 'important');
        }

        if (ongkosKirimTampil) {
            ongkosKirimTampil.textContent = formatRupiah(ongkir);
            ongkosKirimTampil.classList.remove('text-muted');
            ongkosKirimTampil.classList.add('text-success');
        }

        if (jarakInput) {
            jarakInput.value = jarakMaksimal.toFixed(2);
        }

        if (ongkirInput) {
            ongkirInput.value = ongkir;
        }

        if (totalPembayaran) {
            totalPembayaran.textContent =
                formatRupiah(totalProduk + ongkir);
        }

        if (minimalGratis > 0 && totalProduk >= minimalGratis) {
            setStatus(
                'success',
                'fa-circle-check',
                'Lokasi berhasil ditemukan. Ongkos kirim <strong>GRATIS</strong> karena total belanja mencapai ' +
                formatRupiah(minimalGratis) + '.'
            );
        } else {
            setStatus(
                'success',
                'fa-circle-check',
                'Lokasi berhasil ditemukan. Jarak terjauh dari penjual ke lokasi Anda <strong>' +
                jarakMaksimal.toFixed(2) +
                ' km</strong>. Estimasi ongkos kirim <strong>' +
                formatRupiah(ongkir) +
                '</strong>.'
            );
        }

        return true;
    }

    function kosongkanLokasi() {
        latitudeInput.value = '';
        longitudeInput.value = '';

        latitudeTampil.textContent = '-';
        longitudeTampil.textContent = '-';

        resetOngkir();

        setStatus(
            'secondary',
            'fa-circle-info',
            'Lokasi pengiriman belum dipilih. Gunakan lokasi Anda untuk menghitung ongkos kirim.'
        );

        btnLokasi.disabled = false;
        btnLokasi.innerHTML =
            '<i class="fa-solid fa-crosshairs"></i> Gunakan Lokasi Saya';
    }

    btnLokasi.addEventListener('click', function () {

        if (!navigator.geolocation) {
            setStatus(
                'danger',
                'fa-triangle-exclamation',
                'Browser Anda tidak mendukung fitur lokasi.'
            );
            return;
        }

        btnLokasi.disabled = true;
        btnLokasi.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span>' +
            'Mengambil lokasi...';

        setStatus(
            'info',
            'fa-location-crosshairs',
            'Sedang mengambil lokasi perangkat. Pastikan GPS aktif.'
        );

        navigator.geolocation.getCurrentPosition(

            function (position) {

                const lat =
                    Number(position.coords.latitude).toFixed(8);

                const lng =
                    Number(position.coords.longitude).toFixed(8);

                latitudeInput.value = lat;
                longitudeInput.value = lng;

                latitudeTampil.textContent = lat;
                longitudeTampil.textContent = lng;

                const berhasil = hitungOngkirRealtime();

                btnLokasi.disabled = false;
                btnLokasi.innerHTML = berhasil
                    ? '<i class="fa-solid fa-check"></i> Lokasi Berhasil Diambil'
                    : '<i class="fa-solid fa-crosshairs"></i> Gunakan Lokasi Saya';
            },

            function (error) {

                let pesan =
                    'Gagal mengambil lokasi perangkat.';

                if (error.code === error.PERMISSION_DENIED) {
                    pesan =
                        'Akses lokasi ditolak. Izinkan lokasi pada browser, lalu coba lagi.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    pesan =
                        'Lokasi perangkat tidak tersedia. Pastikan GPS aktif.';
                } else if (error.code === error.TIMEOUT) {
                    pesan =
                        'Waktu pengambilan lokasi habis. Silakan coba lagi.';
                }

                setStatus(
                    'danger',
                    'fa-triangle-exclamation',
                    pesan
                );

                btnLokasi.disabled = false;
                btnLokasi.innerHTML =
                    '<i class="fa-solid fa-crosshairs"></i> Gunakan Lokasi Saya';
            },

            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });

    btnHapusLokasi.addEventListener('click', function () {
        kosongkanLokasi();
    });

    const latAwal = latitudeInput.value.trim();
    const lngAwal = longitudeInput.value.trim();

    if (latAwal !== '' && lngAwal !== '') {
        latitudeTampil.textContent = latAwal;
        longitudeTampil.textContent = lngAwal;

        const berhasil = hitungOngkirRealtime();

        if (berhasil) {
            btnLokasi.innerHTML =
                '<i class="fa-solid fa-check"></i> Lokasi Tersimpan';
        }
    } else {
        resetOngkir();
    }

    if (formCheckout) {
        formCheckout.addEventListener('submit', function (event) {
            const lat = parseFloat(latitudeInput.value);
            const lng = parseFloat(longitudeInput.value);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                event.preventDefault();

                setStatus(
                    'warning',
                    'fa-location-dot',
                    'Silakan gunakan <strong>Lokasi Saya</strong> terlebih dahulu agar jarak dan ongkos kirim dapat dihitung.'
                );

                document.querySelector('.location-card')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return;
            }

            if (!hitungOngkirRealtime()) {
                event.preventDefault();
            }
        });
    }

});
</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>