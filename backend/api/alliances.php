<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/Alliance.php';

// Inicializar respuesta
$response = ['success' => false, 'message' => 'Acción no especificada', 'alliances' => []];

try {
    // Determinar el método de entrada (POST o GET)
    $input = $_POST;
    if (empty($input) && isset($_GET['action'])) {
        $input = $_GET;
    }
    
    $action = $input['action'] ?? '';
    $allianceModel = new Alliance($pdo);

    if ($action === 'get_alliances') {
        // Obtener todas las alianzas activas (para frontend)
        $alliances = $allianceModel->getAllActive();
        $response['success'] = true;
        $response['alliances'] = $alliances;
        $response['message'] = count($alliances) . ' alianzas encontradas';
        
    } else {
        $response['message'] = 'Acción no válida. Acciones permitidas: get_alliances';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log("API Error: " . $e->getMessage());
}

echo json_encode($response);
?>