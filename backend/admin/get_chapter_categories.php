<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if (!isset($_GET['chapter_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$chapterId = intval($_GET['chapter_id']);

try {
    $stmt = $pdo->prepare("SELECT category_id FROM chapter_categories WHERE chapter_id = ?");
    $stmt->execute([$chapterId]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode($categories);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([]);
}
?>