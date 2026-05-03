<?php
/**
 * Konfigurasi Database HIMATEP
 * Menggunakan PDO untuk keamanan dan fleksibilitas
 */

// Pengaturan Database
$db_host = 'localhost';
$db_name = 'himatep';
$db_user = 'root'; // Ganti jika Anda menggunakan user lain
$db_pass = '';     // Ganti jika ada password di MySQL Anda

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Set error mode ke exception untuk memudahkan debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode ke associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error
    die("Koneksi database gagal: " . $e->getMessage());
}

// Start session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL (Sesuaikan dengan folder proyek Anda)
define('BASE_URL', '/webHimatep/');
?>
