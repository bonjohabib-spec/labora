<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/koneksi.php';

function getActiveShift($conn, $kasir) {
    $q = $conn->prepare("SELECT * FROM kas_shift WHERE kasir = ? AND status = 'open' LIMIT 1");
    $q->bind_param("s", $kasir);
    $q->execute();
    return $q->get_result()->fetch_assoc();
}

function checkShift($conn, $kasir, $redirect = true) {
    $shift = getActiveShift($conn, $kasir);
    if (!$shift && $redirect) {
        header("Location: ../dashboard/buka_kasir.php");
        exit();
    }
    return $shift;
}
?>
