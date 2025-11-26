<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_seasons':
                $stmt = $pdo->prepare("
                    SELECT * FROM seasons 
                    WHERE is_published = 1 AND is_active = 1 
                    ORDER BY display_order ASC
                ");
                $stmt->execute();
                $seasons = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['seasons'] = $seasons;
                break;
                
            case 'get_season_details':
                $seasonId = $_POST['season_id'] ?? 0;
                
                if (!$seasonId) {
                    $response['message'] = 'ID de temporada requerido';
                    break;
                }
                
                $stmt = $pdo->prepare("
                    SELECT s.*, 
                           COUNT(c.id) as total_chapters,
                           SUM(CASE WHEN c.is_free = 1 THEN 1 ELSE 0 END) as free_chapters
                    FROM seasons s
                    LEFT JOIN chapters c ON s.id = c.season_id AND c.is_published = 1
                    WHERE s.id = ?
                    GROUP BY s.id
                ");
                $stmt->execute([$seasonId]);
                $season = $stmt->fetch();
                
                if ($season) {
                    // Obtener capítulos de la temporada
                    $stmt = $pdo->prepare("
                        SELECT * FROM chapters 
                        WHERE season_id = ? AND is_published = 1 
                        ORDER BY chapter_number ASC
                    ");
                    $stmt->execute([$seasonId]);
                    $chapters = $stmt->fetchAll();
                    
                    $response['success'] = true;
                    $response['season'] = $season;
                    $response['chapters'] = $chapters;
                } else {
                    $response['message'] = 'Temporada no encontrada';
                }
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