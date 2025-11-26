<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'el_camino_whisky');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// SOLO LA CONFIGURACIÓN DE BASE DE DATOS
// Las funciones getWhatsAppConfig() y getBankConfig() están ahora en frontend/public/index.php
?>