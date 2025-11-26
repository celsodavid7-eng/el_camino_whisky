<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'search_content':
                $query = $_POST['query'] ?? '';
                $type = $_POST['type'] ?? 'all'; // all, chapters, seasons, categories
                
                if (empty($query)) {
                    $response['message'] = 'Término de búsqueda requerido';
                    break;
                }
                
                $searchResults = [];
                $searchTerm = '%' . $query . '%';
                
                // Búsqueda en capítulos
                if (in_array($type, ['all', 'chapters'])) {
                    $stmt = $pdo->prepare("
                        SELECT c.*, s.title as season_title, s.id as season_id 
                        FROM chapters c 
                        JOIN seasons s ON c.season_id = s.id 
                        WHERE (c.title LIKE ? OR c.subtitle LIKE ? OR c.content LIKE ?) 
                        AND c.is_published = 1 
                        ORDER BY c.chapter_number ASC
                    ");
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $searchResults['chapters'] = $stmt->fetchAll();
                }
                
                // Búsqueda en temporadas
                if (in_array($type, ['all', 'seasons'])) {
                    $stmt = $pdo->prepare("
                        SELECT s.*, 
                               COUNT(c.id) as chapter_count 
                        FROM seasons s 
                        LEFT JOIN chapters c ON s.id = c.season_id 
                        WHERE (s.title LIKE ? OR s.subtitle LIKE ? OR s.description LIKE ?) 
                        AND s.is_published = 1 
                        GROUP BY s.id 
                        ORDER BY s.display_order ASC
                    ");
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $searchResults['seasons'] = $stmt->fetchAll();
                }
                
                // Búsqueda en categorías
                if (in_array($type, ['all', 'categories'])) {
                    $stmt = $pdo->prepare("
                        SELECT cat.*, COUNT(cc.chapter_id) as usage_count 
                        FROM categories cat 
                        LEFT JOIN chapter_categories cc ON cat.id = cc.category_id 
                        WHERE cat.name LIKE ? OR cat.description LIKE ? 
                        GROUP BY cat.id 
                        ORDER BY cat.name ASC
                    ");
                    $stmt->execute([$searchTerm, $searchTerm]);
                    $searchResults['categories'] = $stmt->fetchAll();
                }
                
                $response['success'] = true;
                $response['results'] = $searchResults;
                $response['query'] = $query;
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