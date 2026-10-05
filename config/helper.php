<?php

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format(
        $angka,
        0,
        ',',
        '.'
    );
}


/*
|--------------------------------------------------------------------------
| HITUNG JARAK ANTARA 2 KOORDINAT
|--------------------------------------------------------------------------
| Menggunakan rumus Haversine.
| Hasil dikembalikan dalam kilometer.
*/

function hitungJarakKm(
    $latitude1,
    $longitude1,
    $latitude2,
    $longitude2
) {
    $earthRadius = 6371; // radius bumi dalam kilometer

    $latitude1  = (float) $latitude1;
    $longitude1 = (float) $longitude1;
    $latitude2  = (float) $latitude2;
    $longitude2 = (float) $longitude2;

    $latFrom = deg2rad($latitude1);
    $latTo   = deg2rad($latitude2);

    $latDelta = deg2rad(
        $latitude2 - $latitude1
    );

    $lonDelta = deg2rad(
        $longitude2 - $longitude1
    );

    $a =
        sin($latDelta / 2) ** 2
        +
        cos($latFrom)
        *
        cos($latTo)
        *
        sin($lonDelta / 2) ** 2;

    $c = 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );

    return round(
        $earthRadius * $c,
        2
    );
}


/*
|--------------------------------------------------------------------------
| HITUNG ONGKOS KIRIM
|--------------------------------------------------------------------------
*/

function hitungOngkosKirim(
    $jarakKm,
    $totalProduk,
    $tarifDasar,
    $tarifPerKm,
    $minimalGratis,
    $maksimalJarakKm
) {

    $jarakKm       = max(0, (float) $jarakKm);
    $totalProduk   = max(0, (float) $totalProduk);
    $tarifDasar    = max(0, (float) $tarifDasar);
    $tarifPerKm    = max(0, (float) $tarifPerKm);
    $minimalGratis = max(0, (float) $minimalGratis);
    $maksimalJarak = max(0, (float) $maksimalJarakKm);


    /*
    |--------------------------------------------------------------------------
    | GRATIS ONGKIR
    |--------------------------------------------------------------------------
    */

    if (
        $minimalGratis > 0 &&
        $totalProduk >= $minimalGratis
    ) {
        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | BATAS JARAK
    |--------------------------------------------------------------------------
    */

    if (
        $maksimalJarak > 0 &&
        $jarakKm > $maksimalJarak
    ) {
        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG ONGKIR
    |--------------------------------------------------------------------------
    */

    $ongkir =
        $tarifDasar
        +
        ($jarakKm * $tarifPerKm);


    return round(
        $ongkir,
        0
    );
}