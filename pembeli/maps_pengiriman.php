<?php

declare(strict_types=1);

require_once '../config/database.php';

require_once '../config/auth.php';

wajibRole('pembeli');



function mapEsc($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function mapValid($lat, $lon): bool {

    return $lat !== null && $lon !== null && $lat !== '' && $lon !== ''

        && is_numeric($lat) && is_numeric($lon)

        && (float)$lat >= -90 && (float)$lat <= 90 && (float)$lon >= -180 && (float)$lon <= 180;

}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) { http_response_code(400); exit('ID pesanan tidak valid.'); }

$userId = (int)userId();

$stmt = $conn->prepare('SELECT ps.id, ps.kode_pesanan, ps.alamat_pengiriman, ps.latitude_pengiriman, ps.longitude_pengiriman, ps.metode_pengiriman, ps.status_pengiriman, ps.status, ps.jarak_km, ps.ongkos_kirim FROM pesanan ps JOIN pembeli pb ON pb.id=ps.pembeli_id WHERE ps.id=? AND pb.user_id=? LIMIT 1');

$stmt->bind_param('ii', $id, $userId);

$stmt->execute(); $order = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$order) { http_response_code(404); exit('Pesanan tidak ditemukan atau bukan milik Anda.'); }



// Ambil toko dari subpesanan. Pesanan lama yang belum memiliki subpesanan

// tetap dapat melihat toko berdasarkan produk dalam detail_pesanan.

$stmt = $conn->prepare('SELECT pp.id AS sub_id, pp.kode_sub_pesanan, pp.status AS status_toko,

    pen.id AS penjual_id, pen.nama_usaha, pen.alamat AS alamat_toko,

    pen.latitude AS lat_toko, pen.longitude AS lon_toko,

    pg.id AS pengiriman_id, pg.metode_pengiriman AS metode_toko,

    pg.jarak_km AS jarak_toko, pg.ongkos_kirim AS ongkir_toko,

    pg.latitude_terakhir AS lat_pengantar, pg.longitude_terakhir AS lon_pengantar,

    pg.lokasi_diperbarui_at, pg.STATUS AS status_pengiriman_toko

    FROM pesanan_penjual pp JOIN penjual pen ON pen.id=pp.penjual_id

    LEFT JOIN pengiriman pg ON pg.id=(SELECT MAX(g2.id) FROM pengiriman g2 WHERE g2.pesanan_penjual_id=pp.id)

    WHERE pp.pesanan_id=? ORDER BY pp.id ASC');

$stmt->bind_param('i', $id); $stmt->execute(); $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();



// Fallback hanya untuk penjual yang belum ditemukan lewat pesanan_penjual.

$known = [];

foreach ($rows as $row) $known[(int)$row['penjual_id']] = true;

$stmt = $conn->prepare('SELECT DISTINCT pen.id AS penjual_id, pen.nama_usaha,

    pen.alamat AS alamat_toko, pen.latitude AS lat_toko, pen.longitude AS lon_toko

    FROM detail_pesanan dp JOIN produk pr ON pr.id=dp.produk_id

    JOIN penjual pen ON pen.id=pr.penjual_id WHERE dp.pesanan_id=? ORDER BY pen.id');

$stmt->bind_param('i',$id); $stmt->execute(); $fallback=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

foreach ($fallback as $r) {

    if (isset($known[(int)$r['penjual_id']])) continue;

    $rows[] = array_merge($r, [

        'sub_id'=>null, 'kode_sub_pesanan'=>null, 'status_toko'=>$order['status'],

        'pengiriman_id'=>null, 'metode_toko'=>null, 'jarak_toko'=>null,

        'ongkir_toko'=>null, 'lat_pengantar'=>null, 'lon_pengantar'=>null,

        'lokasi_diperbarui_at'=>null, 'status_pengiriman_toko'=>null

    ]);

}

$shops=[];

foreach ($rows as $row) {

    $hasShopCoords=mapValid($row['lat_toko'],$row['lon_toko']);

    $hasCourierCoords=mapValid($row['lat_pengantar'],$row['lon_pengantar']);

    $shops[]=[

        'name'=>$row['nama_usaha'] ?: 'Toko',

        'address'=>$row['alamat_toko'] ?: 'Alamat toko belum tersedia',

        'lat'=>$hasShopCoords ? (float)$row['lat_toko'] : null,

        'lon'=>$hasShopCoords ? (float)$row['lon_toko'] : null,

        'method'=>$row['metode_toko'] ?: $order['metode_pengiriman'],

        'distance'=>$row['pengiriman_id'] !== null ? (float)$row['jarak_toko'] : null,

        'fee'=>$row['pengiriman_id'] !== null ? (float)$row['ongkir_toko'] : null,

        'status'=>$row['status_pengiriman_toko'] ?: $row['status_toko'],

        'courierLat'=>$hasCourierCoords ? (float)$row['lat_pengantar'] : null,

        'courierLon'=>$hasCourierCoords ? (float)$row['lon_pengantar'] : null,

        'updated'=>$row['lokasi_diperbarui_at'],

        'sub'=>$row['kode_sub_pesanan'],

        'recorded'=>$row['pengiriman_id'] !== null

    ];

}

$destination=mapValid($order['latitude_pengiriman'],$order['longitude_pengiriman'])

    ? ['lat'=>(float)$order['latitude_pengiriman'],'lon'=>(float)$order['longitude_pengiriman']] : null;

$payload=json_encode(['shops'=>$shops,'destination'=>$destination,'method'=>$order['metode_pengiriman']],

    JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE);

?>

<!doctype html>

<html lang="id">



<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Maps Pengiriman - SIPESTA</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

    <script>
    window.addEventListener('DOMContentLoaded', function () {
      const probe = document.createElement('div');
      probe.className = 'leaflet-pane';
      probe.style.visibility = 'hidden';
      document.body.appendChild(probe);
      const ok = getComputedStyle(probe).position === 'absolute';
      probe.remove();
      if (!ok) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        link.onload = () => { if (window.sipestaMap) window.sipestaMap.invalidateSize(true); };
        document.head.appendChild(link);
      }
    });
    </script>
    <style>

    body {

        background: #f5f7fb

    }



    .card {

        border: 0;

        border-radius: 14px;

        box-shadow: 0 4px 18px #0000000b

    }



    .map-wrapper {

        position: relative;

        width: 100%;

        height: 470px;

        overflow: hidden;

        border-radius: 14px

    }



    #map {

        width: 100%;

        height: 100%;

        z-index: 1;

        background: #e9ecef

    }

    .leaflet-container img.leaflet-tile { max-width: none !important; max-height: none !important; }

    .shop-item {

        cursor: pointer

    }



    .shop-item.active {

        border-color: #198754 !important;

        background: #effbf4

    }



    .small-muted {

        font-size: .87rem;

        color: #6c757d

    }



    @media(max-width:768px) {

        .map-wrapper {

            height: 350px

        }

    }

    </style>

</head>



<body>

    <nav class="navbar bg-white shadow-sm">

        <div class="container"><a href="index.php" class="navbar-brand fw-bold text-success">SIPESTA</a><a

                class="btn btn-outline-success" href="detail_pesanan.php?id=<?= (int)$id ?>">← Detail Pesanan</a></div>

    </nav>

    <main class="container py-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

            <div>

                <h3 class="fw-bold mb-1">🗺️ Maps Pengiriman &amp; Pengambilan</h3>

                <div class="text-muted">Pesanan <?= mapEsc($order['kode_pesanan']) ?></div>

            </div>

            <span

                class="badge text-bg-success fs-6"><?= mapEsc(str_replace('_',' ',(string)$order['metode_pengiriman'])) ?></span>

        </div>

        <div class="row g-4">

            <div class="col-lg-8">

                <div class="card">

                    <div class="card-body">

                        <div class="map-wrapper">

                            <div id="map"></div>

                        </div>

                        <p class="small-muted mt-3 mb-1" id="routeInfo">Pilih toko untuk melihat lokasi dan rute.</p>

                        <p class="small-muted mb-0">Rute hanya panduan perjalanan, bukan dasar ongkir tersimpan. Posisi

                            pengantar ditampilkan jika telah dibagikan.</p>

                    </div>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="card mb-3">

                    <div class="card-body">

                        <h5 class="fw-bold">Informasi Pesanan</h5>

                        <div class="mb-2">Status:

                            <strong><?= mapEsc(str_replace('_',' ',(string)$order['status_pengiriman'])) ?></strong>

                        </div>

                        <div class="mb-2">Ongkir pesanan:

                            <strong>Rp<?= number_format((float)$order['ongkos_kirim'],0,',','.') ?></strong>

                        </div>

                        <div class="mb-2">Jarak tercatat:

                            <strong><?= $order['jarak_km']===null ? '-' : number_format((float)$order['jarak_km'],2,',','.') . ' km' ?></strong>

                        </div>

                        <div class="small-muted">Tujuan: <?= mapEsc($order['alamat_pengiriman']) ?></div>

                    </div>

                </div>

                <div class="card">

                    <div class="card-body">

                        <h5 class="fw-bold">Lokasi Toko</h5>

                        <div id="shops"></div>

                    </div>

                </div>

            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>

    const data = <?= $payload ?: '{}' ?>;

    const map = L.map('map').setView([-2.5, 118], 5);
    window.sipestaMap = map;

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {

        maxZoom: 19,

        attribution: '&copy; OpenStreetMap contributors'

    }).addTo(map);

    const markers = L.featureGroup().addTo(map);

    let routeLayer = null,

        currentShop = -1;

    const shopsBox = document.getElementById('shops'),

        routeInfo = document.getElementById('routeInfo');

    const safe = str => String(str ?? '').replace(/[&<>"']/g, c => ({

        '&': '&amp;',

        '<': '&lt;',

        '>': '&gt;',

        '"': '&quot;',

        "'": '&#39;'

    } [c]));

    const valid = (lat, lon) => typeof lat === 'number' && typeof lon === 'number' && Number.isFinite(lat) && Number

        .isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180;



    function selectShop(index) {

        currentShop = index;

        document.querySelectorAll('.shop-item').forEach((el, i) => el.classList.toggle('active', i === index));

        markers.clearLayers();

        if (routeLayer) {

            map.removeLayer(routeLayer);

            routeLayer = null;

        }

        const s = data.shops[index];

        if (!s) return;

        const bounds = [];

        if (valid(s.lat, s.lon)) {

            L.marker([s.lat, s.lon]).bindPopup('<strong>' + safe(s.name) + '</strong><br>' + safe(s.address)).addTo(

                markers);

            bounds.push([s.lat, s.lon]);

        }

        const pickup = s.method === 'ambil_sendiri';

        if (!pickup && data.destination && valid(data.destination.lat, data.destination.lon)) {

            L.marker([data.destination.lat, data.destination.lon]).bindPopup('Tujuan pengiriman').addTo(markers);

            bounds.push([data.destination.lat, data.destination.lon]);

        }

        if (!pickup && valid(s.courierLat, s.courierLon)) {

            L.circleMarker([s.courierLat, s.courierLon], {

                radius: 9,

                color: '#f59e0b',

                fillOpacity: .9

            }).bindPopup('Posisi pengantar terakhir: ' + safe(s.updated || 'Waktu tidak tersedia')).addTo(markers);

            bounds.push([s.courierLat, s.courierLon]);

        }

        if (bounds.length === 1) map.setView(bounds[0], 15);

        else if (bounds.length > 1) map.fitBounds(bounds, {

            padding: [24, 24],

            maxZoom: 15

        });

        setTimeout(() => map.invalidateSize(), 100);

        if (!valid(s.lat, s.lon)) {

            routeInfo.textContent = 'Koordinat toko belum diatur oleh penjual.';

            return;

        }

        if (pickup) {

            routeInfo.textContent =

                'Ambil sendiri: kunjungi lokasi toko. Ongkos kirim Rp0. Gunakan Petunjuk Arah di daftar toko.';

            return;

        }

        if (!data.destination || !valid(data.destination.lat, data.destination.lon)) {

            routeInfo.textContent = 'Koordinat tujuan belum tersimpan pada pesanan ini.';

            return;

        }

        routeInfo.textContent = 'Memuat estimasi rute jalan...';

        const url = 'https://router.project-osrm.org/route/v1/driving/' + s.lon + ',' + s.lat + ';' + data.destination

            .lon + ',' + data.destination.lat + '?overview=full&geometries=geojson';

        fetch(url).then(r => {

            if (!r.ok) throw Error('Rute tidak tersedia');

            return r.json();

        }).then(j => {

            if (currentShop !== index) return;

            const r = j.routes?.[0];

            if (!r) throw Error('Rute tidak ditemukan');

            routeLayer = L.geoJSON(r.geometry, {

                style: {

                    color: '#198754',

                    weight: 5

                }

            }).addTo(map);

            map.fitBounds(routeLayer.getBounds().pad(.15));

            routeInfo.textContent = 'Estimasi rute jalan: ' + (r.distance / 1000).toFixed(2) + ' km · ' + Math

                .round(r.duration / 60) + ' menit (tanpa data lalu lintas langsung).';

        }).catch(() => {

            if (currentShop === index) routeInfo.textContent =

                'Rute jalan belum dapat ditampilkan. Titik toko dan tujuan tetap tersedia.';

        });

    }

    (data.shops || []).forEach((s, i) => {

        const pickup = s.method === 'ambil_sendiri';

        const nav = valid(s.lat, s.lon) ? 'https://www.google.com/maps/dir/?api=1&destination=' +

            encodeURIComponent(s.lat + ',' + s.lon) : null;

        const el = document.createElement('div');

        el.className = 'shop-item border rounded p-3 mb-2';

        el.innerHTML = '<div class="fw-semibold">' + safe(s.name) + '</div><div class="small-muted">' + safe(s

                .address) + '</div>' +

            '<div class="small-muted mt-1">' + (pickup ? '🏪 Ambil sendiri' : '🚚 ' + safe(s.method.replaceAll(

                '_', ' '))) + ' · ' + safe(s.status) + '</div>' +

            (!s.recorded ?

                '<div class="small text-warning mt-1">Data pengiriman belum tercatat untuk toko ini.</div>' : ''

            ) +

            '<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-success select-btn">Lihat Peta</button> ' +

            (nav ?

                '<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" href="' +

                nav + '">Petunjuk Arah</a>' : '<span class="small-muted">Titik toko belum tersedia</span>') +

            '</div>';

        el.querySelector('.select-btn').addEventListener('click', () => selectShop(i));

        shopsBox.appendChild(el);

    });

    if (data.shops?.length) selectShop(0);

    else {

        shopsBox.textContent = 'Belum ada toko yang dapat diidentifikasi dari detail pesanan.';

        routeInfo.textContent = 'Lokasi toko belum tersedia.';

    }

    window.addEventListener("load", () => {

        setTimeout(() => map.invalidateSize(true), 300);

    });

    setTimeout(() => map.invalidateSize(true), 500);

    </script>

</body>



</html>