<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_user_profile':
                $userId = $_POST['user_id'] ?? $_SESSION['user_id'] ?? 0;
                
                if (!$userId) {
                    $response['message'] = 'Usuario no autenticado';
                    break;
                }
                
                $stmt = $pdo->prepare("
                    SELECT id, username, email, role, created_at 
                    FROM users 
                    WHERE id = ? AND is_active = 1
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Obtener temporadas compradas
                    $stmt = $pdo->prepare("
                        SELECT s.*, p.created_at as purchase_date 
                        FROM payments p 
                        JOIN seasons s ON p.season_id = s.id 
                        WHERE p.user_id = ? AND p.status = 'completed'
                        ORDER BY p.created_at DESC
                    ");
                    $stmt->execute([$userId]);
                    $purchasedSeasons = $stmt->fetchAll();
                    
                    // Obtener comentarios del usuario
                    $stmt = $pdo->prepare("
                        SELECT c.*, ch.title as chapter_title, s.title as season_title 
                        FROM comments c 
                        JOIN chapters ch ON c.chapter_id = ch.id 
                        JOIN seasons s ON ch.season_id = s.id 
                        WHERE c.user_id = ? 
                        ORDER BY c.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$userId]);
                    $userComments = $stmt->fetchAll();
                    
                    $response['success'] = true;
                    $response['user'] = $user;
                    $response['purchased_seasons'] = $purchasedSeasons;
                    $response['recent_comments'] = $userComments;
                } else {
                    $response['message'] = 'Usuario no encontrado';
                }
                break;
                
            case 'update_profile':
                $userId = $_SESSION['user_id'] ?? 0;
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if (!$userId) {
                    $response['message'] = 'Usuario no autenticado';
                    break;
                }
                
                if (empty($username) || empty($email)) {
                    $response['message'] = 'Nombre de usuario y email son requeridos';
                    break;
                }
                
                // Verificar si el email ya existe en otro usuario
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $response['message'] = 'El email ya está en uso por otro usuario';
                    break;
                }
                
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$username, $email, $userId])) {
                    $_SESSION['user_name'] = $username;
                    $response['success'] = true;
                    $response['message'] = 'Perfil actualizado exitosamente';
                } else {
                    $response['message'] = 'Error al actualizar perfil';
                }
                break;
                
            case 'change_password':
                $userId = $_SESSION['user_id'] ?? 0;
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                
                if (!$userId) {
                    $response['message'] = 'Usuario no autenticado';
                    break;
                }
                
                if (empty($currentPassword) || empty($newPassword)) {
                    $response['message'] = 'Contraseña actual y nueva contraseña son requeridas';
                    break;
                }
                
                // Verificar contraseña actual
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $response['message'] = 'Contraseña actual incorrecta';
                    break;
                }
                
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $userId])) {
                    $response['success'] = true;
                    $response['message'] = 'Contraseña cambiada exitosamente';
                } else {
                    $response['message'] = 'Error al cambiar contraseña';
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