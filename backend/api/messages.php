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
        
        if ($action === 'send_message') {
            $receiver_id = $_POST['receiver_id'] ?? '';
            $message = trim($_POST['message'] ?? '');
            
            if (empty($receiver_id) || empty($message)) {
                $response['message'] = 'Datos incompletos';
                echo json_encode($response);
                exit;
            }
            
            // Verificar que el receptor existe
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
            $userStmt->execute([$receiver_id]);
            if (!$userStmt->fetch()) {
                $response['message'] = 'Usuario receptor no válido';
                echo json_encode($response);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $message]);
            
            $response['success'] = true;
            $response['message'] = 'Mensaje enviado';
            $response['message_id'] = $pdo->lastInsertId();
            
        } elseif ($action === 'mark_read') {
            $message_id = $_POST['message_id'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
            $stmt->execute([$message_id, $user_id]);
            
            $response['success'] = true;
            $response['message'] = 'Mensaje marcado como leído';
            
        } else {
            $response['message'] = 'Acción no válida';
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'get_conversations') {
            // Obtener lista de conversaciones
            $sql = "SELECT u.id, u.username, u.avatar,
                           (SELECT message FROM private_messages 
                            WHERE (sender_id = u.id AND receiver_id = ?) 
                               OR (sender_id = ? AND receiver_id = u.id) 
                            ORDER BY created_at DESC LIMIT 1) as last_message,
                           (SELECT COUNT(*) FROM private_messages 
                            WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
                    FROM users u
                    WHERE u.id IN (
                        SELECT DISTINCT 
                            CASE 
                                WHEN sender_id = ? THEN receiver_id 
                                ELSE sender_id 
                            END as other_user
                        FROM private_messages 
                        WHERE sender_id = ? OR receiver_id = ?
                    )
                    ORDER BY (SELECT created_at FROM private_messages 
                             WHERE (sender_id = u.id AND receiver_id = ?) 
                                OR (sender_id = ? AND receiver_id = u.id) 
                             ORDER BY created_at DESC LIMIT 1) DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $conversations;
            
        } elseif ($action === 'get_messages') {
            $other_user_id = $_GET['user_id'] ?? '';
            
            if (empty($other_user_id)) {
                $response['message'] = 'ID de usuario requerido';
                echo json_encode($response);
                exit;
            }
            
            // Obtener mensajes de la conversación
            $stmt = $pdo->prepare("SELECT pm.*, u.username, u.avatar 
                                  FROM private_messages pm 
                                  JOIN users u ON pm.sender_id = u.id 
                                  WHERE (pm.sender_id = ? AND pm.receiver_id = ?) 
                                     OR (pm.sender_id = ? AND pm.receiver_id = ?) 
                                  ORDER BY pm.created_at ASC");
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            $messages = $stmt->fetchAll();
            
            // Marcar mensajes como leídos
            $updateStmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
            $updateStmt->execute([$user_id, $other_user_id]);
            
            $response['success'] = true;
            $response['data'] = $messages;
            
        } else {
            $response['message'] = 'Acción no válida';
        }
    }
    
} catch (Exception $e) {
    error_log("Messages error: " . $e->getMessage());
    $response['message'] = 'Error del servidor';
}

echo json_encode($response);
?>