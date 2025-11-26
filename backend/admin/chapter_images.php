<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Obtener ID del capítulo de forma más robusta
$chapterId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar que el ID sea válido y que el capítulo exista
if ($chapterId <= 0) {
    $_SESSION['error'] = "ID de capítulo no válido";
    header('Location: chapters.php');
    exit;
}

// Obtener información del capítulo
try {
    $stmt = $pdo->prepare("SELECT c.*, s.title as season_title FROM chapters c JOIN seasons s ON c.season_id = s.id WHERE c.id = ?");
    $stmt->execute([$chapterId]);
    $chapter = $stmt->fetch();
    
    if (!$chapter) {
        $_SESSION['error'] = "Capítulo no encontrado";
        header('Location: chapters.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar el capítulo: " . $e->getMessage();
    header('Location: chapters.php');
    exit;
}

$message = '';

// Mostrar mensajes de error de sesión si existen
if (isset($_SESSION['error'])) {
    $message = '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Procesar subida de imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_image':
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                $filePath = $uploadDir . $fileName;
                
                // Validar tipo de archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $caption = $_POST['caption'] ?? '';
                        $imageOrder = $_POST['image_order'] ?? 0;
                        
                        $stmt = $pdo->prepare("INSERT INTO chapter_images (chapter_id, image_path, caption, image_order) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$chapterId, $fileName, $caption, $imageOrder])) {
                            $message = '<div class="alert alert-success">Imagen subida exitosamente</div>';
                        } else {
                            $message = '<div class="alert alert-danger">Error al guardar imagen en la base de datos</div>';
                            unlink($filePath); // Eliminar archivo si hay error en la BD
                        }
                    } else {
                        $message = '<div class="alert alert-danger">Error al subir el archivo</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Tipo de archivo no permitido</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Error en la subida del archivo</div>';
            }
            break;
            
        case 'update_image':
            $id = $_POST['id'];
            $caption = $_POST['caption'] ?? '';
            $imageOrder = $_POST['image_order'] ?? 0;
            
            $stmt = $pdo->prepare("UPDATE chapter_images SET caption = ?, image_order = ? WHERE id = ?");
            if ($stmt->execute([$caption, $imageOrder, $id])) {
                $message = '<div class="alert alert-success">Imagen actualizada exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar imagen</div>';
            }
            break;
            
        case 'delete_image':
            $id = $_POST['id'];
            
            // Obtener información de la imagen para eliminar el archivo
            $stmt = $pdo->prepare("SELECT image_path FROM chapter_images WHERE id = ?");
            $stmt->execute([$id]);
            $image = $stmt->fetch();
            
            if ($image) {
                $filePath = '../../uploads/' . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM chapter_images WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Imagen eliminada exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al eliminar imagen</div>';
            }
            break;
            
        case 'set_featured_image':
            $id = $_POST['id'];
            // Primero resetear todas las imágenes del capítulo
            $stmt = $pdo->prepare("UPDATE chapter_images SET image_order = 0 WHERE chapter_id = ?");
            $stmt->execute([$chapterId]);
            
            // Luego establecer la imagen destacada
            $stmt = $pdo->prepare("UPDATE chapter_images SET image_order = 1 WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Imagen destacada establecida</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al establecer imagen destacada</div>';
            }
            break;
    }
}

// Obtener imágenes del capítulo
$stmt = $pdo->prepare("SELECT * FROM chapter_images WHERE chapter_id = ? ORDER BY image_order DESC, created_at DESC");
$stmt->execute([$chapterId]);
$images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Imágenes - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        body {
            background: #0d0d0d;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar { 
            background: #1a1a1a; 
            min-height: 100vh; 
            border-right: 1px solid #333;
        }
        .sidebar .nav-link { 
            color: #e0e0e0; 
            padding: 12px 20px; 
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover { 
            background: #D4AF37; 
            color: #000; 
            transform: translateX(5px);
        }
        .sidebar .nav-link.active { 
            background: #D4AF37; 
            color: #000; 
            font-weight: 600;
        }
        .stat-card { 
            background: #1e1e1e;
            border-radius: 12px; 
            padding: 25px;
            border: 1px solid #333;
            transition: transform 0.3s ease;
            height: 100%;
            color: #ffffff;
        }
        .card {
            background: #1e1e1e;
            border: 1px solid #333;
            color: #ffffff;
        }
        .card-header {
            background: #2a2a2a;
            border-bottom: 1px solid #333;
            color: #ffffff;
        }
        .form-control, .form-select {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            background: #2a2a2a;
            border-color: #D4AF37;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        .form-label {
            color: #e0e0e0;
        }
        .form-text {
            color: #aaaaaa;
        }
        .image-card { 
            background: #2a2a2a; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 20px; 
            border: 1px solid #444;
            color: #ffffff;
        }
        .image-preview { 
            max-width: 100%; 
            height: 200px; 
            object-fit: cover; 
            border-radius: 8px; 
        }
        .badge-featured { 
            background: #ffc107; 
            color: #000; 
        }
        .upload-area { 
            border: 2px dashed #D4AF37; 
            border-radius: 8px; 
            padding: 40px; 
            text-align: center; 
            cursor: pointer; 
        }
        .upload-area:hover { 
            background: rgba(212, 175, 55, 0.1); 
        }
        .btn-outline-warning {
            color: #D4AF37;
            border-color: #D4AF37;
        }
        .btn-outline-warning:hover {
            background: #D4AF37;
            color: #000;
        }
        .text-muted {
            color: #aaaaaa !important;
        }
        .modal-content {
            background: #1e1e1e;
            color: #ffffff;
        }
        .modal-header {
            border-bottom: 1px solid #333;
        }
        .modal-footer {
            border-top: 1px solid #333;
        }
        .btn-close-white {
            filter: invert(1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="text-warning"><i class="bi bi-images me-2"></i>Gestión de Imágenes</h2>
                        <p class="text-light mb-0">
                            Capítulo: <strong><?= htmlspecialchars($chapter['title']) ?></strong> 
                            (<?= htmlspecialchars($chapter['season_title']) ?>)
                        </p>
                    </div>
                    <a href="chapters.php" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-left me-2"></i>Volver a Capítulos
                    </a>
                </div>

                <?= $message ?>

                <!-- Formulario de subida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 text-warning"><i class="bi bi-upload me-2"></i>Subir Nueva Imagen</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_image">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Seleccionar Imagen *</label>
                                        <input type="file" class="form-control" name="image" accept="image/*" required>
                                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WebP. Tamaño máximo: 5MB</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Leyenda</label>
                                        <input type="text" class="form-control" name="caption" placeholder="Descripción de la imagen">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Orden</label>
                                        <input type="number" class="form-control" name="image_order" value="0" min="0">
                                        <div class="form-text">0 = normal, 1 = imagen destacada</div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-upload me-2"></i>Subir Imagen
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Galería de imágenes -->
                <div class="row">
                    <?php foreach ($images as $image): ?>
                    <div class="col-md-4 mb-4">
                        <div class="image-card">
                            <div class="position-relative">
                                <img src="../../uploads/<?= $image['image_path'] ?>" 
                                     alt="<?= htmlspecialchars($image['caption']) ?>" 
                                     class="image-preview w-100 mb-3">
                                
                                <?php if ($image['image_order'] == 1): ?>
                                    <span class="badge badge-featured position-absolute top-0 start-0 m-2">
                                        <i class="bi bi-star-fill me-1"></i>Destacada
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <?php if ($image['caption']): ?>
                                    <p class="mb-2 text-light"><strong>Leyenda:</strong> <?= htmlspecialchars($image['caption']) ?></p>
                                <?php endif; ?>
                                <p class="mb-2"><small class="text-muted">Orden: <?= $image['image_order'] ?></small></p>
                                <p class="mb-0"><small class="text-muted">Subida: <?= date('d/m/Y H:i', strtotime($image['created_at'])) ?></small></p>
                            </div>
                            
                            <div class="btn-group w-100">
                                <button class="btn btn-outline-warning btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editImageModal"
                                        data-id="<?= $image['id'] ?>"
                                        data-caption="<?= htmlspecialchars($image['caption']) ?>"
                                        data-image-order="<?= $image['image_order'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <?php if ($image['image_order'] != 1): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="set_featured_image">
                                    <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-star"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('¿Estás seguro de eliminar esta imagen?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($images)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-image display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No hay imágenes para este capítulo</h4>
                        <p class="text-muted">Sube la primera imagen usando el formulario de arriba</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Imagen -->
    <div class="modal fade" id="editImageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">Editar Imagen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_image">
                        <input type="hidden" name="id" id="editImageId">
                        
                        <div class="mb-3">
                            <label class="form-label">Leyenda</label>
                            <input type="text" class="form-control" name="caption" id="editImageCaption" placeholder="Descripción de la imagen">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="image_order" id="editImageOrder" min="0">
                            <div class="form-text">0 = normal, 1 = imagen destacada</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Imagen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editImageModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                document.getElementById('editImageId').value = button.getAttribute('data-id');
                document.getElementById('editImageCaption').value = button.getAttribute('data-caption');
                document.getElementById('editImageOrder').value = button.getAttribute('data-image-order');
            });
        });
    </script>
</body>
</html>