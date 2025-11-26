<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verificar si el usuario está logueado
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Debes iniciar sesión para comentar';
            echo json_encode($response);
            exit;
        }

        $chapterId = $_POST['chapter_id'] ?? 0;
        $userId = $_SESSION['user_id'];
        $comment = trim($_POST['comment'] ?? '');
        $rating = $_POST['rating'] ?? null;

        // Validaciones
        if (!$chapterId) {
            $response['message'] = 'ID de capítulo requerido';
            echo json_encode($response);
            exit;
        }

        if (empty($comment)) {
            $response['message'] = 'El comentario no puede estar vacío';
            echo json_encode($response);
            exit;
        }

        if (strlen($comment) < 5) {
            $response['message'] = 'El comentario debe tener al menos 5 caracteres';
            echo json_encode($response);
            exit;
        }

        // Validar rating
        if ($rating && ($rating < 1 || $rating > 5)) {
            $rating = null;
        }

        // Verificar que el capítulo existe y está publicado
        $stmt = $pdo->prepare("SELECT id FROM chapters WHERE id = ? AND is_published = 1");
        $stmt->execute([$chapterId]);
        
        if (!$stmt->fetch()) {
            $response['message'] = 'Capítulo no encontrado';
            echo json_encode($response);
            exit;
        }

        // Insertar comentario
        $stmt = $pdo->prepare("
            INSERT INTO comments (chapter_id, user_id, content, rating, is_approved) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$chapterId, $userId, $comment, $rating])) {
            $response['success'] = true;
            $response['message'] = 'Comentario publicado correctamente';
        } else {
            $response['message'] = 'Error al publicar comentario';
        }
    } else {
        $response['message'] = 'Método no permitido';
    }
} catch (Exception $e) {
    error_log("Error en comments API: " . $e->getMessage());
    $response['message'] = 'Error del servidor';
}

echo json_encode($response);
?>