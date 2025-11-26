<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'mark_completed') {
            $chapter_id = $_POST['chapter_id'] ?? '';
            $season_id = $_POST['season_id'] ?? '';
            
            if (empty($chapter_id) || empty($season_id)) {
                $response['message'] = 'Datos incompletos';
                echo json_encode($response);
                exit;
            }
            
            // Verificar si ya existe progreso
            $checkStmt = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND chapter_id = ?");
            $checkStmt->execute([$user_id, $chapter_id]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Actualizar existente
                $stmt = $pdo->prepare("UPDATE user_progress SET is_completed = 1, progress_percentage = 100, completed_at = NOW() WHERE user_id = ? AND chapter_id = ?");
                $stmt->execute([$user_id, $chapter_id]);
            } else {
                // Crear nuevo
                $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, chapter_id, season_id, is_completed, progress_percentage, completed_at) VALUES (?, ?, ?, 1, 100, NOW())");
                $stmt->execute([$user_id, $chapter_id, $season_id]);
            }
            
            $response['success'] = true;
            $response['message'] = 'Progreso guardado';
            
        } else {
            $response['message'] = 'Acción no válida';
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        $season_id = $_GET['season_id'] ?? null;
        
        if ($action === 'get_progress') {
            $sql = "SELECT up.*, c.title as chapter_title, s.title as season_title 
                    FROM user_progress up 
                    JOIN chapters c ON up.chapter_id = c.id 
                    JOIN seasons s ON up.season_id = s.id 
                    WHERE up.user_id = ?";
            $params = [$user_id];
            
            if ($season_id) {
                $sql .= " AND up.season_id = ?";
                $params[] = $season_id;
            }
            
            $sql .= " ORDER BY up.completed_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $progress = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $progress;
            
        } else {
            $response['message'] = 'Acción no válida';
        }
    }
    
} catch (Exception $e) {
    error_log("Progress error: " . $e->getMessage());
    $response['message'] = 'Error del servidor';
}

echo json_encode($response);
?>