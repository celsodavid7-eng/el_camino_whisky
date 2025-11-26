<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_chapter':
                $chapterId = $_POST['chapter_id'] ?? 0;
                $userId = $_POST['user_id'] ?? 0;
                
                if (!$chapterId) {
                    $response['message'] = 'ID de capítulo requerido';
                    break;
                }
                
                // Obtener información del capítulo
                $stmt = $pdo->prepare("
                    SELECT c.*, s.title as season_title, s.requires_payment 
                    FROM chapters c 
                    LEFT JOIN seasons s ON c.season_id = s.id 
                    WHERE c.id = ? AND c.is_published = 1
                ");
                $stmt->execute([$chapterId]);
                $chapter = $stmt->fetch();
                
                if (!$chapter) {
                    $response['message'] = 'Capítulo no encontrado';
                    break;
                }
                
                // Verificar acceso
                $hasAccess = false;
                if ($chapter['is_free']) {
                    $hasAccess = true;
                } elseif ($userId) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM payments 
                        WHERE user_id = ? AND season_id = ? AND status = 'completed'
                    ");
                    $stmt->execute([$userId, $chapter['season_id']]);
                    $hasAccess = $stmt->fetch() !== false;
                }
                
                // Obtener imágenes del capítulo
                $stmt = $pdo->prepare("SELECT * FROM chapter_images WHERE chapter_id = ? ORDER BY image_order ASC");
                $stmt->execute([$chapterId]);
                $images = $stmt->fetchAll();
                
                // Obtener categorías
                $stmt = $pdo->prepare("
                    SELECT cat.* FROM categories cat
                    JOIN chapter_categories cc ON cat.id = cc.category_id
                    WHERE cc.chapter_id = ?
                ");
                $stmt->execute([$chapterId]);
                $categories = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['chapter'] = $chapter;
                $response['chapter']['images'] = $images;
                $response['chapter']['categories'] = $categories;
                $response['has_access'] = $hasAccess;
                break;
                
            case 'get_chapters_by_season':
                $seasonId = $_POST['season_id'] ?? 0;
                
                if (!$seasonId) {
                    $response['message'] = 'ID de temporada requerido';
                    break;
                }
                
                $stmt = $pdo->prepare("
                    SELECT * FROM chapters 
                    WHERE season_id = ? AND is_published = 1 
                    ORDER BY chapter_number ASC
                ");
                $stmt->execute([$seasonId]);
                $chapters = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['chapters'] = $chapters;
                break;
                
            default:
                $response['message'] = 'Acción no válida';
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>