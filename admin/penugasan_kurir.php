<?php
require_once '../config/database.php';require_once '../config/auth.php';wajibRole('admin');
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
if(empty($_SESSION['csrf_admin_kurir'])) $_SESSION['csrf_admin_kurir']=bin2hex(random_bytes(32));
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$pesan='';
if($_SERVER['REQUEST_METHOD']==='POST'){
try{
if(!hash_equals($_SESSION['csrf_admin_kurir'],(string)($_POST['csrf']??''))) throw new RuntimeException('Token keamanan tidak cocok.');
$pengirimanId=filter_var($_POST['pengiriman_id']??null,FILTER_VALIDATE_INT);$kurirId=filter_var($_POST['kurir_id']??null,FILTER_VALIDATE_INT);
if(!$pengirimanId||!$kurirId) throw new RuntimeException('Pilih pengiriman dan kurir.');
$conn->begin_transaction();
$s=$conn->prepare("SELECT pg.id FROM pengiriman pg JOIN pesanan p ON p.id=pg.pesanan_id WHERE pg.id=? AND pg.metode_pengiriman='diantar_kurir' AND pg.STATUS IN ('Belum Dikirim','Diproses') AND p.STATUS<>'dibatalkan' FOR UPDATE");$s->bind_param('i',$pengirimanId);$s->execute();$valid=$s->get_result()->fetch_assoc();$s->close();if(!$valid) throw new RuntimeException('Pengiriman tidak tersedia untuk ditugaskan.');
$s=$conn->prepare("SELECT id FROM kurir WHERE id=? AND status='aktif' LIMIT 1");$s->bind_param('i',$kurirId);$s->execute();$ada=$s->get_result()->fetch_assoc();$s->close();if(!$ada) throw new RuntimeException('Kurir tidak aktif.');
$s=$conn->prepare("UPDATE pengiriman SET kurir_id=?,STATUS='Diproses' WHERE id=?");$s->bind_param('ii',$kurirId,$pengirimanId);$s->execute();$s->close();$conn->commit();$pesan='Kurir berhasil ditugaskan.';
}catch(Throwable $e){try{$conn->rollback();}catch(Throwable $ignore){}$pesan=$e->getMessage();}
}
$kurir=$conn->query("SELECT id,nama_kurir,no_hp FROM kurir WHERE status='aktif' ORDER BY nama_kurir")->fetch_all(MYSQLI_ASSOC);
$daftar=$conn->query("SELECT pg.id,pg.STATUS,pg.kurir_id,p.kode_pesanan,p.alamat_pengiriman,pp.kode_sub_pesanan,pen.nama_usaha,k.nama_kurir FROM pengiriman pg JOIN pesanan p ON p.id=pg.pesanan_id LEFT JOIN pesanan_penjual pp ON pp.id=pg.pesanan_penjual_id LEFT JOIN penjual pen ON pen.id=pp.penjual_id LEFT JOIN kurir k ON k.id=pg.kurir_id WHERE pg.metode_pengiriman='diantar_kurir' AND pg.STATUS IN ('Belum Dikirim','Diproses') AND p.STATUS<>'dibatalkan' ORDER BY pg.id DESC")->fetch_all(MYSQLI_ASSOC);
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Penugasan Kurir - SIPESTA</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-4"><h3>Penugasan Kurir</h3><p class="text-muted">Hanya pengiriman yang belum dimulai yang dapat ditugaskan atau dialihkan.</p><?php if($pesan):?><div class="alert alert-info"><?=h($pesan)?></div><?php endif;?><?php if(!$daftar):?><div class="alert alert-secondary">Tidak ada pengiriman yang menunggu penugasan.</div><?php endif;?><div class="row g-3"><?php foreach($daftar as $d):?><div class="col-md-6"><div class="card shadow-sm"><div class="card-body"><h5><?=h($d['kode_sub_pesanan'] ?: $d['kode_pesanan'])?></h5><p class="mb-1">Toko: <?=h($d['nama_usaha'] ?: '-')?></p><p>Tujuan: <?=h($d['alamat_pengiriman'])?></p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_admin_kurir'])?>"><input type="hidden" name="pengiriman_id" value="<?=(int)$d['id']?>"><label class="form-label">Pilih Kurir</label><select name="kurir_id" class="form-select mb-2" required><option value="">-- Pilih kurir --</option><?php foreach($kurir as $k):?><option value="<?=(int)$k['id']?>" <?=((int)$d['kurir_id']===(int)$k['id']?'selected':'')?>><?=h($k['nama_kurir'])?> (<?=h($k['no_hp'])?>)</option><?php endforeach;?></select><button class="btn btn-success" <?=(!$kurir?'disabled':'')?>>Simpan Penugasan</button></form></div></div></div><?php endforeach;?></div></main></body></html>
