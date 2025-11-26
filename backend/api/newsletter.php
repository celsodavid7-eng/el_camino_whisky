<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'subscribe':
                $email = $_POST['email'] ?? '';
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response['message'] = 'Email válido requerido';
                    break;
                }
                
                // Crear tabla si no existe
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        is_active TINYINT DEFAULT 1,
                        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Verificar si ya está suscrito
                $stmt = $pdo->prepare("SELECT id FROM newsletter_subscriptions WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $response['message'] = 'Este email ya está suscrito';
                    break;
                }
                
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
                if ($stmt->execute([$email])) {
                    $response['success'] = true;
                    $response['message'] = '¡Suscripción exitosa! Gracias por unirte.';
                } else {
                    $response['message'] = 'Error en la suscripción';
                }
                break;
                
            case 'get_subscribers':
                // Verificar si es admin
                session_start();
                if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                    $response['message'] = 'No autorizado';
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM newsletter_subscriptions WHERE is_active = 1 ORDER BY subscribed_at DESC");
                $stmt->execute();
                $subscribers = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['subscribers'] = $subscribers;
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