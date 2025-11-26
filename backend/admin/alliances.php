<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_alliance'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $website = $_POST['website'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Manejar upload de logo
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $uploadDir = '../uploads/alliances/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $fileName = 'alliance_' . time() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $logo = $fileName;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO alliances (name, description, website, logo, is_active) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $website, $logo, $is_active])) {
            $_SESSION['success'] = 'Alianza agregada correctamente';
        } else {
            $_SESSION['error'] = 'Error al agregar la alianza';
        }
    }
    
    if (isset($_POST['update_alliance'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $website = $_POST['website'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Manejar upload de logo
        $logo = $_POST['current_logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $uploadDir = '../uploads/alliances/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Eliminar logo anterior si existe
            if ($logo && file_exists($uploadDir . $logo)) {
                unlink($uploadDir . $logo);
            }
            
            $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $fileName = 'alliance_' . time() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $logo = $fileName;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE alliances SET name = ?, description = ?, website = ?, logo = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$name, $description, $website, $logo, $is_active, $id])) {
            $_SESSION['success'] = 'Alianza actualizada correctamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar la alianza';
        }
    }
    
    if (isset($_POST['delete_alliance'])) {
        $id = $_POST['id'];
        
        // Obtener logo para eliminarlo
        $stmt = $pdo->prepare("SELECT logo FROM alliances WHERE id = ?");
        $stmt->execute([$id]);
        $alliance = $stmt->fetch();
        
        if ($alliance && $alliance['logo']) {
            $logoPath = '../uploads/alliances/' . $alliance['logo'];
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM alliances WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = 'Alianza eliminada correctamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar la alianza';
        }
    }
    
    header('Location: alliances.php');
    exit;
}

// Obtener alianzas
$stmt = $pdo->query("SELECT * FROM alliances ORDER BY created_at DESC");
$alliances = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alianzas - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">

    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .alliance-logo-admin { max-height: 60px; max-width: 120px; object-fit: contain; }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Incluir Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="title-font">Gestión de Alianzas</h2>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addAllianceModal">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Alianza
                    </button>
                </div>

                <!-- Mensajes de éxito/error -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Tabla de Alianzas -->
                <div class="card bg-dark border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-handshake me-2"></i>Lista de Alianzas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Logo</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Sitio Web</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($alliances)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-handshake display-4 d-block mb-2"></i>
                                                No hay alianzas registradas
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($alliances as $alliance): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($alliance['logo']): ?>
                                                        <img src="../uploads/alliances/<?= $alliance['logo'] ?>" 
                                                             alt="<?= htmlspecialchars($alliance['name']) ?>" 
                                                             class="alliance-logo-admin">
                                                    <?php else: ?>
                                                        <i class="bi bi-building text-warning fs-4"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($alliance['name']) ?></td>
                                                <td>
                                                    <?php 
                                                    $description = $alliance['description'];
                                                    echo strlen($description) > 50 ? 
                                                        substr($description, 0, 50) . '...' : 
                                                        $description;
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($alliance['website']): ?>
                                                        <a href="<?= htmlspecialchars($alliance['website']) ?>" 
                                                           target="_blank" class="text-warning">
                                                            <i class="bi bi-link-45deg"></i> Visitar
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No especificado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $alliance['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $alliance['is_active'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editAllianceModal"
                                                                onclick="editAlliance(<?= htmlspecialchars(json_encode($alliance)) ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $alliance['id'] ?>">
                                                            <button type="submit" name="delete_alliance" 
                                                                    class="btn btn-outline-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar esta alianza?')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Alianza -->
    <div class="modal fade" id="addAllianceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-warning">
                    <h5 class="modal-title">Agregar Nueva Alianza</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Alianza *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Logo</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" class="form-control" name="website" placeholder="https://...">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Alianza Activa</label>
                        </div>
                    </div>
                    <div class="modal-footer border-warning">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_alliance" class="btn btn-warning">Guardar Alianza</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Alianza -->
    <div class="modal fade" id="editAllianceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-warning">
                    <h5 class="modal-title">Editar Alianza</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="current_logo" id="edit_current_logo">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Alianza *</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Logo</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                    <div id="current_logo_preview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" class="form-control" name="website" id="edit_website" placeholder="https://...">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">Alianza Activa</label>
                        </div>
                    </div>
                    <div class="modal-footer border-warning">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_alliance" class="btn btn-warning">Actualizar Alianza</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editAlliance(alliance) {
            document.getElementById('edit_id').value = alliance.id;
            document.getElementById('edit_name').value = alliance.name;
            document.getElementById('edit_description').value = alliance.description;
            document.getElementById('edit_website').value = alliance.website || '';
            document.getElementById('edit_is_active').checked = alliance.is_active == 1;
            document.getElementById('edit_current_logo').value = alliance.logo || '';
            
            // Mostrar preview del logo actual
            const preview = document.getElementById('current_logo_preview');
            if (alliance.logo) {
                preview.innerHTML = `
                    <small class="text-muted">Logo actual:</small><br>
                    <img src="../uploads/alliances/${alliance.logo}" 
                         alt="${alliance.name}" 
                         class="alliance-logo-admin mt-1">
                `;
            } else {
                preview.innerHTML = '<small class="text-muted">No hay logo actual</small>';
            }
            
            new bootstrap.Modal(document.getElementById('editAllianceModal')).show();
        }
    </script>
</body>
</html>