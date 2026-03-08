<?php
include __DIR__ . '/includes/koneksi.php';

function checkTable($conn, $table) {
    echo "<h3>Table: $table</h3>";
    $res = $conn->query("DESCRIBE `$table`");
    if (!$res) {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        return;
    }
    echo "<ul>";
    while($row = $res->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
}

checkTable($conn, 'penjualan');
checkTable($conn, 'detail_penjualan');
checkTable($conn, 'barang_varian');
checkTable($conn, 'kas_shift');

echo "<p><a href='db_update.php'>Ke Halaman Update Database</a></p>";
?>
