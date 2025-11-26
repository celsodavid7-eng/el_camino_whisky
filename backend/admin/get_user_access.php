<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? 0;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

try {
    // Obtener accesos del usuario
    $stmt = $pdo->prepare("
        SELECT s.title, p.created_at 
        FROM payments p 
        JOIN seasons s ON p.season_id = s.id 
        WHERE p.user_id = ? AND p.status = 'completed'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId]);
    $accesses = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'accesses' => $accesses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>