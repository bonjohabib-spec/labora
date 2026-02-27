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
        FROM detail_penjualan dp
        JOIN barang_varian v ON dp.id_varian = v.id_varian
        JOIN barang b ON v.id_barang = b.id_barang
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
  <title>Detail Penjualan - LABORA</title>
  <link rel="stylesheet" href="../assets/css/detail_penjualan.css">
  <style>
    input.inline-edit { width: 80px; }
    button.inline-btn { margin-left:5px; }
    .alert.error { color:red; margin-bottom:10px; }
  </style>
</head>
<body>
<div class="container">
  <h2>Detail Transaksi #<?= $penjualan['id_penjualan'] ?></h2>

  <?php if (isset($err)): ?>
    <div class="alert error"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="info-section">
    <p>
      <strong>Tanggal:</strong> <?= $penjualan['tanggal'] ?><br>
      <strong>Pelanggan:</strong> <?= htmlspecialchars($penjualan['pelanggan'] ?: '-') ?><br>
      <strong>Kasir:</strong> <?= htmlspecialchars($penjualan['kasir']) ?><br>
      <strong>Status:</strong> <?= ucfirst($penjualan['status']) ?>
    </p>
  </div>

  <table border="1" cellpadding="5" cellspacing="0" id="tabel-penjualan">
    <thead>
      <tr>
        <th>No</th>
        <th>Barang</th>
        <th>Warna</th>
        <th>Ukuran</th>
        <th>Qty</th>
        <th>Harga</th>
        <th>Subtotal</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; $grand=0; while($r=$d->fetch_assoc()): ?>
      <tr data-id="<?= $r['id'] ?>">
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($r['nama_barang']) ?></td>
        <td><?= htmlspecialchars($r['warna']) ?></td>
        <td><?= htmlspecialchars($r['ukuran']) ?></td>
        <td class="td-qty"><?= $r['qty'] ?></td>
        <td class="td-harga">Rp <?= number_format($r['harga_jual'],0,',','.') ?></td>
        <td class="subtotal">Rp <?= number_format($r['subtotal'],0,',','.') ?></td>
        <td>
          <?php if (in_array($penjualan['status'], ['aktif','selesai'])): ?>
            <button class="edit-btn">✏️ Edit</button>
            <a href="penjualan_detail.php?id=<?= $id ?>&hapus_item=<?= $r['id'] ?>" onclick="return confirm('Hapus item ini?')">🗑 Hapus</a>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
      </tr>
      <?php $grand += $r['subtotal']; endwhile; ?>
    </tbody>
  </table>

  <div class="summary">
    <p><span>Total:</span> <strong id="grand-total">Rp <?= number_format($grand,0,',','.') ?></strong></p>
    <p><span>Laba:</span> <strong>Rp <?= number_format($penjualan['keuntungan'] ?? 0,0,',','.') ?></strong></p>
  </div>

  <div class="actions">
    <a href="penjualan.php" class="back-link">← Kembali ke Daftar</a>
    <a href="../cetak_struk.php?id=<?= $penjualan['id_penjualan'] ?>" target="_blank" class="print-link">🧾 Cetak Struk</a>
  </div>
</div>

<script>
function formatRp(num) {
    return 'Rp ' + num.toLocaleString('id-ID');
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('#tabel-penjualan tbody tr').forEach(row=>{
        let subtotalText = row.querySelector('.subtotal').textContent.replace(/[^\d]/g,'');
        total += parseInt(subtotalText) || 0;
    });
    document.getElementById('grand-total').textContent = formatRp(total);
}

document.querySelectorAll('.edit-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        let row = btn.closest('tr');
        let qtyTd = row.querySelector('.td-qty');
        let hargaTd = row.querySelector('.td-harga');
        let subtotalTd = row.querySelector('.subtotal');

        let currentQty = parseInt(qtyTd.textContent);
        let currentHarga = parseInt(hargaTd.textContent.replace(/[^\d]/g,''));

        qtyTd.innerHTML = `<input type="number" value="${currentQty}" min="1" class="qty-edit">`;
        hargaTd.innerHTML = `<input type="text" value="${currentHarga}" class="harga-edit">`;

        const qtyInput = qtyTd.querySelector('.qty-edit');
        const hargaInput = hargaTd.querySelector('.harga-edit');

        function updateSubtotal(){
            let newQty = parseInt(qtyInput.value) || 0;
            let newHarga = parseInt(hargaInput.value) || 0;
            subtotalTd.textContent = formatRp(newQty * newHarga);
            updateGrandTotal();
        }

        qtyInput.addEventListener('input', updateSubtotal);
        hargaInput.addEventListener('input', updateSubtotal);

        btn.textContent = '💾 Simpan';
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
