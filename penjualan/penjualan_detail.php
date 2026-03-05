<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Pastikan ID valid
if (!isset($_GET['id'])) die("ID tidak valid");
$id = intval($_GET['id']);

// Ambil data penjualan
$q = $conn->query("SELECT * FROM penjualan WHERE id_penjualan = $id");
$penjualan = $q->fetch_assoc();
if (!$penjualan) die("Transaksi tidak ditemukan.");

// ======================
// Proses edit item (POST)
// ======================
if (isset($_POST['update_item'])) {
    $id_detail = intval($_POST['id_detail']);
    $qty_baru = intval($_POST['qty']);
    $harga_baru = floatval($_POST['harga_jual']);

    // Ambil data item sebelumnya
    $stmt = $conn->prepare("
        SELECT dp.*, v.stok AS stok_saat_ini, v.id_varian, b.nama_barang
        FROM detail_pen_detail d
        JOIN detail_penjualan dp ON d.id = dp.id
        JOIN barang_varian v ON dp.id_varian = v.id_varian
        JOIN barang b ON v.id_barang = b.id_barang
        WHERE dp.id = ?
    ");
    // Oups, query di atas sepertinya salah ketik dari sebelumnya. Saya perbaiki yang standar.
    $stmt = $conn->prepare("
        SELECT dp.*, v.stok AS stok_saat_ini, v.id_varian
        FROM detail_penjualan dp
        JOIN barang_varian v ON dp.id_varian = v.id_varian
        WHERE dp.id = ?
    ");
    $stmt->bind_param("i", $id_detail);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($item) {
        $stok_awal = (int)$item['stok_saat_ini'] + (int)$item['qty'];
        $subtotal_baru = $qty_baru * $harga_baru;

        if ($qty_baru <= 0) {
            $err = "Qty harus lebih dari 0.";
        } elseif ($qty_baru > $stok_awal) {
            $err = "Stok tidak cukup (tersedia: $stok_awal).";
        } else {
            $conn->begin_transaction();
            try {
                $selisih_qty = $qty_baru - $item['qty'];

                // Update stok varian
                $u = $conn->prepare("UPDATE barang_varian SET stok = stok - ? WHERE id_varian = ?");
                $u->bind_param("ii", $selisih_qty, $item['id_varian']);
                $u->execute();

                // Update detail
                $up = $conn->prepare("UPDATE detail_penjualan SET qty=?, harga_jual=?, subtotal=? WHERE id=?");
                $up->bind_param("iidi", $qty_baru, $harga_baru, $subtotal_baru, $id_detail);
                $up->execute();
                
                // Update Total di Penjualan Utama
                $upTotal = $conn->prepare("UPDATE penjualan SET total = (SELECT SUM(subtotal) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
                $upTotal->bind_param("ii", $id, $id);
                $upTotal->execute();

                $conn->commit();
                header("Location: penjualan_detail.php?id=$id");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $err = "Gagal update item: " . $e->getMessage();
            }
        }
    }
}

// ======================
// Hapus item (GET)
// ======================
if (isset($_GET['hapus_item'])) {
    $id_detail = intval($_GET['hapus_item']);

    $chk = $conn->prepare("SELECT id_varian, qty FROM detail_penjualan WHERE id = ? AND id_penjualan = ?");
    $chk->bind_param("ii", $id_detail, $id);
    $chk->execute();
    $res_chk = $chk->get_result()->fetch_assoc();

    if ($res_chk) {
        $conn->begin_transaction();
        try {
            $u = $conn->prepare("UPDATE barang_varian SET stok = stok + ? WHERE id_varian = ?");
            $u->bind_param("ii", $res_chk['qty'], $res_chk['id_varian']);
            $u->execute();

            $del = $conn->prepare("DELETE FROM detail_penjualan WHERE id = ?");
            $del->bind_param("i", $id_detail);
            $del->execute();
            
            // Update Total di Penjualan Utama
            $upTotal = $conn->prepare("UPDATE penjualan SET total = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
            $upTotal->bind_param("ii", $id, $id);
            $upTotal->execute();

            $conn->commit();
            header("Location: penjualan_detail.php?id=$id");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Gagal menghapus item: " . $e->getMessage();
        }
    }
}

// Ambil detail barang (JOIN varian)
$d = $conn->query("
  SELECT dp.*, b.nama_barang, v.warna, v.ukuran
  FROM detail_penjualan dp
  JOIN barang_varian v ON dp.id_varian = v.id_varian
  JOIN barang b ON v.id_barang = b.id_barang
  WHERE dp.id_penjualan = $id
  ORDER BY dp.id ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Transaksi #<?= $id ?> | LABORA</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/detail_penjualan_v2.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
</head>
<body>
<div class="container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">

      <div class="card">
        <div class="card-header">
          <h3>Detail Transaksi #INV-<?= $penjualan['id_penjualan'] ?></h3>
          <span class="status-badge status-<?= $penjualan['status'] ?>"><?= ucfirst($penjualan['status']) ?></span>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <label>Tanggal Transaksi</label>
              <span><?= date('d M Y, H:i', strtotime($penjualan['tanggal'])) ?></span>
            </div>
            <div class="info-item">
              <label>Nama Pelanggan</label>
              <span style="color: #3b82f6;"><?= htmlspecialchars($penjualan['pelanggan'] ?: '-') ?></span>
            </div>
            <div class="info-item">
              <label>Kasir</label>
              <span><?= htmlspecialchars($penjualan['kasir']) ?></span>
            </div>
            <div class="info-item">
              <label>No. Invoice</label>
              <span>#<?= $id ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Daftar Belanja</h3>
        </div>
        <div class="card-body">
          <?php if (isset($err)): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
              ⚠️ <?= htmlspecialchars($err) ?>
            </div>
          <?php endif; ?>

          <div class="table-responsive">
            <table id="tabel-penjualan">
            <thead>
              <tr>
                <th>No</th>
                <th>Produk</th>
                <th>Varian</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; $grand=0; while($r=$d->fetch_assoc()): ?>
              <tr data-id="<?= $r['id'] ?>">
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($r['nama_barang']) ?></strong></td>
                <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 11px;"><?= htmlspecialchars($r['warna']) ?> / <?= htmlspecialchars($r['ukuran']) ?></span></td>
                <td class="td-qty"><?= $r['qty'] ?></td>
                <td class="td-harga">Rp <?= number_format($r['harga_jual'],0,',','.') ?></td>
                <td class="subtotal"><strong>Rp <?= number_format($r['subtotal'],0,',','.') ?></strong></td>
                <td style="text-align:right;" class="action-links">
                  <?php if (in_array($penjualan['status'], ['aktif','selesai'])): ?>
                    <button class="btn btn-secondary btn-xs edit-btn" style="padding: 4px 8px; font-size: 11px;">✏️ Edit</button>
                    <a href="penjualan_detail.php?id=<?= $id ?>&hapus_item=<?= $r['id'] ?>" onclick="return confirm('Hapus item ini?')">🗑 Hapus</a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
              <?php $grand += $r['subtotal']; endwhile; ?>
            </tbody>
            </table>
          </div>

          <div class="summary-box">
            <div class="summary-item">Total Pembayaran: <strong id="grand-total">Rp <?= number_format($grand,0,',','.') ?></strong></div>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'owner'): ?>
              <div class="summary-item">Estimasi Laba: <strong style="color: #16a34a;">Rp <?= number_format($penjualan['keuntungan'] ?? 0,0,',','.') ?></strong></div>
            <?php endif; ?>
          </div>

          <div style="margin-top: 25px; display: flex; justify-content: space-between;">
            <a href="penjualan.php" class="btn btn-secondary">← Kembali ke Daftar</a>
            <a href="penjualan_finish.php?id=<?= $penjualan['id_penjualan'] ?>" class="btn btn-print">🧾 Cetak Struk Belanja</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function formatRp(num) {
    return 'Rp ' + num.toLocaleString('id-ID');
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('#tabel-penjualan tbody tr .subtotal strong').forEach(el=>{
        let subtotalText = el.textContent.replace(/[^\d]/g,'');
        total += parseInt(subtotalText) || 0;
    });
    document.getElementById('grand-total').textContent = formatRp(total);
}

document.querySelectorAll('.edit-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        let row = btn.closest('tr');
        let qtyTd = row.querySelector('.td-qty');
        let hargaTd = row.querySelector('.td-harga');
        let subtotalTd = row.querySelector('.subtotal strong');

        let currentQty = parseInt(qtyTd.textContent);
        let currentHarga = parseInt(hargaTd.textContent.replace(/[^\d]/g,''));

        qtyTd.innerHTML = `<input type="number" value="${currentQty}" min="1" class="qty-edit">`;
        hargaTd.innerHTML = `<input type="text" value="${currentHarga}" class="harga-edit">`;

        const qtyInput = qtyTd.querySelector('.qty-edit');
        const hargaInput = hargaTd.querySelector('.harga-edit');

        function updateSub(){
            let newQty = parseInt(qtyInput.value) || 0;
            let newHarga = parseInt(hargaInput.value) || 0;
            subtotalTd.textContent = formatRp(newQty * newHarga);
            updateGrandTotal();
        }

        qtyInput.addEventListener('input', updateSub);
        hargaInput.addEventListener('input', updateSub);

        btn.textContent = '💾 Simpan';
        btn.classList.add('btn-print'); // Ganti warna jadi biru terang
        
        btn.addEventListener('click', ()=>{
            let newQty = parseInt(qtyInput.value) || 0;
            let newHarga = parseInt(hargaInput.value) || 0;

            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="update_item" value="1">
                <input type="hidden" name="id_detail" value="${row.dataset.id}">
                <input type="hidden" name="qty" value="${newQty}">
                <input type="hidden" name="harga_jual" value="${newHarga}">
            `;
            document.body.appendChild(form);
            form.submit();
        }, {once:true});
    });
});
</script>
</body>
</html>
