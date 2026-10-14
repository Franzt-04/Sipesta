<?php
require_once '../config/database.php';
require_once '../config/auth.php';
wajibRole('kurir');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_kurir'])) $_SESSION['csrf_kurir'] = bin2hex(random_bytes(32));
$uid=(int)userId();
$s=$conn->prepare('SELECT id,nama_kurir,status FROM kurir WHERE user_id=? LIMIT 1');$s->bind_param('i',$uid);$s->execute();$kurir=$s->get_result()->fetch_assoc();$s->close();
if (!$kurir || $kurir['status'] !== 'aktif') { http_response_code(403); exit('Profil kurir belum tersedia atau tidak aktif. Hubungi admin.'); }
$kid=(int)$kurir['id'];
$s=$conn->prepare("SELECT pg.id,pg.pesanan_id,pg.pesanan_penjual_id,pg.STATUS AS status_kirim,pg.tanggal_kirim,pg.tanggal_sampai,pg.lokasi_diperbarui_at,ps.kode_pesanan,ps.nama_penerima,ps.no_hp,ps.alamat_pengiriman,ps.latitude_pengiriman,ps.longitude_pengiriman,ps.status_pengiriman,pp.kode_sub_pesanan,pen.nama_usaha FROM pengiriman pg JOIN pesanan ps ON ps.id=pg.pesanan_id LEFT JOIN pesanan_penjual pp ON pp.id=pg.pesanan_penjual_id LEFT JOIN penjual pen ON pen.id=pp.penjual_id WHERE pg.kurir_id=? AND pg.metode_pengiriman='diantar_kurir' AND ps.STATUS <> 'dibatalkan' ORDER BY pg.id DESC");
$s->bind_param('i',$kid);$s->execute();$tugas=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Kurir - SIPESTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: #f4f7f8
    }

    .card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 5px 20px #0001
    }

    .small-muted {
        font-size: .9rem;
        color: #667
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-success">
        <div class="container"><span class="navbar-brand">SIPESTA • Dashboard Kurir</span><span
                class="text-white"><?=h($kurir['nama_kurir'])?></span></div>
    </nav>
    <main class="container py-4">
        <h3 class="mb-3">Tugas Pengiriman <span class="badge bg-success"><?=count($tugas)?></span></h3>
        <?php if(isset($_GET['ok'])): ?><div class="alert alert-success">Perubahan berhasil disimpan.</div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?><div class="alert alert-danger"><?=h($_GET['error'])?></div><?php endif; ?>
        <?php if(!$tugas): ?><div class="alert alert-info">Belum ada pengiriman yang ditugaskan admin.</div>
        <?php endif; ?>
        <div class="row g-3"><?php foreach($tugas as $t): ?>
            <div class="col-lg-6">
                <section class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2">
                            <h5><?=h($t['kode_sub_pesanan'] ?: $t['kode_pesanan'])?></h5><span
                                class="badge bg-secondary align-self-start"><?=h($t['status_kirim'])?></span>
                        </div>
                        <p class="small-muted mb-1">Toko: <?=h($t['nama_usaha'] ?: 'Tidak diketahui')?></p>
                        <p class="mb-1"><strong>Penerima:</strong> <?=h($t['nama_penerima'])?></p>
                        <p class="mb-1"><strong>Telepon:</strong> <?=h($t['no_hp'])?></p>
                        <p><strong>Alamat:</strong> <?=nl2br(h($t['alamat_pengiriman']))?></p>
                        <?php if($t['latitude_pengiriman']!==null && $t['longitude_pengiriman']!==null): ?>
                        <a class="btn btn-outline-primary btn-sm mb-3" target="_blank" rel="noopener noreferrer"
                            href="https://www.google.com/maps/dir/?api=1&amp;destination=<?=rawurlencode($t['latitude_pengiriman'].','.$t['longitude_pengiriman'])?>">Petunjuk
                            Arah</a>
                        <?php endif; ?>
                        <form method="post" action="aksi.php" class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_kurir'])?>"><input
                                type="hidden" name="pengiriman_id" value="<?=(int)$t['id']?>">
                            <?php if(in_array($t['status_kirim'],['Belum Dikirim','Diproses'],true)): ?><button
                                class="btn btn-primary btn-sm" name="aksi" value="mulai">Mulai
                                Pengiriman</button><?php endif; ?>
                            <?php if($t['status_kirim']==='Dikirim'): ?><button type="button"
                                class="btn btn-outline-success btn-sm"
                                onclick="kirimLokasi(<?=(int)$t['id']?>,this)">Perbarui GPS</button><button
                                class="btn btn-success btn-sm" name="aksi" value="selesai"
                                onclick="return confirm('Pastikan barang sudah diterima. Selesaikan pengiriman?')">Barang
                                Diterima</button><?php endif; ?>
                        </form>
                        <p class="small-muted mt-2 mb-0" id="gps-<?=(int)$t['id']?>">Lokasi terakhir:
                            <?=h($t['lokasi_diperbarui_at'] ?: 'belum ada')?></p>
                    </div>
                </section>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
    const csrf = <?=json_encode($_SESSION['csrf_kurir'])?>;

    function kirimLokasi(id, btn) {
        const out = document.getElementById('gps-' + id);
        if (!navigator.geolocation) {
            out.textContent = 'Browser tidak mendukung GPS.';
            return;
        }
        btn.disabled = true;
        out.textContent = 'Meminta izin lokasi…';
        navigator.geolocation.getCurrentPosition(async p => {
            try {
                const fd = new FormData();
                fd.append('csrf', csrf);
                fd.append('pengiriman_id', id);
                fd.append('aksi', 'lokasi');
                fd.append('latitude', p.coords.latitude);
                fd.append('longitude', p.coords.longitude);
                const r = await fetch('aksi.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const j = await r.json();
                out.textContent = j.message || 'Gagal memperbarui lokasi.';
            } catch (e) {
                out.textContent = 'Gagal menghubungi server.';
            } finally {
                btn.disabled = false;
            }
        }, e => {
            out.textContent = 'Izin GPS gagal: ' + e.message;
            btn.disabled = false;
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        });
    }
    </script>
</body>

</html>