<?php
require_once "../config/database.php";
require_once "../config/auth.php";
require_once "../config/helper.php";
wajibRole("penjual");
$user_id = userId();
$stmt = $conn->prepare("SELECT id,nama_usaha,alamat,latitude,longitude FROM penjual WHERE user_id=? LIMIT 1");
if (!$stmt) die("Gagal menyiapkan data penjual.");
$stmt->bind_param("i",$user_id); $stmt->execute();
$penjual=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$penjual) die("Data penjual tidak ditemukan.");
$pesan=""; $tipePesan="success";
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $latitude=trim($_POST['latitude']??''); $longitude=trim($_POST['longitude']??'');
  if ($latitude==='' || $longitude==='' || !is_numeric($latitude) || !is_numeric($longitude)) { $pesan="Latitude dan longitude wajib diisi dengan koordinat yang valid."; $tipePesan="danger"; }
  else { $latitude=(float)$latitude; $longitude=(float)$longitude;
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) { $pesan="Koordinat berada di luar batas yang valid."; $tipePesan="danger"; }
    else { $stmt=$conn->prepare("UPDATE penjual SET latitude=?, longitude=? WHERE user_id=?");
      if (!$stmt) { $pesan="Gagal menyiapkan penyimpanan lokasi."; $tipePesan="danger"; }
      else { $stmt->bind_param("ddi",$latitude,$longitude,$user_id);
        if ($stmt->execute()) { $pesan="Lokasi usaha berhasil disimpan."; $penjual['latitude']=$latitude; $penjual['longitude']=$longitude; }
        else { $pesan="Lokasi usaha gagal disimpan."; $tipePesan="danger"; }
        $stmt->close();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Lokasi Usaha - SIPESTA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body{background:#f5f7fb}.page-card{border:none;border-radius:18px;box-shadow:0 4px 18px rgba(0,0,0,.06)}
.location-card{border:1px solid #e5e7eb;border-radius:15px;background:#fbfffc}.coordinate-box{background:#f8f9fa;border-radius:12px;padding:15px}.coordinate-value{font-weight:700;word-break:break-word}.status-box{border-radius:12px}.location-icon{width:55px;height:55px;border-radius:15px;display:flex;align-items:center;justify-content:center;background:#e8f5e9;color:#198754;font-size:24px;flex-shrink:0}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm"><div class="container">
<a href="index.php" class="navbar-brand fw-bold text-success"><i class="fa-solid fa-store"></i> SIPESTA</a>
<a href="index.php" class="btn btn-outline-success"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
</div></nav>
<div class="container py-4">
<div class="mb-4"><h3 class="fw-bold mb-1"><i class="fa-solid fa-location-dot text-danger"></i> Lokasi Usaha</h3><p class="text-muted mb-0">Atur lokasi usaha untuk membantu sistem menghitung jarak dan ongkos kirim kepada pembeli.</p></div>
<?php if($pesan!==''): ?><div class="alert alert-<?=e($tipePesan)?> alert-dismissible fade show"><i class="fa-solid <?= $tipePesan==='success'?'fa-circle-check':'fa-circle-exclamation'?>"></i> <?=e($pesan)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-8"><div class="card page-card"><div class="card-body p-4">
<div class="d-flex gap-3 align-items-start mb-4"><div class="location-icon"><i class="fa-solid fa-map-location-dot"></i></div><div><h5 class="fw-bold mb-1"><?=e($penjual['nama_usaha'])?></h5><div class="text-muted"><i class="fa-solid fa-location-dot"></i> <?=e($penjual['alamat']?:'Alamat belum tersedia')?></div></div></div>
<div id="statusLokasi" class="alert alert-secondary status-box"><i class="fa-solid fa-circle-info"></i> Silakan ambil lokasi usaha menggunakan GPS perangkat.</div>
<form method="POST" id="formLokasi">
<input type="hidden" name="latitude" id="latitude" value="<?=e($penjual['latitude']??'')?>"><input type="hidden" name="longitude" id="longitude" value="<?=e($penjual['longitude']??'')?>">
<div class="d-flex flex-wrap gap-2 mb-4"><button type="button" class="btn btn-primary" id="btnLokasi"><i class="fa-solid fa-crosshairs"></i> Gunakan Lokasi Saya</button><button type="button" class="btn btn-outline-secondary" id="btnHapus"><i class="fa-solid fa-xmark"></i> Hapus Lokasi</button><button type="submit" class="btn btn-success" id="btnSimpan" disabled><i class="fa-solid fa-save"></i> Simpan Lokasi</button></div>
<div class="row g-3"><div class="col-md-6"><div class="coordinate-box"><small class="text-muted d-block mb-1">Latitude</small><div id="latitudeTampil" class="coordinate-value"><?=e($penjual['latitude']??'-')?></div></div></div><div class="col-md-6"><div class="coordinate-box"><small class="text-muted d-block mb-1">Longitude</small><div id="longitudeTampil" class="coordinate-value"><?=e($penjual['longitude']??'-')?></div></div></div></div>
<div class="mt-4 p-3 bg-light rounded-3 small text-muted"><i class="fa-solid fa-shield-halved text-success"></i> Koordinat ini digunakan SIPESTA untuk menghitung jarak antara lokasi usaha Anda dengan lokasi pengiriman pembeli.</div>
</form></div></div></div>
<div class="col-lg-4"><div class="card page-card"><div class="card-body p-4"><h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-question text-success"></i> Informasi</h5><div class="small text-muted"><p><i class="fa-solid fa-1 text-success me-2"></i> Aktifkan GPS pada perangkat.</p><p><i class="fa-solid fa-2 text-success me-2"></i> Klik <strong>Gunakan Lokasi Saya</strong>.</p><p><i class="fa-solid fa-3 text-success me-2"></i> Periksa koordinat yang muncul.</p><p class="mb-0"><i class="fa-solid fa-4 text-success me-2"></i> Klik <strong>Simpan Lokasi</strong>.</p></div><hr><div class="small"><div class="fw-semibold mb-2">Fungsi lokasi:</div><ul class="text-muted ps-3 mb-0"><li>Menghitung jarak pengiriman</li><li>Menghitung ongkos kirim</li><li>Menentukan jangkauan pengiriman</li></ul></div></div></div></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const b=document.getElementById('btnLokasi'),h=document.getElementById('btnHapus'),s=document.getElementById('btnSimpan'),lat=document.getElementById('latitude'),lng=document.getElementById('longitude'),lt=document.getElementById('latitudeTampil'),gt=document.getElementById('longitudeTampil'),st=document.getElementById('statusLokasi');
 function status(t,i,m){st.className='alert alert-'+t+' status-box';st.innerHTML='<i class="fa-solid '+i+'"></i> '+m}
 function update(){lt.textContent=lat.value.trim()||'-';gt.textContent=lng.value.trim()||'-';s.disabled=!(lat.value.trim()&&lng.value.trim())}
 b.addEventListener('click',()=>{if(!navigator.geolocation){status('danger','fa-triangle-exclamation','Browser Anda tidak mendukung fitur lokasi.');return}b.disabled=true;b.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Mengambil lokasi...';status('info','fa-location-crosshairs','Sedang mengambil lokasi perangkat. Pastikan GPS aktif.');navigator.geolocation.getCurrentPosition(p=>{lat.value=Number(p.coords.latitude).toFixed(8);lng.value=Number(p.coords.longitude).toFixed(8);update();status('success','fa-circle-check','Lokasi berhasil ditemukan. Klik Simpan Lokasi untuk menyimpannya.');b.disabled=false;b.innerHTML='<i class="fa-solid fa-check"></i> Lokasi Berhasil Diambil'},e=>{let m='Gagal mengambil lokasi perangkat.';if(e.code===e.PERMISSION_DENIED)m='Akses lokasi ditolak. Izinkan lokasi pada browser lalu coba lagi.';else if(e.code===e.POSITION_UNAVAILABLE)m='Lokasi perangkat tidak tersedia. Pastikan GPS aktif.';else if(e.code===e.TIMEOUT)m='Waktu pengambilan lokasi habis. Silakan coba lagi.';status('danger','fa-triangle-exclamation',m);b.disabled=false;b.innerHTML='<i class="fa-solid fa-crosshairs"></i> Gunakan Lokasi Saya'},{enableHighAccuracy:true,timeout:15000,maximumAge:0})});
 h.addEventListener('click',()=>{lat.value='';lng.value='';update();status('warning','fa-triangle-exclamation','Lokasi dihapus dari formulir. Klik Simpan Lokasi untuk menghapus lokasi dari database.')});
 update(); if(lat.value.trim()&&lng.value.trim())status('success','fa-circle-check','Lokasi usaha sudah tersimpan.');
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
