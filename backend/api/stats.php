<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_dashboard_stats':
                // Verificar si es admin
                if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                    $response['message'] = 'No autorizado';
                    break;
                }
                
                // Estadísticas generales
                $stats = [
                    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                    'total_seasons' => $pdo->query("SELECT COUNT(*) FROM seasons")->fetchColumn(),
                    'total_chapters' => $pdo->query("SELECT COUNT(*) FROM chapters")->fetchColumn(),
                    'total_payments' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn(),
                    'total_revenue' => $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn() ?: 0,
                    'pending_comments' => $pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0")->fetchColumn(),
                    'published_seasons' => $pdo->query("SELECT COUNT(*) FROM seasons WHERE is_published = 1")->fetchColumn(),
                    'published_chapters' => $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_published = 1")->fetchColumn()
                ];
                
                // Estadísticas de pagos por mes (últimos 6 meses)
                $paymentStats = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = date('Y-m', strtotime("-$i months"));
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as revenue 
                        FROM payments 
                        WHERE status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = ?
                    ");
                    $stmt->execute([$month]);
                    $data = $stmt->fetch();
                    $paymentStats[] = [
                        'month' => $month,
                        'count' => $data['count'],
                        'revenue' => floatval($data['revenue'])
                    ];
                }
                
                // Temporadas más populares
                $popularSeasons = $pdo->query("
                    SELECT s.title, COUNT(p.id) as sales 
                    FROM payments p 
                    JOIN seasons s ON p.season_id = s.id 
                    WHERE p.status = 'completed' 
                    GROUP BY s.id 
                    ORDER BY sales DESC 
                    LIMIT 5
                ")->fetchAll();
                
                $response['success'] = true;
                $response['stats'] = $stats;
                $response['payment_stats'] = $paymentStats;
                $response['popular_seasons'] = $popularSeasons;
                break;
                
            case 'get_user_stats':
                $userId = $_POST['user_id'] ?? $_SESSION['user_id'] ?? 0;
                
                if (!$userId) {
                    $response['message'] = 'Usuario no autenticado';
                    break;
                }
                
                $userStats = [
                    'purchased_seasons' => $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'completed'")->execute([$userId])->fetchColumn(),
                    'total_comments' => $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?")->execute([$userId])->fetchColumn(),
                    'approved_comments' => $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ? AND is_approved = 1")->execute([$userId])->fetchColumn(),
                    'member_since' => $pdo->prepare("SELECT created_at FROM users WHERE id = ?")->execute([$userId])->fetchColumn()
                ];
                
                $response['success'] = true;
                $response['user_stats'] = $userStats;
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