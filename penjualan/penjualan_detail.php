<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$kasir = $_SESSION['username'] ?? 'owner';
$active_shift = checkShift($conn, $kasir); // Wajib shift aktif untuk akses detail (buat jaga-jaga bayar piutang)

// Pastikan ID valid
if (!isset($_GET['id'])) die("ID tidak valid");
$id = intval($_GET['id']);

// Ambil data penjualan
$q = $conn->query("SELECT * FROM penjualan WHERE id_penjualan = $id");
$penjualan = $q->fetch_assoc();
if (!$penjualan) die("Transaksi tidak ditemukan.");

// ======================
// Proses Bayar Piutang (POST)
// ======================
if (isset($_POST['bayar_piutang'])) {
    $nominal = floatval($_POST['nominal_bayar']);
    if ($nominal <= 0) {
        $err = "Nominal bayar tidak valid.";
    } elseif ($nominal > $penjualan['sisa_piutang']) {
        $err = "Nominal bayar melebihi sisa hutang (Sisa: Rp " . number_format($penjualan['sisa_piutang'], 0, ',', '.') . ")";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Catat ke riwayat pembayaran_piutang
            $metode_bayar = $_POST['metode_pelunasan'] ?? 'tunai';
            $stmtPay = $conn->prepare("INSERT INTO pembayaran_piutang (id_penjualan, tanggal, nominal, metode_pembayaran, id_shift) VALUES (?, NOW(), ?, ?, ?)");
            $id_shift = isset($active_shift['id_shift']) ? $active_shift['id_shift'] : null;
            $stmtPay->bind_param("idsi", $id, $nominal, $metode_bayar, $id_shift);
            $stmtPay->execute();

            // 2. Update sisa_piutang di tabel penjualan
            $conn->query("UPDATE penjualan SET sisa_piutang = sisa_piutang - $nominal, jumlah_bayar = jumlah_bayar + $nominal WHERE id_penjualan = $id");

            // 3. Update Saldo Kas Shift (Hanya jika Tunai)
            if ($id_shift && $metode_bayar == 'tunai') {
                $conn->query("UPDATE kas_shift SET saldo_akhir_sistem = saldo_akhir_sistem + $nominal WHERE id_shift = $id_shift");
            }

            $conn->commit();
            header("Location: penjualan_detail.php?id=$id&msg=sukses_bayar");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Gagal memproses pembayaran: " . $e->getMessage();
        }
    }
}

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
                
                // Update Total & Sisa Piutang di Penjualan Utama
                $upTotal = $conn->prepare("UPDATE penjualan SET total = (SELECT SUM(subtotal) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
                $upTotal->bind_param("ii", $id, $id);
                $upTotal->execute();

                $conn->query("UPDATE penjualan SET sisa_piutang = GREATEST(0, total - jumlah_bayar) WHERE id_penjualan = $id");

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
            
            // Update Total & Sisa Piutang di Penjualan Utama
            $upTotal = $conn->prepare("UPDATE penjualan SET total = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) WHERE id_penjualan = ?");
            $upTotal->bind_param("ii", $id, $id);
            $upTotal->execute();

            $conn->query("UPDATE penjualan SET sisa_piutang = GREATEST(0, total - jumlah_bayar) WHERE id_penjualan = $id");

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
  <link rel="stylesheet" href="../assets/css/sidebar.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../assets/css/header.css?v=<?= time() ?>">
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

          <?php if ($penjualan['sisa_piutang'] > 0): ?>
          <div class="debt-payment-box" style="margin-top: 30px; background: #fff7ed; border: 1px solid #fdba74; padding: 20px; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0; color: #9a3412;">💳 Pelunasan Piutang</h4>
                <div class="badge-debt" style="background: #ea580c; color: white; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 14px;">
                    SISA HUTANG: Rp <?= number_format($penjualan['sisa_piutang'], 0, ',', '.') ?>
                </div>
            </div>
            
            <form method="POST" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #9a3412; margin-bottom: 5px;">NOMINAL BAYAR (RP)</label>
                    <input type="text" id="nominal_bayar_display" placeholder="Contoh: 50.000" style="width: 100%; padding: 10px; border: 1px solid #fdba74; border-radius: 8px; font-size: 16px; font-weight: 700;" required>
                    <input type="hidden" name="nominal_bayar" id="nominal_bayar_real">
                </div>
                <div style="width: 150px;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #9a3412; margin-bottom: 5px;">METODE</label>
                    <select name="metode_pelunasan" style="width: 100%; padding: 10px; border: 1px solid #fdba74; border-radius: 8px; font-size: 14px; font-weight: 600; background: white;">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <button type="submit" name="bayar_piutang" class="btn" style="height: 45px; padding: 0 25px; background: #ea580c; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">BAYAR SEKARANG</button>
            </form>
            <p style="font-size: 11px; color: #9a3412; margin-top: 10px; opacity: 0.8;">* Pembayaran akan otomatis masuk ke laporan kas shift hari ini.</p>
          </div>
          <?php endif; ?>

          <?php 
          $qHist = $conn->query("SELECT * FROM pembayaran_piutang WHERE id_penjualan = $id ORDER BY tanggal ASC");
          if ($qHist->num_rows > 0): 
          ?>
          <div style="margin-top: 30px;">
            <h4 style="margin-bottom: 15px; color: #475569;">📜 Riwayat Pembayaran / Cicilan</h4>
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px;">Waktu</th>
                        <th style="padding: 10px;">Metode</th>
                        <th style="padding: 10px;">Nominal</th>
                        <th style="padding: 10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = $qHist->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; color: #64748b;"><?= date('d M Y, H:i', strtotime($h['tanggal'])) ?></td>
                        <td style="padding: 10px;"><span style="text-transform: uppercase; font-size: 11px; font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;"><?= $h['metode_pembayaran'] ?></span></td>
                        <td style="padding: 10px;"><strong>Rp <?= number_format($h['nominal'], 0, ',', '.') ?></strong></td>
                        <td style="padding: 10px;"><span style="color: #10b981; font-weight: 700;">DITERIMA ✅</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
          </div>
          <?php endif; ?>

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
const nDisp = document.getElementById('nominal_bayar_display');
const nReal = document.getElementById('nominal_bayar_real');

if (nDisp) {
    nDisp.addEventListener('input', function(e) {
        let value = this.value.replace(/[^\d]/g, "");
        if (value === "") {
            this.value = "";
            nReal.value = "";
            return;
        }
        nReal.value = value;
        this.value = "Rp " + new Intl.NumberFormat('id-ID').format(value);
    });

    // Set nilai awal (dari PHP)
    let initialVal = "<?= $penjualan['sisa_piutang'] ?>";
    if (initialVal > 0) {
        nReal.value = initialVal;
        nDisp.value = "Rp " + new Intl.NumberFormat('id-ID').format(initialVal);
    }
}

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
