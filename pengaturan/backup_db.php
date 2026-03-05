<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include __DIR__ . '/../includes/koneksi.php';

// 1. Proteksi Role (Hanya Owner)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    die("Akses ditolak. Fitur ini hanya untuk Owner.");
}

// 2. Setup Header untuk Download File .sql
$filename = "backup_labora_" . date('Y-m-d_H-i-s') . ".sql";
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $filename);

// 3. Ambil Semua Tabel
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$output = "-- Backup Database LABORA\n";
$output .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // A. Struktur Tabel
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $output .= "\n\n" . $row[1] . ";\n\n";

    // B. Isi Data
    $result = $conn->query("SELECT * FROM $table");
    $num_fields = $result->field_count;

    for ($i = 0; $i < $result->num_rows; $i++) {
        $row = $result->fetch_row();
        $output .= "INSERT INTO $table VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            if (isset($row[$j])) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                $output .= '"' . $row[$j] . '"';
            } else {
                $output .= 'NULL';
            }
            if ($j < ($num_fields - 1)) {
                $output .= ',';
            }
        }
        $output .= ");\n";
    }
    $output .= "\n\n\n";
}

// 4. Keluarkan Output (Trigger Download)
echo $output;
exit;
