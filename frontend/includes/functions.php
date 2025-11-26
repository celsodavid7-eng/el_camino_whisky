<?php
// frontend/includes/functions.php

// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}

// Incluir database.php
require_once ROOT_DIR . '/backend/config/database.php';

// ... el resto del código se mantiene igual ...

function getChaptersBySeason($season_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM chapters 
        WHERE season_id = ? AND is_published = 1 
        ORDER BY display_order ASC, published_at DESC
    ");
    $stmt->execute([$season_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChapterImages($chapter_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM chapter_images 
        WHERE chapter_id = ? 
        ORDER BY image_order ASC
    ");
    $stmt->execute([$chapter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChapterCategories($chapter_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.* FROM categories c
        JOIN chapter_categories cc ON c.id = cc.category_id
        WHERE cc.chapter_id = ?
    ");
    $stmt->execute([$chapter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChapterAverageRating($chapter_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating 
        FROM comments 
        WHERE chapter_id = ? AND is_approved = 1 AND rating IS NOT NULL
    ");
    $stmt->execute([$chapter_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

function userHasAccess($user_id, $chapter_id) {
    global $pdo;
    
    // Verificar si el capítulo es gratis
    $stmt = $pdo->prepare("SELECT is_free FROM chapters WHERE id = ?");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($chapter && $chapter['is_free']) {
        return true;
    }
    
    // Verificar si usuario ha pagado por la temporada
    $stmt = $pdo->prepare("
        SELECT p.* FROM payments p
        JOIN chapters c ON p.season_id = c.season_id
        WHERE p.user_id = ? AND c.id = ? AND p.status = 'completed'
    ");
    $stmt->execute([$user_id, $chapter_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function getChapterComments($chapter_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.chapter_id = ? AND c.is_approved = 1 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$chapter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPublishedSeasons() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM seasons 
        WHERE is_published = 1 
        ORDER BY display_order ASC, created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPublishedChapters($limit = null) {
    global $pdo;
    $sql = "
        SELECT c.*, s.title as season_title 
        FROM chapters c 
        LEFT JOIN seasons s ON c.season_id = s.id 
        WHERE c.is_published = 1 AND s.is_published = 1
        ORDER BY c.display_order ASC, c.published_at DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSliderItems() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT hs.*, c.title as chapter_title, c.subtitle as chapter_subtitle, 
               ci.image_path, s.title as season_title, c.id as chapter_id
        FROM home_slider hs
        JOIN chapters c ON hs.chapter_id = c.id
        LEFT JOIN chapter_images ci ON c.id = ci.chapter_id AND ci.image_order = 0
        LEFT JOIN seasons s ON c.season_id = s.id
        WHERE hs.is_active = 1 AND c.is_published = 1
        ORDER BY hs.display_order ASC
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getWhatsAppConfig() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT config_value FROM site_config WHERE config_key = 'whatsapp_number'");
        $stmt->execute();
        $number = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT config_value FROM site_config WHERE config_key = 'whatsapp_default_message'");
        $stmt->execute();
        $defaultMessage = $stmt->fetchColumn();
        
        return [
            'number' => $number ?: '595983163300',
            'default_message' => $defaultMessage ?: 'Hola! Estoy interesado en El Camino del Whisky'
        ];
    } catch (Exception $e) {
        return [
            'number' => '595983163300',
            'default_message' => 'Hola! Estoy interesado en El Camino del Whisky'
        ];
    }
}
?>