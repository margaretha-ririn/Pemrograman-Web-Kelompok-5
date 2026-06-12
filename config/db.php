<?php
// config/db.php

$host     = 'localhost';
$db_name  = 'skillnext_db';
$username = 'root';       // ganti sesuai XAMPP kamu
$password = '';           // biasanya kosong di XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>