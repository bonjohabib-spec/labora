<?php
include __DIR__ . '/../includes/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = 'owner1';
  $_SESSION['user_role'] = 'owner';
}

$kasir = $_SESSION['username'];

$id_penjualan = intval($_GET['id'] ?? 0);
if (!$id_penjualan) die("ID transaksi tidak ditemukan.");

$q = $conn->query("SELECT * FROM penjualan WHERE id_penjualan=$id_penjualan");
$penjualan = $q->fetch_assoc();
if (!$penjualan) die("Data transaksi tidak ditemukan.");

$list_barang = $conn->query("SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang ASC");

$detail = $conn->query("
  SELECT d.id, b.nama_barang, v.warna, v.ukuran, d.qty, d.harga_jual, d.subtotal
  FROM detail_penjualan d
  JOIN barang_varian v ON d.id_varian = v.id_varian
  JOIN barang b ON v.id_barang = b.id_barang
  WHERE d.id_penjualan = $id_penjualan
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Transaksi - LABORA</title>
<link rel="stylesheet" href="../assets/css/penjualan_tambah.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="container">
<h2>Tambah Barang ke Transaksi #<?= $id_penjualan ?></h2>

<div class="trx-header">
  <div><strong>Tanggal:</strong> <?= htmlspecialchars($penjualan['tanggal']) ?></div>
  <div><strong>Kasir:</strong> <?= htmlspecialchars($kasir) ?></div>
  <div class="status"><?= htmlspecialchars($penjualan['status']) ?></div>
</div>

<div class="pelanggan-section" style="margin:15px 0;">
  <label for="pelanggan"><strong>Nama Pelanggan:</strong></label>
  <input type="text" id="pelanggan" name="pelanggan" value="<?= htmlspecialchars($penjualan['pelanggan'] ?? '') ?>" style="padding:5px 10px; border-radius:5px; border:1px solid #ccc; width:250px;">
</div>

<!-- form tambah item -->
<div class="tambah-item">
<form id="formTambahItem" class="form-inline" method="POST" action="penjualan_proses_tambah_item.php">
  <input type="hidden" name="id_penjualan" value="<?= $id_penjualan ?>">

  <label>Barang
    <select name="id_barang" id="id_barang" required>
      <option value="">Pilih Barang</option>
      <?php while ($b = $list_barang->fetch_assoc()): ?>
        <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
      <?php endwhile; ?>
    </select>
  </label>

  <label>Varian
    <select name="id_varian" id="id_varian" required>
      <option value="">Pilih Varian</option>
    </select>
  </label>

  <label>Qty
    <input type="number" name="qty" id="qty" min=" " value=" " required>
  </label>

  <label>Harga Satuan
    <input type="number" name="harga_jual" id="harga_jual" min="0" required>
  </label>

  <label>Subtotal
    <input type="number" name="subtotal" id="subtotal" readonly>
  </label>

  <button type="submit" class="btn btn-add">Tambah</button>
</form>
</div>

<div class="list-item">
<table class="tbl">
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
<?php $no=1; $total=0; while($d=$detail->fetch_assoc()): $total+=$d['subtotal']; ?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= htmlspecialchars($d['nama_barang']) ?></td>
  <td><?= htmlspecialchars($d['warna']) ?></td>
  <td><?= htmlspecialchars($d['ukuran']) ?></td>
  <td><?= $d['qty'] ?></td>
  <td><?= number_format($d['harga_jual'],0,',','.') ?></td>
  <td><?= number_format($d['subtotal'],0,',','.') ?></td>
  <td>
    <a href="#" class="btn-danger btn-hapus-item" data-id="<?= $d['id'] ?>">Hapus</a>

  </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<div class="total-row">
  <span>Total</span>
  <span class="total-value">Rp <?= number_format($total,0,',','.') ?></span>
</div>
</div>

<div class="actions">
<form method="POST" action="penjualan_selesai.php" onsubmit="return kirimPelanggan();">
  <input type="hidden" name="id_penjualan" value="<?= $id_penjualan ?>">
  <input type="hidden" id="hidden_pelanggan" name="pelanggan">
  <button type="submit" class="btn btn-primary">Selesaikan Transaksi</button>
  <a href="penjualan.php" class="btn btn-light">Kembali</a>
</form>
</div>
</div>

<script>
function kirimPelanggan() {
  document.getElementById('hidden_pelanggan').value = document.getElementById('pelanggan').value;
  return true;
}

$(document).ready(function() {
  $('#id_barang').select2({ width:'180px', placeholder:'Cari Barang...' });
  $('#id_varian').select2({ width:'180px', placeholder:'Pilih Varian...' });

  // Ambil varian saat barang berubah
  $('#id_barang').change(function() {
    const id_barang = $(this).val();
    $('#id_varian').html('<option value="">Memuat...</option>');
    if(id_barang){
      $.get('../get_varian.php', { id_barang }, function(data){
        $('#id_varian').html(data);
      });
    } else {
      $('#id_varian').html('<option value="">Pilih Varian</option>');
    }
  });

  // Hitung subtotal realtime
  function hitungSubtotal(){
    let qty = parseFloat($('#qty').val()) || 0;
    let harga = parseFloat($('#harga_jual').val()) || 0;
    $('#subtotal').val(qty*harga);
  }
  $('#qty, #harga_jual').on('input', hitungSubtotal);
});


// Hapus item via AJAX
$('.btn-hapus-item').click(function(e) {
  e.preventDefault();
  if(!confirm('Hapus item ini?')) return;

  let id_item = $(this).data('id');
  let row = $(this).closest('tr');

  $.post('hapus_item.php', {id: id_item}, function(res) {
    if(res.success) {
      // Hapus baris dari tabel
      row.remove();

      // Update total
      let total = 0;
      $('.tbl tbody tr').each(function() {
        let subtotal = parseFloat($(this).find('td:nth-child(7)').text().replace(/\D/g,''));
        total += subtotal;
      });
      $('.total-value').text('Rp ' + total.toLocaleString('id-ID'));
    } else {
      alert(res.message);
    }
  }, 'json');
});

</script>

</body>
</html>
