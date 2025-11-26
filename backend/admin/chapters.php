<?php
session_start();
require_once '../config/database.php';
require_once '../models/Chapter.php';
require_once '../models/Season.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$chapterModel = new Chapter($pdo);
$seasonModel = new Season($pdo);
$message = '';

// Obtener el próximo orden automáticamente
$stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM chapters");
$nextOrder = $stmt->fetch()['max_order'] + 1;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_chapter':
            $data = [
                'season_id' => $_POST['season_id'],
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'] ?? '',
                'content' => $_POST['content'],
                'chapter_number' => $_POST['chapter_number'],
                'is_free' => isset($_POST['is_free']) ? 1 : 0,
                'display_order' => $_POST['display_order'] ?? $nextOrder,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            
            if ($chapterModel->create($data)) {
                $chapterId = $pdo->lastInsertId();
                
                // Procesar categorías si existen
                if (!empty($_POST['categories'])) {
                    foreach ($_POST['categories'] as $categoryId) {
                        $stmt = $pdo->prepare("INSERT INTO chapter_categories (chapter_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$chapterId, $categoryId]);
                    }
                }
                
                $message = '<div class="alert alert-success">Capítulo creado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al crear capítulo</div>';
            }
            break;
            
        case 'update_chapter':
            $id = $_POST['id'];
            $data = [
                'season_id' => $_POST['season_id'],
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'] ?? '',
                'content' => $_POST['content'],
                'chapter_number' => $_POST['chapter_number'],
                'is_free' => isset($_POST['is_free']) ? 1 : 0,
                'display_order' => $_POST['display_order'] ?? 0,
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            
            if ($chapterModel->update($id, $data)) {
                // Actualizar categorías
                $pdo->prepare("DELETE FROM chapter_categories WHERE chapter_id = ?")->execute([$id]);
                if (!empty($_POST['categories'])) {
                    foreach ($_POST['categories'] as $categoryId) {
                        $stmt = $pdo->prepare("INSERT INTO chapter_categories (chapter_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$id, $categoryId]);
                    }
                }
                
                $message = '<div class="alert alert-success">Capítulo actualizado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar capítulo</div>';
            }
            break;
            
        case 'delete_chapter':
            $id = $_POST['id'];
            if ($chapterModel->delete($id)) {
                $message = '<div class="alert alert-success">Capítulo eliminado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al eliminar capítulo</div>';
            }
            break;
    }
}

// Obtener datos
$chapters = $chapterModel->getAll();
$seasons = $seasonModel->getAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Obtener categorías por capítulo para el modal de edición
$chapterCategories = [];
foreach ($chapters as $chapter) {
    $stmt = $pdo->prepare("SELECT category_id FROM chapter_categories WHERE chapter_id = ?");
    $stmt->execute([$chapter['id']]);
    $chapterCategories[$chapter['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Capítulos - Admin Panel</title>
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
        .content-preview { max-height: 100px; overflow: hidden; }
        .order-column { width: 80px; text-align: center; }
        .number-column { width: 100px; text-align: center; }
        .actions-column { width: 150px; }
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
                    <h2 class="title-font"><i class="bi bi-play-btn me-2"></i>Gestión de Capítulos</h2>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#createChapterModal">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Capítulo
                    </button>
                </div>

                <?= $message ?>

                <div class="card bg-secondary text-light">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th class="order-column">Orden</th>
                                        <th>Título</th>
                                        <th>Temporada</th>
                                        <th class="number-column">Número</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th class="actions-column">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chapters as $chapter): ?>
                                    <tr>
                                        <td class="text-center">
                                            <strong><?= $chapter['display_order'] ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($chapter['title']) ?></strong>
                                            <?php if ($chapter['subtitle']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($chapter['subtitle']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($chapter['season_title'] ?? 'Sin temporada') ?></span>
                                        </td>
                                        <td class="text-center"><?= $chapter['chapter_number'] ?></td>
                                        <td>
                                            <?php if ($chapter['is_free']): ?>
                                                <span class="badge badge-free">Gratuito</span>
                                            <?php else: ?>
                                                <span class="badge badge-premium">Premium</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($chapter['is_published']): ?>
                                                <span class="badge bg-success">Publicado</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Borrador</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning edit-chapter-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editChapterModal"
                                                        data-id="<?= $chapter['id'] ?>"
                                                        data-season-id="<?= $chapter['season_id'] ?>"
                                                        data-title="<?= htmlspecialchars($chapter['title']) ?>"
                                                        data-subtitle="<?= htmlspecialchars($chapter['subtitle'] ?? '') ?>"
                                                        data-content="<?= htmlspecialchars($chapter['content']) ?>"
                                                        data-chapter-number="<?= $chapter['chapter_number'] ?>"
                                                        data-is-free="<?= $chapter['is_free'] ?>"
                                                        data-display-order="<?= $chapter['display_order'] ?>"
                                                        data-is-published="<?= $chapter['is_published'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="chapter_images.php?id=<?= $chapter['id'] ?>" class="btn btn-outline-info" title="Gestionar imágenes">
                                                    <i class="bi bi-image"></i>
                                                </a>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_chapter">
                                                    <input type="hidden" name="id" value="<?= $chapter['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('¿Estás seguro de eliminar este capítulo?')"
                                                            title="Eliminar capítulo">
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

    <!-- Modal Crear Capítulo -->
    <div class="modal fade" id="createChapterModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Capítulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_chapter">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Temporada *</label>
                                    <select class="form-control" name="season_id" required>
                                        <option value="">Seleccionar temporada...</option>
                                        <?php foreach ($seasons as $season): ?>
                                            <option value="<?= $season['id'] ?>"><?= htmlspecialchars($season['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Número de Capítulo *</label>
                                    <input type="number" class="form-control" name="chapter_number" required min="0">
                                    <small class="text-muted">Puede empezar desde 0</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Orden de Visualización</label>
                                    <input type="number" class="form-control" name="display_order" value="<?= $nextOrder ?>" min="0">
                                    <small class="text-muted">Automático: <?= $nextOrder ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" name="subtitle">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido *</label>
                            <textarea class="form-control" name="content" rows="10" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categorías</label>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $category['id'] ?>" id="cat_<?= $category['id'] ?>">
                                                <label class="form-check-label" for="cat_<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Configuración</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_free" id="createFree">
                                        <label class="form-check-label" for="createFree">Capítulo Gratuito</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_published" id="createPublished">
                                        <label class="form-check-label" for="createPublished">Publicado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Crear Capítulo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Capítulo -->
    <div class="modal fade" id="editChapterModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Capítulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_chapter">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Temporada *</label>
                                    <select class="form-control" name="season_id" id="editSeasonId" required>
                                        <option value="">Seleccionar temporada...</option>
                                        <?php foreach ($seasons as $season): ?>
                                            <option value="<?= $season['id'] ?>"><?= htmlspecialchars($season['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Número de Capítulo *</label>
                                    <input type="number" class="form-control" name="chapter_number" id="editChapterNumber" required min="0">
                                    <small class="text-muted">Puede empezar desde 0</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Orden de Visualización</label>
                                    <input type="number" class="form-control" name="display_order" id="editDisplayOrder" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="title" id="editTitle" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" name="subtitle" id="editSubtitle">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contenido *</label>
                            <textarea class="form-control" name="content" id="editContent" rows="10" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categorías</label>
                                    <div style="max-height: 200px; overflow-y: auto;" id="editCategoriesContainer">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="form-check">
                                                <input class="form-check-input category-checkbox" type="checkbox" name="categories[]" 
                                                       value="<?= $category['id'] ?>" id="edit_cat_<?= $category['id'] ?>">
                                                <label class="form-check-label" for="edit_cat_<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Configuración</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_free" id="editFree">
                                        <label class="form-check-label" for="editFree">Capítulo Gratuito</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_published" id="editPublished">
                                        <label class="form-check-label" for="editPublished">Publicado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Capítulo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editChapterModal');
            
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const chapterId = button.getAttribute('data-id');
                
                // Datos básicos
                document.getElementById('editId').value = chapterId;
                document.getElementById('editSeasonId').value = button.getAttribute('data-season-id');
                document.getElementById('editTitle').value = button.getAttribute('data-title');
                document.getElementById('editSubtitle').value = button.getAttribute('data-subtitle');
                document.getElementById('editContent').value = button.getAttribute('data-content');
                document.getElementById('editChapterNumber').value = button.getAttribute('data-chapter-number');
                document.getElementById('editDisplayOrder').value = button.getAttribute('data-display-order');
                
                // Checkboxes
                document.getElementById('editFree').checked = button.getAttribute('data-is-free') === '1';
                document.getElementById('editPublished').checked = button.getAttribute('data-is-published') === '1';
                
                // Cargar categorías del capítulo via AJAX
                loadChapterCategories(chapterId);
            });
            
            function loadChapterCategories(chapterId) {
                // Resetear todos los checkboxes primero
                const checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(checkbox => checkbox.checked = false);
                
                // Hacer petición AJAX para obtener las categorías del capítulo
                fetch(`get_chapter_categories.php?chapter_id=${chapterId}`)
                    .then(response => response.json())
                    .then(categories => {
                        categories.forEach(categoryId => {
                            const checkbox = document.getElementById(`edit_cat_${categoryId}`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error loading categories:', error);
                    });
            }
        });
    </script>
</body>
</html>