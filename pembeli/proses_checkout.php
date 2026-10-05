<?php

require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";
require_once "../config/notifikasi.php";

wajibRole("pembeli");

$user_id = userId();

if ($user_id <= 0) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FUNGSI REDIRECT ERROR
|--------------------------------------------------------------------------
*/

function checkoutError($pesan)
{
    header(
        "Location: checkout.php?error=" .
        urlencode($pesan)
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMBELI
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        nama,
        no_hp,
        alamat,
        latitude,
        longitude
    FROM pembeli
    WHERE user_id = ?
    LIMIT 1
");

if (!$stmt) {
    checkoutError("Gagal mengambil data pembeli.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$pembeli = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$pembeli) {
    checkoutError("Data pembeli tidak ditemukan.");
}

$pembeli_id = (int) $pembeli['id'];


/*
|--------------------------------------------------------------------------
| AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

$alamat_pengiriman = trim(
    $_POST['alamat_pengiriman'] ?? ''
);

$catatan = trim(
    $_POST['catatan'] ?? ''
);

$metode_pembayaran = trim(
    $_POST['metode_pembayaran'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDASI FORM
|--------------------------------------------------------------------------
*/

if ($alamat_pengiriman === '') {
    checkoutError(
        "Alamat pengiriman wajib diisi."
    );
}

if ($metode_pembayaran === '') {
    checkoutError(
        "Metode pembayaran wajib dipilih."
    );
}


/*
|--------------------------------------------------------------------------
| NAMA DAN NOMOR HP
|--------------------------------------------------------------------------
|
| Jangan menggunakan nama/no_hp dari browser sebagai sumber utama.
| Gunakan data yang tersimpan di database pembeli.
|
*/

$nama_penerima = trim(
    $pembeli['nama'] ?? ''
);

$no_hp = trim(
    $pembeli['no_hp'] ?? ''
);

if ($nama_penerima === '') {
    checkoutError(
        "Nama pembeli belum tersedia."
    );
}

if ($no_hp === '') {
    checkoutError(
        "Nomor HP pembeli belum tersedia."
    );
}


/*
|--------------------------------------------------------------------------
| KOORDINAT PENGIRIMAN
|--------------------------------------------------------------------------
|
| Koordinat dari form digunakan sebagai lokasi pengiriman.
| Nilai ongkir tetap dihitung ulang dari server.
|
*/

$latitude_pengiriman = trim(
    $_POST['latitude_pengiriman'] ?? ''
);

$longitude_pengiriman = trim(
    $_POST['longitude_pengiriman'] ?? ''
);


if (
    $latitude_pengiriman === '' ||
    $longitude_pengiriman === ''
) {

    checkoutError(
        "Lokasi pengiriman belum dipilih. Silakan klik Gunakan Lokasi Saya."
    );
}


if (
    !is_numeric($latitude_pengiriman) ||
    !is_numeric($longitude_pengiriman)
) {

    checkoutError(
        "Koordinat lokasi pengiriman tidak valid."
    );
}


$latitude_pengiriman = (float) $latitude_pengiriman;
$longitude_pengiriman = (float) $longitude_pengiriman;


if (
    $latitude_pengiriman < -90 ||
    $latitude_pengiriman > 90
) {

    checkoutError(
        "Latitude lokasi pengiriman tidak valid."
    );
}


if (
    $longitude_pengiriman < -180 ||
    $longitude_pengiriman > 180
) {

    checkoutError(
        "Longitude lokasi pengiriman tidak valid."
    );
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

if (!$stmt) {
    checkoutError(
        "Gagal mengambil keranjang."
    );
}

$stmt->bind_param(
    "i",
    $pembeli_id
);

$stmt->execute();

$keranjang = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


if (!$keranjang) {

    checkoutError(
        "Keranjang Anda masih kosong."
    );
}


$keranjang_id = (int) $keranjang['id'];


/*
|--------------------------------------------------------------------------
| MULAI TRANSAKSI
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


try {


    /*
    |--------------------------------------------------------------------------
    | AMBIL PRODUK KERANJANG
    |--------------------------------------------------------------------------
    |
    | FOR UPDATE digunakan agar stok tidak berubah
    | selama proses checkout.
    |
    */

    $stmt = $conn->prepare("
        SELECT

            kd.id AS keranjang_detail_id,

            kd.jumlah,

            p.id AS produk_id,

            p.nama_produk,

            p.harga,

            p.stok,

            p.status AS status_produk,

            p.penjual_id,

            pen.nama_usaha,

            pen.user_id AS penjual_user_id,

            pen.latitude AS penjual_latitude,

            pen.longitude AS penjual_longitude

        FROM keranjang_detail kd

        INNER JOIN produk p
            ON kd.produk_id = p.id

        INNER JOIN penjual pen
            ON p.penjual_id = pen.id

        WHERE kd.keranjang_id = ?

        FOR UPDATE
    ");


    if (!$stmt) {
        throw new Exception(
            "Gagal mengambil produk keranjang."
        );
    }


    $stmt->bind_param(
        "i",
        $keranjang_id
    );

    $stmt->execute();

    $result = $stmt->get_result();


    $items = [];

    $total_produk = 0;

    $jarak_maksimal = 0;

    $penjualYangBelumAdaLokasi = [];


    while (
        $row = $result->fetch_assoc()
    ) {


        /*
        |--------------------------------------------------------------------------
        | VALIDASI PRODUK
        |--------------------------------------------------------------------------
        */

        if (
            $row['status_produk'] !== 'tersedia'
        ) {

            throw new Exception(
                "Produk " .
                $row['nama_produk'] .
                " sudah tidak tersedia."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI JUMLAH
        |--------------------------------------------------------------------------
        */

        $jumlah = (float) $row['jumlah'];

        if ($jumlah <= 0) {

            throw new Exception(
                "Jumlah produk " .
                $row['nama_produk'] .
                " tidak valid."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI STOK
        |--------------------------------------------------------------------------
        */

        $stok = (float) $row['stok'];

        if ($jumlah > $stok) {

            throw new Exception(
                "Stok produk " .
                $row['nama_produk'] .
                " tidak mencukupi."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI LOKASI PENJUAL
        |--------------------------------------------------------------------------
        */

        if (
            $row['penjual_latitude'] === null ||
            $row['penjual_latitude'] === '' ||
            $row['penjual_longitude'] === null ||
            $row['penjual_longitude'] === ''
        ) {

            $penjualYangBelumAdaLokasi[] =
                $row['nama_usaha'];

        }


        /*
        |--------------------------------------------------------------------------
        | HITUNG SUBTOTAL
        |--------------------------------------------------------------------------
        */

        $harga = (float) $row['harga'];

        $subtotal = $harga * $jumlah;

        $total_produk += $subtotal;


        /*
        |--------------------------------------------------------------------------
        | SIMPAN ITEM
        |--------------------------------------------------------------------------
        */

        $row['jumlah_final'] = $jumlah;

        $row['harga_final'] = $harga;

        $row['subtotal_final'] = $subtotal;

        $items[] = $row;

    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | CEK KERANJANG
    |--------------------------------------------------------------------------
    */

    if (empty($items)) {

        throw new Exception(
            "Keranjang Anda masih kosong."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CEK LOKASI SEMUA PENJUAL
    |--------------------------------------------------------------------------
    */

    if (!empty($penjualYangBelumAdaLokasi)) {

        $penjualYangBelumAdaLokasi =
            array_unique(
                $penjualYangBelumAdaLokasi
            );

        throw new Exception(
            "Lokasi penjual berikut belum diatur: " .
            implode(
                ', ',
                $penjualYangBelumAdaLokasi
            ) .
            ". Silakan hubungi penjual untuk melengkapi lokasi usaha."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG JARAK PENJUAL → PEMBELI
    |--------------------------------------------------------------------------
    |
    | Jika terdapat beberapa penjual dalam satu pesanan,
    | digunakan jarak terjauh.
    |
    */

    foreach ($items as $item) {

        $latPenjual =
            (float) $item['penjual_latitude'];

        $lngPenjual =
            (float) $item['penjual_longitude'];


        $jarak =
            hitungJarakKm(
                $latitude_pengiriman,
                $longitude_pengiriman,
                $latPenjual,
                $lngPenjual
            );


        if ($jarak > $jarak_maksimal) {

            $jarak_maksimal = $jarak;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL PENGATURAN ONGKIR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            tarif_dasar,
            tarif_per_km,
            minimal_gratis,
            maksimal_jarak_km

        FROM pengaturan_ongkir

        WHERE status = 'aktif'

        ORDER BY id ASC

        LIMIT 1

        FOR UPDATE
    ");


    if (!$stmt) {

        throw new Exception(
            "Gagal mengambil pengaturan ongkir."
        );

    }


    $stmt->execute();

    $pengaturan =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();


    if (!$pengaturan) {

        throw new Exception(
            "Pengaturan ongkos kirim belum tersedia."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | KONVERSI PENGATURAN
    |--------------------------------------------------------------------------
    */

    $tarif_dasar =
        (float) $pengaturan['tarif_dasar'];

    $tarif_per_km =
        (float) $pengaturan['tarif_per_km'];

    $minimal_gratis =
        (float) $pengaturan['minimal_gratis'];

    $maksimal_jarak_km =
        (float) $pengaturan['maksimal_jarak_km'];


    /*
    |--------------------------------------------------------------------------
    | CEK BATAS JARAK
    |--------------------------------------------------------------------------
    */

    if (
        $maksimal_jarak_km > 0 &&
        $jarak_maksimal > $maksimal_jarak_km
    ) {

        throw new Exception(
            "Jarak pengiriman " .
            number_format(
                $jarak_maksimal,
                2,
                ',',
                '.'
            ) .
            " km melebihi batas layanan " .
            number_format(
                $maksimal_jarak_km,
                2,
                ',',
                '.'
            ) .
            " km."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG ONGKIR
    |--------------------------------------------------------------------------
    */

    $ongkos_kirim =
        hitungOngkosKirim(
            $jarak_maksimal,
            $total_produk,
            $tarif_dasar,
            $tarif_per_km,
            $minimal_gratis,
            $maksimal_jarak_km
        );


    if ($ongkos_kirim === null) {

        throw new Exception(
            "Lokasi pengiriman berada di luar jangkauan layanan."
        );

    }


    $ongkos_kirim =
        (float) $ongkos_kirim;


    /*
    |--------------------------------------------------------------------------
    | TOTAL PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    $total_harga =
        $total_produk +
        $ongkos_kirim;


    /*
    |--------------------------------------------------------------------------
    | GENERATE KODE PESANAN
    |--------------------------------------------------------------------------
    */

    $kode_pesanan =
        'PSN-' .
        date('YmdHis') .
        '-' .
        strtoupper(
            substr(
                bin2hex(
                    random_bytes(3)
                ),
                0,
                6
            )
        );


    /*
    |--------------------------------------------------------------------------
    | SIMPAN PESANAN
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO pesanan
        (
            kode_pesanan,
            pembeli_id,
            nama_penerima,
            no_hp,
            tanggal_pesanan,
            total_harga,
            alamat_pengiriman,
            catatan,
            metode_pembayaran,
            status,
            latitude_pengiriman,
            longitude_pengiriman,
            jarak_km,
            ongkos_kirim
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            ?,
            'menunggu',
            ?,
            ?,
            ?,
            ?
        )
    ");


    if (!$stmt) {

        throw new Exception(
            "Gagal menyiapkan penyimpanan pesanan."
        );

    }


    $stmt->bind_param(
        "sissdsssdddd",
        $kode_pesanan,
        $pembeli_id,
        $nama_penerima,
        $no_hp,
        $total_harga,
        $alamat_pengiriman,
        $catatan,
        $metode_pembayaran,
        $latitude_pengiriman,
        $longitude_pengiriman,
        $jarak_maksimal,
        $ongkos_kirim
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Gagal menyimpan pesanan."
        );

    }


    $pesanan_id =
        (int) $conn->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SIMPAN DETAIL PESANAN
    |--------------------------------------------------------------------------
    */

    $stmtDetail = $conn->prepare("
        INSERT INTO detail_pesanan
        (
            pesanan_id,
            produk_id,
            jumlah,
            harga,
            subtotal
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    if (!$stmtDetail) {

        throw new Exception(
            "Gagal menyiapkan detail pesanan."
        );

    }


    foreach ($items as $item) {

        $produk_id =
            (int) $item['produk_id'];

        $jumlah =
            (float) $item['jumlah_final'];

        $harga =
            (float) $item['harga_final'];

        $subtotal =
            (float) $item['subtotal_final'];


        $stmtDetail->bind_param(
            "iiddd",
            $pesanan_id,
            $produk_id,
            $jumlah,
            $harga,
            $subtotal
        );


        if (!$stmtDetail->execute()) {

            throw new Exception(
                "Gagal menyimpan detail produk."
            );

        }

    }


    $stmtDetail->close();


    /*
    |--------------------------------------------------------------------------
    | KURANGI STOK
    |--------------------------------------------------------------------------
    */

    $stmtStok = $conn->prepare("
        UPDATE produk

        SET
            stok = stok - ?,
            status =
                CASE
                    WHEN stok - ? <= 0
                    THEN 'habis'
                    ELSE status
                END

        WHERE id = ?
    ");


    if (!$stmtStok) {

        throw new Exception(
            "Gagal menyiapkan pembaruan stok."
        );

    }


    foreach ($items as $item) {

        $produk_id =
            (int) $item['produk_id'];

        $jumlah =
            (float) $item['jumlah_final'];


        $stmtStok->bind_param(
            "ddi",
            $jumlah,
            $jumlah,
            $produk_id
        );


        if (!$stmtStok->execute()) {

            throw new Exception(
                "Gagal memperbarui stok produk."
            );

        }

    }


    $stmtStok->close();


    /*
    |--------------------------------------------------------------------------
    | HAPUS KERANJANG
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM keranjang_detail
        WHERE keranjang_id = ?
    ");


    if (!$stmt) {

        throw new Exception(
            "Gagal membersihkan keranjang."
        );

    }


    $stmt->bind_param(
        "i",
        $keranjang_id
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Gagal membersihkan keranjang."
        );

    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI PEMBELI
    |--------------------------------------------------------------------------
    */

    tambahNotifikasi(
        $conn,
        $user_id,
        "Pesanan Berhasil Dibuat",
        "Pesanan {$kode_pesanan} berhasil dibuat dengan total " .
        rupiah($total_harga) .
        ".",
        "pesanan",
        "detail_pesanan.php?id=" . $pesanan_id
    );


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | REDIRECT BERHASIL
    |--------------------------------------------------------------------------
    */

    header(
        "Location: pesanan_sukses.php?id=" .
        $pesanan_id
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    /*
    |--------------------------------------------------------------------------
    | REDIRECT ERROR
    |--------------------------------------------------------------------------
    */

    checkoutError(
        $e->getMessage()
    );

}