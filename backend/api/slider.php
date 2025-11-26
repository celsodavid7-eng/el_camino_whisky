<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("
            SELECT hs.*, 
                   c.title as chapter_title, 
                   c.subtitle as chapter_subtitle,
                   s.title as season_title,
                   s.id as season_id
            FROM home_slider hs
            LEFT JOIN chapters c ON hs.chapter_id = c.id
            LEFT JOIN seasons s ON c.season_id = s.id
            WHERE hs.is_active = 1 
            ORDER BY hs.display_order ASC, hs.created_at DESC
        ");
        $stmt->execute();
        $sliderItems = $stmt->fetchAll();
        
        $response['success'] = true;
        $response['slider_items'] = $sliderItems;
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_active_slider':
                $stmt = $pdo->prepare("
                    SELECT hs.*, 
                           c.title as chapter_title, 
                           c.subtitle as chapter_subtitle,
                           s.title as season_title,
                           s.id as season_id
                    FROM home_slider hs
                    LEFT JOIN chapters c ON hs.chapter_id = c.id
                    LEFT JOIN seasons s ON c.season_id = s.id
                    WHERE hs.is_active = 1 
                    ORDER BY hs.display_order ASC, hs.created_at DESC
                ");
                $stmt->execute();
                $sliderItems = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['slider_items'] = $sliderItems;
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