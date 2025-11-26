<?php
session_start();
require_once '../config/database.php';
require_once '../models/Season.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$seasonModel = new Season($pdo);
$message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_season':
            $data = [
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'requires_payment' => isset($_POST['requires_payment']) ? 1 : 0,
                'display_order' => $_POST['display_order'] ?? 0,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            
            if ($seasonModel->create($data)) {
                $message = '<div class="alert alert-success">Temporada creada exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al crear temporada</div>';
            }
            break;
            
        case 'update_season':
            $id = $_POST['id'];
            $data = [
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'requires_payment' => isset($_POST['requires_payment']) ? 1 : 0,
                'display_order' => $_POST['display_order'] ?? 0,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            
            if ($seasonModel->update($id, $data)) {
                $message = '<div class="alert alert-success">Temporada actualizada exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar temporada</div>';
            }
            break;
            
        case 'delete_season':
            $id = $_POST['id'];
            if ($seasonModel->delete($id)) {
                $message = '<div class="alert alert-success">Temporada eliminada exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al eliminar temporada</div>';
            }
            break;
    }
}

// Obtener todas las temporadas
$seasons = $seasonModel->getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Temporadas - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .badge-free { background: #28a745; }
        .badge-premium { background: #ffc107; color: #000; }
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
                    <h2 class="title-font"><i class="bi bi-collection-play me-2"></i>Gestión de Temporadas</h2>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#createSeasonModal">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Temporada
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
                                        <th>Título</th>
                                        <th>Subtítulo</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Capítulos</th>
                                        <th>Orden</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seasons as $season): ?>
                                    <tr>
                                        <td><?= $season['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($season['title']) ?></strong>
                                            <?php if ($season['requires_payment']): ?>
                                                <span class="badge badge-premium ms-1">Premium</span>
                                            <?php else: ?>
                                                <span class="badge badge-free ms-1">Gratis</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($season['subtitle'] ?? '') ?></td>
                                        <td>$<?= number_format($season['price'], 2) ?> USD</td>
                                        <td>
                                            <?php if ($season['is_published']): ?>
                                                <span class="badge bg-success">Publicado</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Borrador</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapters WHERE season_id = ?");
                                            $stmt->execute([$season['id']]);
                                            $chapterCount = $stmt->fetchColumn();
                                            ?>
                                            <span class="badge bg-info"><?= $chapterCount ?> capítulos</span>
                                        </td>
                                        <td><?= $season['display_order'] ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editSeasonModal"
                                                        data-id="<?= $season['id'] ?>"
                                                        data-title="<?= htmlspecialchars($season['title']) ?>"
                                                        data-subtitle="<?= htmlspecialchars($season['subtitle'] ?? '') ?>"
                                                        data-description="<?= htmlspecialchars($season['description'] ?? '') ?>"
                                                        data-price="<?= $season['price'] ?>"
                                                        data-requires-payment="<?= $season['requires_payment'] ?>"
                                                        data-display-order="<?= $season['display_order'] ?>"
                                                        data-is-published="<?= $season['is_published'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_season">
                                                    <input type="hidden" name="id" value="<?= $season['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('¿Estás seguro de eliminar esta temporada?')">
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

    <!-- Modal Crear Temporada -->
    <div class="modal fade" id="createSeasonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Temporada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_season">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subtítulo</label>
                                    <input type="text" class="form-control" name="subtitle">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio (USD)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Orden de Visualización</label>
                                    <input type="number" class="form-control" name="display_order" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_published" id="createPublished">
                                        <label class="form-check-label" for="createPublished">Publicado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requires_payment" id="createPayment">
                                        <label class="form-check-label" for="createPayment">Requiere Pago</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Crear Temporada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Temporada -->
    <div class="modal fade" id="editSeasonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Temporada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_season">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="title" id="editTitle" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subtítulo</label>
                                    <input type="text" class="form-control" name="subtitle" id="editSubtitle">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio (USD)</label>
                                    <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Orden de Visualización</label>
                                    <input type="number" class="form-control" name="display_order" id="editDisplayOrder">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_published" id="editPublished">
                                        <label class="form-check-label" for="editPublished">Publicado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requires_payment" id="editPayment">
                                        <label class="form-check-label" for="editPayment">Requiere Pago</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Temporada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para el modal de edición
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editSeasonModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                document.getElementById('editId').value = button.getAttribute('data-id');
                document.getElementById('editTitle').value = button.getAttribute('data-title');
                document.getElementById('editSubtitle').value = button.getAttribute('data-subtitle');
                document.getElementById('editDescription').value = button.getAttribute('data-description');
                document.getElementById('editPrice').value = button.getAttribute('data-price');
                document.getElementById('editDisplayOrder').value = button.getAttribute('data-display-order');
                
                // Checkboxes
                document.getElementById('editPublished').checked = button.getAttribute('data-is-published') === '1';
                document.getElementById('editPayment').checked = button.getAttribute('data-requires-payment') === '1';
            });
        });
    </script>
</body>
</html>