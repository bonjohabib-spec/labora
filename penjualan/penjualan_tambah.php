<?php
include __DIR__ . '/../includes/koneksi.php';
include __DIR__ . '/../includes/auth_shift.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = 'owner1';
  $_SESSION['user_role'] = 'owner';
}

$kasir = $_SESSION['username'];
$active_shift = checkShift($conn, $kasir); // Wajib buka kasir

$id_penjualan = intval($_GET['id'] ?? 0);
if (!$id_penjualan) die("ID transaksi tidak ditemukan.");

$q = $conn->query("SELECT * FROM penjualan WHERE id_penjualan=$id_penjualan");
$penjualan = $q->fetch_assoc();
if (!$penjualan) die("Data transaksi tidak ditemukan.");

// Hanya ambil barang yang aktif
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kasir - #INV-<?= $id_penjualan ?> | LABORA</title>
<link rel="stylesheet" href="../assets/css/global.css">
<!-- Tidak menggunakan sidebar.css karena sidebar dihapus -->
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/penjualan_tambah.css?v=<?= time() ?>">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="pos-screen">
  <!-- Header tetap ada tapi full width -->
  <header class="pos-header">
    <div class="header-left">
      <a href="penjualan.php" class="btn-back">← Kembali ke Dashboard</a>
      <h1>Transaksi #INV-<?= $id_penjualan ?></h1>
    </div>
    <div class="header-right">
      <div class="kasir-info">
        <span class="kasir-name">Kasir: <strong><?= htmlspecialchars($kasir) ?></strong></span>
        <span class="badge-status drafted"><?= ucfirst($penjualan['status']) ?></span>
      </div>
    </div>
  </header>

  <main class="pos-container">
    <!-- Kolom Kiri: Form Input -->
    <aside class="pos-sidebar-form">
      <div class="card">
        <div class="card-header">
          <h3>📦 Berkas Barang</h3>
        </div>
        <div class="card-body">
          <form id="formTambahItem" method="POST" action="penjualan_proses_tambah_item.php">
            <input type="hidden" name="id_penjualan" value="<?= $id_penjualan ?>">
            
            <div class="form-group">
              <label>Pelanggan</label>
              <input type="text" id="pelanggan" name="pelanggan" value="<?= htmlspecialchars($penjualan['pelanggan'] ?: '-') ?>" placeholder="Nama Pelanggan..." onfocus="if(this.value==='-') this.value='';" onblur="if(this.value==='') this.value='-';">
            </div>

            <div class="form-group">
              <label>Pilih Produk</label>
              <select name="id_barang" id="id_barang" class="select2" required>
                <option value="">Cari Produk...</option>
                <?php while ($b = $list_barang->fetch_assoc()): ?>
                  <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Varian & Stok</label>
              <select name="id_varian" id="id_varian" class="select2" required>
                <option value="">Pilih varian...</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jumlah</label>
                <input type="number" name="qty" id="qty" min="1" value="1" required onfocus="this.select()">
              </div>
              <div class="form-group">
                <label>Stok Tersedia</label>
                <div class="stok-indicator" id="stokTersedia">0</div>
              </div>
            </div>

            <div class="form-group">
              <label>Harga Satuan (Rp)</label>
              <input type="number" name="harga_jual" id="harga_jual" min="0" required onfocus="this.select()">
              <div class="rupiah-live" id="rupiahFormat">Rp 0</div>
            </div>

            <div class="form-group subtotal-group">
              <label>Subtotal</label>
              <div class="subtotal-display" id="subtotal_display">Rp 0</div>
              <input type="hidden" name="subtotal" id="subtotal">
            </div>

            <button type="submit" class="btn-add-item">Tambah ke Keranjang</button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Kolom Kanan: Daftar Keranjang -->
    <section class="pos-main-table">
      <div class="card table-card">
        <div class="card-header">
          <h3>🛒 Keranjang Belanja</h3>
        </div>
        <div class="table-scroll">
          <table class="pos-table">
            <thead>
              <tr>
                <th>Produk</th>
                <th>Varian</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Total</th>
                <th style="text-align:center;">Hapus</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; $total=0; while($d=$detail->fetch_assoc()): $total+=$d['subtotal']; ?>
              <tr class="item-row">
                <td><strong><?= htmlspecialchars($d['nama_barang']) ?></strong></td>
                <td><span class="v-tag"><?= htmlspecialchars($d['warna']) ?> / <?= htmlspecialchars($d['ukuran']) ?></span></td>
                <td><?= $d['qty'] ?></td>
                <td>Rp <?= number_format($d['harga_jual'], 0, ',', '.') ?></td>
                <td><strong>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></strong></td>
                <td style="text-align:center;">
                  <button type="button" class="btn-delete-item" data-id="<?= $d['id'] ?>">✕</button>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($no == 1 && $total == 0): ?>
                <tr>
                  <td colspan="6" class="empty-state">
                    <img src="../assets/img/empty-cart.png" alt="" style="width: 80px; opacity: 0.3; margin-bottom: 10px; display:block; margin: 0 auto;">
                    Keranjang masih kosong
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="pos-footer">
          <div class="payment-section">
            <div class="payment-row">
              <label>Metode Pembayaran</label>
              <select id="metode_pembayaran" class="input-bayar" style="font-weight: 600;">
                <option value="tunai">Tunai / Lunas</option>
                <option value="piutang">Piutang / Bon / Panjar</option>
              </select>
            </div>
            <div class="payment-row" id="row_bayar">
              <label id="label_bayar">Nominal Bayar (Rp)</label>
              <input type="number" id="bayar_display" class="input-bayar" placeholder="0" onfocus="this.select()">
            </div>
            <div class="payment-row" id="row_kembali">
              <label id="label_kembali">Kembalian</label>
              <div class="kembali-display" id="kembali_text">Rp 0</div>
            </div>
          </div>
          
          <div class="total-section">
            <span class="total-label">TOTAL AKHIR:</span>
            <span class="total-amount">Rp <span class="total-value"><?= number_format($total, 0, ',', '.') ?></span></span>
          </div>
          
          <form method="POST" action="penjualan_selesai.php" id="formCheckout" onsubmit="return confirmSelesai();">
            <input type="hidden" name="id_penjualan" value="<?= $id_penjualan ?>">
            <input type="hidden" id="hidden_pelanggan" name="pelanggan">
            <input type="hidden" id="hidden_metode" name="metode_pembayaran" value="tunai">
            <input type="hidden" id="hidden_bayar" name="bayar" value="0">
            <input type="hidden" id="hidden_kembali" name="kembali" value="0">
            <button type="submit" class="btn-checkout">SELESAIKAN TRANSAKSI (F10)</button>
          </form>
        </div>
      </div>
    </section>
  </main>

<script>
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(angka);
}

function confirmSelesai() {
  const total = parseFloat($('.total-value').text().replace(/\D/g,'')) || 0;
  if(total <= 0) {
    alert('Keranjang belanja kosong!');
    return false;
  }
  
  const metode = $('#metode_pembayaran').val();
  const bayar = parseFloat($('#bayar_display').val()) || 0;
  
  if (metode === 'tunai' && bayar < total) {
      return confirm('Uang bayar kurang dari total. Lanjutkan sebagai transaksi Tunai?');
  }

  document.getElementById('hidden_pelanggan').value = document.getElementById('pelanggan').value;
  document.getElementById('hidden_metode').value = metode;
  document.getElementById('hidden_bayar').value = bayar;
  
  return confirm('Apakah transaksi ini sudah benar? Stok akan segera berkurang.');
}

$(document).ready(function() {
  $('.select2').select2({ width: '100%' });

  $('#id_barang').change(function() {
    const id_barang = $(this).val();
    $('#id_varian').html('<option value="">Memuat...</option>');
    if(id_barang){
      $.get('../get/get_varian.php', { id_barang }, function(data){
        $('#id_varian').html(data);
      });
    } else {
      $('#id_varian').html('<option value="">Pilih varian...</option>');
    }
  });

  $('#id_varian').change(function() {
    const selected = $(this).find(':selected');
    const harga = selected.data('harga') || 0;
    const stok = selected.data('stok') || 0;
    
    $('#harga_jual').val(harga);
    $('#stokTersedia').text(stok);
    $('#qty').attr('max', stok);
    
    $('#rupiahFormat').text(formatRupiah(harga));
    hitungSubtotal();
  });

  function hitungSubtotal(){
    let qty = parseFloat($('#qty').val()) || 0;
    let harga = parseFloat($('#harga_jual').val()) || 0;
    let stok = parseFloat($('#stokTersedia').text()) || 0;

    if (qty > stok) {
      alert('Stok tidak mencukupi!');
      $('#qty').val(stok);
      qty = stok;
    }

    const subtotal = qty * harga;
    $('#subtotal').val(subtotal);
    $('#subtotal_display').text(formatRupiah(subtotal));
    $('#rupiahFormat').text(formatRupiah(harga));
  }

  $('#qty, #harga_jual').on('input', hitungSubtotal);

  // Hapus item via AJAX
  $('.btn-delete-item').click(function(e) {
    e.preventDefault();
    let id_item = $(this).data('id');
    let row = $(this).closest('tr');

    $.post('hapus_item.php', {id: id_item}, function(res) {
      if(res.success) {
        row.fadeOut(300, function() { 
          $(this).remove(); 
          updateTotal();
          if($('.item-row').length === 0) location.reload();
        });
      } else {
        alert(res.message);
      }
    }, 'json');
  });

  function updateTotal() {
    let total = 0;
    $('.item-row').each(function() {
      let subText = $(this).find('td:nth-child(5) strong').text();
      let subtotal = parseFloat(subText.replace(/\D/g,'')) || 0;
      total += subtotal;
    });
    $('.total-value').text(total.toLocaleString('id-ID'));
    hitungKembalian(); // Update kembalian saat total berubah
  }

  // Logika Pembayaran
  function hitungKembalian() {
    const total = parseFloat($('.total-value').text().replace(/\D/g,'')) || 0;
    const bayar = parseFloat($('#bayar_display').val()) || 0;
    const kembali = bayar - total;
    
    $('#kembali_text').text(formatRupiah(Math.max(0, kembali)));
    if (kembali < 0) {
      $('#kembali_text').css('color', '#ef4444');
    } else {
      $('#kembali_text').css('color', '#10b981');
    }
    
    // Set hidden inputs
    $('#hidden_bayar').val(bayar);
    $('#hidden_kembali').val(kembali);
  }

  $('#bayar_display').on('input', hitungKembalian);

  // Shortcut F10 untuk checkout
  $(document).keydown(function(e) {
    if (e.which === 121) { // F10
      e.preventDefault();
      $('#formCheckout').submit();
    }
  });
});
</script>
</body>
</html>
