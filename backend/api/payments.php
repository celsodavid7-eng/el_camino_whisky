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
        
        if ($action === 'create_payment') {
            $season_id = $_POST['season_id'] ?? '';
            $amount = $_POST['amount'] ?? '';
            $payment_method = $_POST['payment_method'] ?? 'transfer';
            
            if (empty($season_id)) {
                $response['message'] = 'Selecciona una temporada';
                echo json_encode($response);
                exit;
            }
            
            // Obtener información de la temporada
            $seasonStmt = $pdo->prepare("SELECT title, price FROM seasons WHERE id = ? AND is_published = 1");
            $seasonStmt->execute([$season_id]);
            $season = $seasonStmt->fetch();
            
            if (!$season) {
                $response['message'] = 'Temporada no válida';
                echo json_encode($response);
                exit;
            }
            
            // Obtener información del usuario
            $userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
            $userStmt->execute([$user_id]);
            $user = $userStmt->fetch();
            
            // Crear registro de pago pendiente
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, season_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $season_id, $season['price'], $payment_method]);
            
            $response['success'] = true;
            $response['message'] = 'Pago creado exitosamente';
            $response['data'] = [
                'season_title' => $season['title'],
                'amount' => $season['price'],
                'payment_method' => $payment_method,
                'user_email' => $user['email']
            ];
            
        } else {
            $response['message'] = 'Acción no válida';
        }
    }
    
} catch (Exception $e) {
    error_log("Payments error: " . $e->getMessage());
    $response['message'] = 'Error del servidor';
}

echo json_encode($response);
?>