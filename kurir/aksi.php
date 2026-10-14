<?php
require_once '../config/database.php';
require_once '../config/auth.php';
wajibRole('kurir');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$isJson=($_POST['aksi']??'')==='lokasi';
function gagal($msg,$json=false){if($json){http_response_code(400);header('Content-Type: application/json');echo json_encode(['ok'=>false,'message'=>$msg]);}else{header('Location: index.php?error='.urlencode($msg));}exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') gagal('Metode tidak diizinkan.',$isJson);
if(!hash_equals($_SESSION['csrf_kurir']??'',(string)($_POST['csrf']??'')) || empty($_SESSION['csrf_kurir'])) gagal('Sesi tidak valid, muat ulang halaman.',$isJson);
$id=filter_var($_POST['pengiriman_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$aksi=(string)($_POST['aksi']??'');if(!$id||!in_array($aksi,['mulai','lokasi','selesai'],true)) gagal('Permintaan tidak valid.',$isJson);
$uid=(int)userId();
try{
$conn->begin_transaction();
$s=$conn->prepare("SELECT pg.id,pg.STATUS,pg.pesanan_id,pg.pesanan_penjual_id,ps.STATUS AS status_pesanan FROM pengiriman pg JOIN kurir k ON k.id=pg.kurir_id JOIN pesanan ps ON ps.id=pg.pesanan_id WHERE pg.id=? AND k.user_id=? AND k.status='aktif' AND pg.metode_pengiriman='diantar_kurir' FOR UPDATE");$s->bind_param('ii',$id,$uid);$s->execute();$r=$s->get_result()->fetch_assoc();$s->close();
if(!$r || $r['status_pesanan']==='dibatalkan') throw new RuntimeException('Tugas tidak ditemukan atau sudah dibatalkan.');
if($aksi==='mulai'){
if(!in_array($r['STATUS'],['Belum Dikirim','Diproses'],true)) throw new RuntimeException('Pengiriman sudah dimulai/selesai.');
$s=$conn->prepare("UPDATE pengiriman SET STATUS='Dikirim',tanggal_kirim=COALESCE(tanggal_kirim,NOW()) WHERE id=?");$s->bind_param('i',$id);$s->execute();$s->close();
$s=$conn->prepare("UPDATE pesanan SET status_pengiriman='dalam_perjalanan' WHERE id=? AND STATUS NOT IN ('selesai','dibatalkan')");$s->bind_param('i',$r['pesanan_id']);$s->execute();$s->close();
}elseif($aksi==='lokasi'){
if($r['STATUS']!=='Dikirim') throw new RuntimeException('GPS hanya dapat diperbarui saat pengiriman berlangsung.');
$lat=filter_var($_POST['latitude']??null,FILTER_VALIDATE_FLOAT);$lon=filter_var($_POST['longitude']??null,FILTER_VALIDATE_FLOAT);
if($lat===false||$lon===false||$lat < -90||$lat > 90||$lon < -180||$lon > 180) throw new RuntimeException('Koordinat GPS tidak valid.');
$s=$conn->prepare('UPDATE pengiriman SET latitude_terakhir=?,longitude_terakhir=?,lokasi_diperbarui_at=NOW() WHERE id=?');$s->bind_param('ddi',$lat,$lon,$id);$s->execute();$s->close();
}else{
if($r['STATUS']!=='Dikirim') throw new RuntimeException('Pengiriman belum dimulai atau sudah selesai.');
$s=$conn->prepare("UPDATE pengiriman SET STATUS='Sampai',tanggal_sampai=NOW() WHERE id=?");$s->bind_param('i',$id);$s->execute();$s->close();
// Jangan menandai pesanan utama selesai bila pengiriman lain belum selesai.
$s=$conn->prepare("SELECT COUNT(*) AS sisa FROM pengiriman WHERE pesanan_id=? AND metode_pengiriman='diantar_kurir' AND STATUS<>'Sampai'");$s->bind_param('i',$r['pesanan_id']);$s->execute();$sisa=(int)$s->get_result()->fetch_assoc()['sisa'];$s->close();
if($sisa===0){$s=$conn->prepare("UPDATE pesanan SET status_pengiriman='diterima' WHERE id=? AND STATUS<>'dibatalkan'");$s->bind_param('i',$r['pesanan_id']);$s->execute();$s->close();}
}
$conn->commit();
if($isJson){header('Content-Type: application/json');echo json_encode(['ok'=>true,'message'=>'GPS berhasil diperbarui pada '.date('H:i:s')]);}else{header('Location: index.php?ok=1');}
}catch(Throwable $e){$conn->rollback();gagal($e->getMessage(),$isJson);}