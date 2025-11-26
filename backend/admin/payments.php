<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_payment_status':
            $id = $_POST['id'];
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                $message = '<div class="alert alert-success">Estado de pago actualizado</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar estado</div>';
            }
            break;
    }
}

// Obtener todos los pagos con información de usuarios y temporadas
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email, s.title as season_title 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    JOIN seasons s ON p.season_id = s.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$payments = $stmt->fetchAll();

// Estadísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
    'total_amount' => $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn() ?: 0
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .badge-completed { background: #28a745; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-failed { background: #dc3545; }
        .stat-card { background: linear-gradient(145deg, #1A1A1A, #0F0F0F); border-radius: 12px; padding: 20px; }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="title-font"><i class="bi bi-credit-card me-2"></i>Gestión de Pagos</h2>
                </div>

                <?= $message ?>

                <!-- Estadísticas -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="bi bi-credit-card display-4 text-warning mb-3"></i>
                            <h3><?= $stats['total'] ?></h3>
                            <p class="mb-0">Total Pagos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="bi bi-check-circle display-4 text-success mb-3"></i>
                            <h3><?= $stats['completed'] ?></h3>
                            <p class="mb-0">Completados</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="bi bi-clock display-4 text-warning mb-3"></i>
                            <h3><?= $stats['pending'] ?></h3>
                            <p class="mb-0">Pendientes</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="bi bi-currency-dollar display-4 text-info mb-3"></i>
                            <h3>$<?= number_format($stats['total_amount'], 2) ?></h3>
                            <p class="mb-0">Total Recaudado</p>
                        </div>
                    </div>
                </div>

                <div class="card bg-secondary text-light">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Temporada</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= $payment['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($payment['username']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($payment['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($payment['season_title']) ?></td>
                                        <td>$<?= number_format($payment['amount'], 2) ?> USD</td>
                                        <td>
                                            <?php
                                            $methodBadge = [
                                                'transfer' => '<span class="badge bg-info">Transferencia</span>',
                                                'cash' => '<span class="badge bg-success">Efectivo</span>'
                                            ];
                                            echo $methodBadge[$payment['payment_method']] ?? '<span class="badge bg-secondary">' . $payment['payment_method'] . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = [
                                                'completed' => 'badge-completed',
                                                'pending' => 'badge-pending',
                                                'failed' => 'badge-failed'
                                            ];
                                            ?>
                                            <span class="badge <?= $statusBadge[$payment['status']] ?>">
                                                <?= ucfirst($payment['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editPaymentModal"
                                                        data-id="<?= $payment['id'] ?>"
                                                        data-status="<?= $payment['status'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Pago -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar Estado de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_payment_status">
                        <input type="hidden" name="id" id="editPaymentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Estado del Pago</label>
                            <select class="form-control" name="status" id="editPaymentStatus" required>
                                <option value="pending">Pendiente</option>
                                <option value="completed">Completado</option>
                                <option value="failed">Fallido</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editPaymentModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                document.getElementById('editPaymentId').value = button.getAttribute('data-id');
                document.getElementById('editPaymentStatus').value = button.getAttribute('data-status');
            });
        });
    </script>
</body>
</html>