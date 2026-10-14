<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/helper.php';
wajibRole('pembeli');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: checkout.php'); exit; }

function jarakCheckout(float $a, float $b, float $c, float $d): float {
    $r = 6371.0; $dl = deg2rad($c-$a); $dn = deg2rad($d-$b);
    $h = sin($dl/2)**2 + cos(deg2rad($a))*cos(deg2rad($c))*sin($dn/2)**2;
    $h = max(0.0,min(1.0,$h));
    return 2*$r*atan2(sqrt($h),sqrt(1-$h));
}
function koordinatCheckout($a, $b): bool {
    return $a !== null && $b !== null && $a !== '' && $b !== '' && is_numeric($a) && is_numeric($b)
        && (float)$a >= -90 && (float)$a <= 90 && (float)$b >= -180 && (float)$b <= 180;
}
function notifikasiCheckout(mysqli $conn, int $user, string $judul, string $pesan, string $link): void {
    $s = $conn->prepare("INSERT INTO notifications (user_id, judul, pesan, tipe, link) VALUES (?, ?, ?, 'pesanan', ?)");
    $s->bind_param('isss', $user, $judul, $pesan, $link); $s->execute(); $s->close();
}
$transaksi = false;
try {
    $metode = (string)($_POST['metode_pengiriman'] ?? '');
    if (!in_array($metode, ['diantar_penjual','diantar_kurir','ambil_sendiri'], true)) throw new RuntimeException('Metode pengiriman tidak valid.');
    $ambil = $metode === 'ambil_sendiri';
    $bayar = strtoupper(trim((string)($_POST['metode_pembayaran'] ?? '')));
    if (!in_array($bayar, ['COD','TRANSFER'], true)) throw new RuntimeException('Metode pembayaran tidak valid.');
    // ENUM database adalah COD/Transfer (bukan TRANSFER).
    $bayarDb = $bayar === 'TRANSFER' ? 'Transfer' : 'COD';
    $alamat = trim((string)($_POST['alamat_pengiriman'] ?? ''));
    $catatan = trim((string)($_POST['catatan'] ?? ''));
    if (!$ambil && $alamat === '') throw new RuntimeException('Alamat pengiriman wajib diisi.');
    if (mb_strlen($alamat) > 2000 || mb_strlen($catatan) > 2000) throw new RuntimeException('Alamat atau catatan terlalu panjang.');
    $latRaw = $_POST['latitude_pengiriman'] ?? null;
    $lonRaw = $_POST['longitude_pengiriman'] ?? null;
    if (!$ambil && !koordinatCheckout($latRaw,$lonRaw)) throw new RuntimeException('Pilih lokasi tujuan pada peta terlebih dahulu.');
    $lat = !$ambil ? (float)$latRaw : null;
    $lon = !$ambil ? (float)$lonRaw : null;

    $userId = (int)userId();
    $s = $conn->prepare('SELECT id, nama, no_hp FROM pembeli WHERE user_id=? LIMIT 1');
    $s->bind_param('i',$userId); $s->execute(); $pembeli=$s->get_result()->fetch_assoc(); $s->close();
    if (!$pembeli) throw new RuntimeException('Profil pembeli tidak ditemukan.');
    $pembeliId=(int)$pembeli['id']; $nama=trim((string)$pembeli['nama']); $hp=trim((string)$pembeli['no_hp']);
    if ($nama === '' || $hp === '') throw new RuntimeException('Lengkapi nama dan nomor HP di profil.');

    $conn->begin_transaction(); $transaksi=true;
    $s=$conn->prepare('SELECT id FROM keranjang WHERE pembeli_id=? LIMIT 1 FOR UPDATE');
    $s->bind_param('i',$pembeliId); $s->execute(); $cart=$s->get_result()->fetch_assoc(); $s->close();
    if (!$cart) throw new RuntimeException('Keranjang tidak ditemukan.');
    $cartId=(int)$cart['id'];
    $s=$conn->prepare("SELECT kd.produk_id,kd.jumlah,p.nama_produk,p.harga,p.stok,p.status AS status_produk,
          p.penjual_id,pen.user_id AS penjual_user_id,pen.nama_usaha,pen.alamat AS alamat_toko,
          pen.latitude AS lat_penjual,pen.longitude AS lon_penjual
          FROM keranjang_detail kd JOIN produk p ON p.id=kd.produk_id
          JOIN penjual pen ON pen.id=p.penjual_id WHERE kd.keranjang_id=? FOR UPDATE");
    $s->bind_param('i',$cartId); $s->execute(); $result=$s->get_result();
    $items=[]; $seller=[]; $subtotal=0.0; $maksJarak=0.0;
    while ($row=$result->fetch_assoc()) {
        $jumlah=(float)$row['jumlah']; $harga=(float)$row['harga']; $stok=(float)$row['stok'];
        if ($row['status_produk'] !== 'tersedia' || !is_finite($jumlah) || $jumlah<=0 || $stok<$jumlah || !is_finite($harga) || $harga<0)
            throw new RuntimeException('Produk '. $row['nama_produk'] .' tidak tersedia atau stok tidak mencukupi.');
        $sid=(int)$row['penjual_id'];
        if (!koordinatCheckout($row['lat_penjual'],$row['lon_penjual'])) throw new RuntimeException('Lokasi toko '.($row['nama_usaha'] ?: $sid).' belum diatur.');
        $jarak=$ambil ? 0.0 : jarakCheckout((float)$lat,(float)$lon,(float)$row['lat_penjual'],(float)$row['lon_penjual']);
        $maksJarak=max($maksJarak,$jarak);
        if (!isset($seller[$sid])) $seller[$sid]=['user_id'=>(int)$row['penjual_user_id'],'subtotal'=>0.0,'jarak'=>$jarak,'alamat'=>(string)($row['alamat_toko'] ?? ''),'nama'=>(string)($row['nama_usaha'] ?? '')];
        $line=round($harga*$jumlah,2); $subtotal+=$line; $seller[$sid]['subtotal']+=$line;
        $items[]=['produk_id'=>(int)$row['produk_id'],'jumlah'=>$jumlah,'harga'=>$harga,'subtotal'=>$line,'nama'=>$row['nama_produk']];
    }
    $s->close();
    if (!$items || $subtotal<=0) throw new RuntimeException('Keranjang kosong atau total belanja tidak valid.');
    if ($ambil) {
        $alamat='Ambil sendiri di lokasi penjual (lihat detail toko).';
        $ongkir=0.0; $jarakKm=0.0; $statusPengiriman='menunggu';
    } else {
        $res=$conn->query("SELECT tarif_dasar,tarif_per_km,minimal_gratis,maksimal_jarak_km FROM pengaturan_ongkir WHERE status='aktif' ORDER BY id DESC LIMIT 1");
        $tarif=$res->fetch_assoc();
        if (!$tarif) throw new RuntimeException('Pengaturan ongkir belum aktif.');
        foreach (['tarif_dasar','tarif_per_km','minimal_gratis','maksimal_jarak_km'] as $k) if ((float)$tarif[$k]<0) throw new RuntimeException('Pengaturan ongkir tidak valid.');
        if ((float)$tarif['maksimal_jarak_km']>0 && $maksJarak>(float)$tarif['maksimal_jarak_km']) throw new RuntimeException('Lokasi berada di luar jangkauan pengiriman.');
        $ongkir=(float)$tarif['minimal_gratis']>0 && $subtotal>=(float)$tarif['minimal_gratis'] ? 0.0
          : round((float)$tarif['tarif_dasar'] + (ceil($maksJarak*100)/100)*(float)$tarif['tarif_per_km'],2);
        $jarakKm=round($maksJarak,2);
        $statusPengiriman=$metode==='diantar_kurir' ? 'menunggu_kurir' : 'menunggu';
    }
    $total=round($subtotal+$ongkir,2);
    $kode='PSN-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(3)));
    $status='menunggu';
    $s=$conn->prepare('INSERT INTO pesanan (kode_pesanan,pembeli_id,nama_penerima,no_hp,tanggal_pesanan,total_harga,alamat_pengiriman,catatan,metode_pembayaran,status,latitude_pengiriman,longitude_pengiriman,jarak_km,ongkos_kirim,metode_pengiriman,status_pengiriman) VALUES (?,?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?)');
    $s->bind_param('sissdssssddddss',$kode,$pembeliId,$nama,$hp,$total,$alamat,$catatan,$bayarDb,$status,$lat,$lon,$jarakKm,$ongkir,$metode,$statusPengiriman);
    $s->execute(); $orderId=(int)$conn->insert_id; $s->close();

    $detail=$conn->prepare('INSERT INTO detail_pesanan (pesanan_id,produk_id,jumlah,harga,subtotal) VALUES (?,?,?,?,?)');
    $stock=$conn->prepare("UPDATE produk SET stok=stok-?,status=CASE WHEN stok-?<=0 THEN 'habis' ELSE 'tersedia' END,updated_at=NOW() WHERE id=? AND stok>=? AND status='tersedia'");
    foreach ($items as $item) {
        $pid=$item['produk_id']; $qty=$item['jumlah']; $harga=$item['harga']; $line=$item['subtotal'];
        $detail->bind_param('iiddd',$orderId,$pid,$qty,$harga,$line); $detail->execute();
        $stock->bind_param('ddid',$qty,$qty,$pid,$qty); $stock->execute();
        if ($stock->affected_rows!==1) throw new RuntimeException('Stok produk '.$item['nama'].' berubah. Ulangi checkout.');
    }
    $detail->close(); $stock->close();
    // Satu subpesanan dan satu pengiriman untuk setiap toko.
    $sub=$conn->prepare('INSERT INTO pesanan_penjual (pesanan_id,penjual_id,kode_sub_pesanan,total_harga,status) VALUES (?,?,?,?,?)');
    $kirim=$conn->prepare('INSERT INTO pengiriman (pesanan_id,pesanan_penjual_id,metode_pengiriman,jarak_km,ongkos_kirim,alamat,status) VALUES (?,?,?,?,?,?,?)');
    $idx=0; $jumlahToko=count($seller); $ongkirTerbagi=0.0;
    foreach ($seller as $sid=>$toko) {
        $idx++; $subKode=$kode.'-P'.str_pad((string)$idx,2,'0',STR_PAD_LEFT); $subTotal=$toko['subtotal'];
        $sub->bind_param('iisds',$orderId,$sid,$subKode,$subTotal,$status); $sub->execute();
        $subId=(int)$conn->insert_id;
        // Ongkir total dicatat sekali pada pesanan; dibagi untuk informasi pengiriman per toko.
        $ongkirToko=$idx===$jumlahToko ? round($ongkir-$ongkirTerbagi,2) : round($ongkir/$jumlahToko,2);
        $ongkirTerbagi=($ongkirTerbagi??0)+$ongkirToko;
        $jarakToko=round($toko['jarak'],2);
        $alamatKirim=$ambil ? ($toko['alamat']!=='' ? $toko['alamat'] : 'Lokasi toko: '.$toko['nama']) : $alamat;
        $statusLama='Belum Dikirim';
        $kirim->bind_param('iisddss', $orderId,$subId,$metode,$jarakToko,$ongkirToko,$alamatKirim,$statusLama);
        $kirim->execute();
        $hubung=$conn->prepare('UPDATE detail_pesanan dp INNER JOIN produk p ON p.id=dp.produk_id SET dp.pesanan_penjual_id=? WHERE dp.pesanan_id=? AND p.penjual_id=?');
        $hubung->bind_param('iii',$subId,$orderId,$sid); $hubung->execute(); $hubung->close();
        notifikasiCheckout($conn,$toko['user_id'],'Pesanan Baru','Pesanan '.$subKode.' masuk. Metode: '.str_replace('_',' ',$metode).'.','../penjual/pesanan.php');
    }
    $sub->close(); $kirim->close();
    $s=$conn->prepare('DELETE FROM keranjang_detail WHERE keranjang_id=?'); $s->bind_param('i',$cartId); $s->execute(); $s->close();
    notifikasiCheckout($conn,$userId,'Pesanan Berhasil','Pesanan '.$kode.' berhasil dibuat. Total Rp'.number_format($total,0,',','.').'.','detail_pesanan.php?id='.$orderId);
    $conn->commit(); $transaksi=false;
    header('Location: pesanan_sukses.php?id='.$orderId); exit;
} catch (Throwable $e) {
    if ($transaksi) $conn->rollback();
    http_response_code(400);
    $pesan=htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Checkout Gagal</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"><main class="container py-5"><div class="card p-4 mx-auto" style="max-width:650px"><h2 class="text-danger">Checkout gagal</h2><p>'.$pesan.'</p><a class="btn btn-success" href="checkout.php">Kembali ke Checkout</a></div></main></html>';
}