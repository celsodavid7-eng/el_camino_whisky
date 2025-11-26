<?php
session_start();
require_once '../config/database.php';
require_once '../models/Contact.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$contactModel = new Contact($pdo);
$message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_as_read':
            $id = $_POST['id'];
            if ($contactModel->markAsRead($id)) {
                $message = '<div class="alert alert-success">Mensaje marcado como leído</div>';
            }
            break;
            
        case 'delete_message':
            $id = $_POST['id'];
            if ($contactModel->delete($id)) {
                $message = '<div class="alert alert-success">Mensaje eliminado</div>';
            }
            break;
            
        case 'bulk_action':
            $actionType = $_POST['bulk_action_type'];
            $messageIds = $_POST['message_ids'] ?? [];
            
            if (!empty($messageIds)) {
                $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
                
                if ($actionType === 'mark_read') {
                    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id IN ($placeholders)");
                } else {
                    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
                }
                
                if ($stmt->execute($messageIds)) {
                    $message = '<div class="alert alert-success">Acción completada</div>';
                }
            }
            break;
    }
}

// Obtener mensajes de contacto
$contacts = $contactModel->getAll();
$unreadCount = $contactModel->getUnreadCount();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contactos - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .unread { background: rgba(13, 110, 253, 0.1); }
        .message-preview { max-height: 100px; overflow: hidden; }
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
                        <i class="bi bi-envelope me-2"></i>Mensajes de Contacto
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $unreadCount ?> nuevos</span>
                        <?php endif; ?>
                    </h2>
                    
                    <!-- Bulk Actions -->
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="bulkActionSelect" style="width: auto;">
                            <option value="">Acciones en lote</option>
                            <option value="mark_read">Marcar como leído</option>
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
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Asunto</th>
                                        <th>Mensaje</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                    <tr class="<?= !$contact['is_read'] ? 'unread' : '' ?>">
                                        <td>
                                            <input type="checkbox" class="message-checkbox" name="message_ids[]" value="<?= $contact['id'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($contact['name']) ?></strong>
                                        </td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="text-warning">
                                                <?= htmlspecialchars($contact['email']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($contact['subject']) ?></td>
                                        <td>
                                            <div class="message-preview">
                                                <?= nl2br(htmlspecialchars($contact['message'])) ?>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></td>
                                        <td>
                                            <?php if ($contact['is_read']): ?>
                                                <span class="badge bg-success">Leído</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Nuevo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$contact['is_read']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_as_read">
                                                    <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_message">
                                                    <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('¿Eliminar este mensaje?')">
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
            const checkboxes = document.querySelectorAll('.message-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        // Acciones en lote
        function applyBulkAction() {
            const actionType = document.getElementById('bulkActionSelect').value;
            const selectedMessages = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            if (!actionType) {
                alert('Por favor selecciona una acción');
                return;
            }
            
            if (selectedMessages.length === 0) {
                alert('Por favor selecciona al menos un mensaje');
                return;
            }
            
            if (confirm(`¿Estás seguro de ${actionType === 'mark_read' ? 'marcar como leído' : 'eliminar'} ${selectedMessages.length} mensaje(s)?`)) {
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
                
                selectedMessages.forEach(messageId => {
                    const input = document.createElement('input');
                    input.name = 'message_ids[]';
                    input.value = messageId;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>