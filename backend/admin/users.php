<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$userModel = new User($pdo);
$message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_user':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            
            if ($userModel->create($username, $email, $password, $role)) {
                $message = '<div class="alert alert-success">Usuario creado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al crear usuario (posible email/usuario duplicado)</div>';
            }
            break;
            
        case 'update_user':
            $id = $_POST['id'];
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'role' => $_POST['role'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            if ($userModel->update($id, $data)) {
                $message = '<div class="alert alert-success">Usuario actualizado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar usuario</div>';
            }
            break;
            
        case 'delete_user':
            $id = $_POST['id'];
            // No permitir eliminar el usuario actual
            if ($id == $_SESSION['user_id']) {
                $message = '<div class="alert alert-danger">No puedes eliminar tu propio usuario</div>';
            } else if ($userModel->delete($id)) {
                $message = '<div class="alert alert-success">Usuario eliminado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al eliminar usuario</div>';
            }
            break;
            
        // NUEVAS ACCIONES PARA LIBERAR CONTENIDO
        case 'grant_season_access':
            $userId = $_POST['user_id'];
            $seasonId = $_POST['season_id'];
            
            // Verificar si ya existe un pago
            $stmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND season_id = ?");
            $stmt->execute([$userId, $seasonId]);
            
            if (!$stmt->fetch()) {
                // Crear pago completado
                $seasonStmt = $pdo->prepare("SELECT price FROM seasons WHERE id = ?");
                $seasonStmt->execute([$seasonId]);
                $season = $seasonStmt->fetch();
                
                $stmt = $pdo->prepare("
                    INSERT INTO payments (user_id, season_id, amount, payment_method, status, created_at) 
                    VALUES (?, ?, ?, 'manual', 'completed', NOW())
                ");
                if ($stmt->execute([$userId, $seasonId, $season['price']])) {
                    $message = '<div class="alert alert-success">Acceso a temporada liberado exitosamente</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error al liberar acceso</div>';
                }
            } else {
                $message = '<div class="alert alert-warning">El usuario ya tiene acceso a esta temporada</div>';
            }
            break;
            
        case 'grant_chapter_access':
            $userId = $_POST['user_id'];
            $chapterId = $_POST['chapter_id'];
            
            // Hacer el capítulo gratuito para este usuario específico
            // Esto requiere una nueva tabla o modificar la lógica
            // Por ahora, vamos a crear un registro especial en payments para el capítulo
            $chapterStmt = $pdo->prepare("SELECT season_id FROM chapters WHERE id = ?");
            $chapterStmt->execute([$chapterId]);
            $chapter = $chapterStmt->fetch();
            
            if ($chapter) {
                // Verificar si ya tiene acceso
                $accessStmt = $pdo->prepare("
                    SELECT p.id FROM payments p 
                    WHERE p.user_id = ? AND p.season_id = ? AND p.status = 'completed'
                ");
                $accessStmt->execute([$userId, $chapter['season_id']]);
                
                if (!$accessStmt->fetch()) {
                    $message = '<div class="alert alert-warning">El usuario necesita acceso a la temporada primero</div>';
                } else {
                    $message = '<div class="alert alert-success">El usuario ya tiene acceso a este capítulo (vía temporada)</div>';
                }
            }
            break;
    }
}

// Obtener todos los usuarios
$users = $userModel->getAll();

// Obtener temporadas y capítulos para los modales
$seasons = $pdo->query("SELECT * FROM seasons WHERE is_published = 1 ORDER BY title")->fetchAll();
$chapters = $pdo->query("
    SELECT c.*, s.title as season_title 
    FROM chapters c 
    JOIN seasons s ON c.season_id = s.id 
    WHERE c.is_published = 1 
    ORDER BY s.title, c.chapter_number
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .badge-admin { background: #dc3545; }
        .badge-writer { background: #fd7e14; }
        .badge-user { background: #20c997; }
        .actions-column { width: 200px; }
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
                    <h2 class="title-font"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>

                <?= $message ?>

                <div class="card bg-secondary text-light">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Registro</th>
                                        <th class="actions-column">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary ms-1">Tú</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'admin' => 'badge-admin',
                                                'writer' => 'badge-writer',
                                                'user' => 'badge-user'
                                            ];
                                            ?>
                                            <span class="badge <?= $badgeClass[$user['role']] ?>"><?= ucfirst($user['role']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-id="<?= $user['id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-role="<?= $user['role'] ?>"
                                                        data-is-active="<?= $user['is_active'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <!-- Botón para liberar contenido -->
                                                <button class="btn btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#grantAccessModal"
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>">
                                                    <i class="bi bi-unlock"></i>
                                                </button>
                                                
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
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

    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rol *</label>
                            <select class="form-control" name="role" required>
                                <option value="user">Usuario</option>
                                <option value="writer">Escritor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rol *</label>
                            <select class="form-control" name="role" id="editRole" required>
                                <option value="user">Usuario</option>
                                <option value="writer">Escritor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                                <label class="form-check-label" for="editIsActive">Usuario Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Liberar Acceso -->
    <div class="modal fade" id="grantAccessModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Liberar Acceso a Contenido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="grantUserId">
                    <p>Usuario: <strong id="grantUsername"></strong></p>
                    
                    <div class="row">
                        <!-- Liberar Temporada -->
                        <div class="col-md-6">
                            <div class="card bg-secondary mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-collection-play me-2"></i>Liberar Temporada</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="grantSeasonForm">
                                        <input type="hidden" name="action" value="grant_season_access">
                                        <input type="hidden" name="user_id" id="seasonUserId">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Seleccionar Temporada</label>
                                            <select class="form-control" name="season_id" required>
                                                <option value="">Seleccionar temporada...</option>
                                                <?php foreach ($seasons as $season): ?>
                                                    <option value="<?= $season['id'] ?>">
                                                        <?= htmlspecialchars($season['title']) ?> 
                                                        - $<?= number_format($season['price'], 2) ?>
                                                        <?= $season['requires_payment'] ? '(Premium)' : '(Gratis)' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-unlock me-2"></i>Liberar Temporada
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ver Accesos Actuales -->
                        <div class="col-md-6">
                            <div class="card bg-secondary">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Accesos Actuales</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Obtener accesos actuales del usuario
                                    $accessStmt = $pdo->prepare("
                                        SELECT s.title, p.created_at 
                                        FROM payments p 
                                        JOIN seasons s ON p.season_id = s.id 
                                        WHERE p.user_id = ? AND p.status = 'completed'
                                        ORDER BY p.created_at DESC
                                    ");
                                    ?>
                                    <div id="currentAccesses">
                                        <p class="text-muted">Selecciona un usuario para ver sus accesos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal editar usuario
            const editModal = document.getElementById('editUserModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                document.getElementById('editId').value = button.getAttribute('data-id');
                document.getElementById('editUsername').value = button.getAttribute('data-username');
                document.getElementById('editEmail').value = button.getAttribute('data-email');
                document.getElementById('editRole').value = button.getAttribute('data-role');
                document.getElementById('editIsActive').checked = button.getAttribute('data-is-active') === '1';
            });
            
            // Modal liberar acceso
            const grantModal = document.getElementById('grantAccessModal');
            grantModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const username = button.getAttribute('data-username');
                
                document.getElementById('grantUserId').value = userId;
                document.getElementById('grantUsername').textContent = username;
                document.getElementById('seasonUserId').value = userId;
                
                // Cargar accesos actuales
                loadCurrentAccesses(userId);
            });
            
            // Formulario liberar temporada
            document.getElementById('grantSeasonForm').addEventListener('submit', function(e) {
                if (!confirm('¿Estás seguro de liberar el acceso a esta temporada?')) {
                    e.preventDefault();
                }
            });
            
            function loadCurrentAccesses(userId) {
                fetch(`get_user_access.php?user_id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('currentAccesses');
                        if (data.success && data.accesses.length > 0) {
                            let html = '';
                            data.accesses.forEach(access => {
                                html += `
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-dark rounded">
                                        <div>
                                            <strong>${access.title}</strong>
                                            <br><small class="text-muted">${access.created_at}</small>
                                        </div>
                                        <span class="badge bg-success">Activo</span>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<p class="text-muted">El usuario no tiene accesos premium</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('currentAccesses').innerHTML = '<p class="text-danger">Error al cargar accesos</p>';
                    });
            }
        });
    </script>
</body>
</html>