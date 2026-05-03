<?php
session_start();
require_once '../../private/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi input dasar
    $nama = htmlspecialchars(trim($_POST['nama'])) ?: 'Anonim';
    $email = htmlspecialchars(trim($_POST['email']));
    $jenis = htmlspecialchars(trim($_POST['jenis']));
    $pesan = htmlspecialchars(trim($_POST['pesan']));

    // Validasi: Jika email diisi, harus menggunakan @gmail.com
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
            header("Location: ../index.php?status=error&msg=Hanya+menerima+email+Gmail+(@gmail.com)#aspirasi");
            exit;
        }
    } else {
        $email = '-'; // Default jika kosong
    }

    if ($pdo) {
        try {
            // FITUR OTOMATIS: Cek apakah kolom 'email' sudah ada, jika belum tambahkan otomatis
            try {
                $pdo->query("SELECT email FROM aspirasi LIMIT 1");
            } catch (PDOException $e) {
                $pdo->exec("ALTER TABLE aspirasi ADD COLUMN email VARCHAR(100) DEFAULT '-' AFTER nama");
            }

            $sql = "INSERT INTO aspirasi (nama, email, jenis, pesan, status) VALUES (?, ?, ?, ?, 'Baru')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $email, $jenis, $pesan]);
            
            // Redirect sukses
            header("Location: ../index.php?status=success#aspirasi");
            exit;
        } catch(PDOException $e) {
            // Jika masih error, kirim pesan error detail ke URL (untuk debugging)
            $error_msg = urlencode($e->getMessage());
            header("Location: ../index.php?status=error&msg=$error_msg#aspirasi");
            exit;
        }
    } else {
        // SIMULASI BERHASIL jika tidak ada database (untuk demo)
        header("Location: ../index.php?status=success#aspirasi");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
