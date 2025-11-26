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
        case 'approve_comment':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Comentario aprobado</div>';
            }
            break;
            
        case 'reject_comment':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE comments SET is_approved = 0 WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-warning">Comentario rechazado</div>';
            }
            break;
            
        case 'delete_comment':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-danger">Comentario eliminado</div>';
            }
            break;
            
        case 'bulk_action':
            $actionType = $_POST['bulk_action_type'];
            $commentIds = $_POST['comment_ids'] ?? [];
            
            if (!empty($commentIds)) {
                $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';
                
                if ($actionType === 'approve') {
                    $stmt = $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE id IN ($placeholders)");
                } elseif ($actionType === 'reject') {
                    $stmt = $pdo->prepare("UPDATE comments SET is_approved = 0 WHERE id IN ($placeholders)");
                } else {
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                }
                
                if ($stmt->execute($commentIds)) {
                    $message = '<div class="alert alert-success">Acción completada</div>';
                }
            }
            break;
    }
}

// Obtener todos los comentarios con información de usuario y capítulo
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.username, 
               u.email,
               ch.title as chapter_title,
               s.title as season_title
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN chapters ch ON c.chapter_id = ch.id
        LEFT JOIN seasons s ON ch.season_id = s.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $comments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error getting comments: " . $e->getMessage());
    $comments = [];
}

// Contar comentarios pendientes
$pendingCount = 0;
foreach ($comments as $comment) {
    if (!$comment['is_approved']) {
        $pendingCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Comentarios - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .pending { background: rgba(255, 193, 7, 0.1); }
        .comment-preview { max-height: 100px; overflow: hidden; }
        .rating-stars { color: #D4AF37; }
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
                    <h2 class="title-font">
                        <i class="bi bi-chat-dots me-2"></i>Gestión de Comentarios
                        <?php if ($pendingCount > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $pendingCount ?> pendientes</span>
                        <?php endif; ?>
                    </h2>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="bulkActionSelect" style="width: auto;">
                            <option value="">Acciones en lote</option>
                            <option value="approve">Aprobar seleccionados</option>
                            <option value="reject">Rechazar seleccionados</option>
                            <option value="delete">Eliminar seleccionados</option>
                        </select>
                        <button class="btn btn-warning btn-sm" onclick="applyBulkAction()">Aplicar</button>
                    </div>
                </div>

                <?= $message ?>

                <div class="card bg-secondary text-light">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Usuario</th>
                                        <th>Capítulo</th>
                                        <th>Comentario</th>
                                        <th>Rating</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                    <tr class="<?= !$comment['is_approved'] ? 'pending' : '' ?>">
                                        <td>
                                            <input type="checkbox" class="comment-checkbox" name="comment_ids[]" value="<?= $comment['id'] ?>">
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($comment['email']) ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($comment['chapter_title']) ?></strong>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($comment['season_title']) ?></small>
                                        </td>
                                        <td>
                                            <div class="comment-preview">
                                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($comment['rating'] > 0): ?>
                                                <div class="rating-stars">
                                                    <?= str_repeat('★', $comment['rating']) ?><?= str_repeat('☆', 5 - $comment['rating']) ?>
                                                </div>
                                                <small class="text-muted">(<?= $comment['rating'] ?>/5)</small>
                                            <?php else: ?>
                                                <span class="text-muted">Sin rating</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($comment['is_approved']): ?>
                                                <span class="badge bg-success">Aprobado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$comment['is_approved']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve_comment">
                                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Aprobar">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($comment['is_approved']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject_comment">
                                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning" title="Rechazar">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('¿Eliminar este comentario?')" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seleccionar/deseleccionar todos
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.comment-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        // Acciones en lote
        function applyBulkAction() {
            const actionType = document.getElementById('bulkActionSelect').value;
            const selectedComments = Array.from(document.querySelectorAll('.comment-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            if (!actionType) {
                alert('Por favor selecciona una acción');
                return;
            }
            
            if (selectedComments.length === 0) {
                alert('Por favor selecciona al menos un comentario');
                return;
            }
            
            const actionText = {
                'approve': 'aprobar',
                'reject': 'rechazar', 
                'delete': 'eliminar'
            }[actionType];
            
            if (confirm(`¿Estás seguro de ${actionText} ${selectedComments.length} comentario(s)?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'bulk_action';
                form.appendChild(actionInput);
                
                const bulkActionInput = document.createElement('input');
                bulkActionInput.name = 'bulk_action_type';
                bulkActionInput.value = actionType;
                form.appendChild(bulkActionInput);
                
                selectedComments.forEach(commentId => {
                    const input = document.createElement('input');
                    input.name = 'comment_ids[]';
                    input.value = commentId;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>