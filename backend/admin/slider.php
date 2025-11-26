<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Directorio de uploads
$uploadDir = '../../uploads/slider/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_slider_item':
                $chapterId = !empty($_POST['chapter_id']) ? $_POST['chapter_id'] : null;
                $title = $_POST['title'] ?? '';
                $subtitle = $_POST['subtitle'] ?? '';
                $buttonText = $_POST['button_text'] ?? 'Ver Más';
                $displayOrder = $_POST['display_order'] ?? 0;
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $imageCaption = $_POST['image_caption'] ?? '';
                
                // Procesar imagen
                $imagePath = null;
                if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] === UPLOAD_ERR_OK) {
                    $imageFile = $_FILES['slider_image'];
                    $fileExtension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $fileName = 'slider_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
                            $imagePath = 'uploads/slider/' . $fileName;
                        } else {
                            throw new Exception('Error al subir la imagen');
                        }
                    } else {
                        throw new Exception('Formato de imagen no permitido');
                    }
                } else {
                    throw new Exception('Debe seleccionar una imagen');
                }
                
                $stmt = $pdo->prepare("INSERT INTO home_slider (chapter_id, title, subtitle, button_text, display_order, is_active, image_path, image_caption) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$chapterId, $title, $subtitle, $buttonText, $displayOrder, $isActive, $imagePath, $imageCaption])) {
                    $message = '<div class="alert alert-success fw-bold">Item del slider creado exitosamente</div>';
                } else {
                    $message = '<div class="alert alert-danger fw-bold">Error al crear item del slider</div>';
                }
                break;
                
            case 'update_slider_item':
                $id = $_POST['id'];
                $chapterId = !empty($_POST['chapter_id']) ? $_POST['chapter_id'] : null;
                $title = $_POST['title'] ?? '';
                $subtitle = $_POST['subtitle'] ?? '';
                $buttonText = $_POST['button_text'] ?? 'Ver Más';
                $displayOrder = $_POST['display_order'] ?? 0;
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $imageCaption = $_POST['image_caption'] ?? '';
                $keepImage = isset($_POST['keep_image']);
                
                // Obtener imagen actual
                $currentImage = $pdo->query("SELECT image_path FROM home_slider WHERE id = $id")->fetchColumn();
                
                // Procesar nueva imagen
                $imagePath = $keepImage ? $currentImage : null;
                if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] === UPLOAD_ERR_OK) {
                    $imageFile = $_FILES['slider_image'];
                    $fileExtension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $fileName = 'slider_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
                            // Eliminar imagen anterior si existe
                            if ($currentImage && file_exists('../../' . $currentImage)) {
                                unlink('../../' . $currentImage);
                            }
                            $imagePath = 'uploads/slider/' . $fileName;
                        } else {
                            throw new Exception('Error al subir la imagen');
                        }
                    } else {
                        throw new Exception('Formato de imagen no permitido');
                    }
                } else if (!$keepImage) {
                    // Si no se mantiene la imagen y no se sube nueva, usar la actual
                    $imagePath = $currentImage;
                }
                
                $stmt = $pdo->prepare("UPDATE home_slider SET chapter_id = ?, title = ?, subtitle = ?, button_text = ?, display_order = ?, is_active = ?, image_path = ?, image_caption = ? WHERE id = ?");
                if ($stmt->execute([$chapterId, $title, $subtitle, $buttonText, $displayOrder, $isActive, $imagePath, $imageCaption, $id])) {
                    $message = '<div class="alert alert-success fw-bold">Item del slider actualizado exitosamente</div>';
                } else {
                    $message = '<div class="alert alert-danger fw-bold">Error al actualizar item del slider</div>';
                }
                break;
                
            case 'delete_slider_item':
                $id = $_POST['id'];
                
                // Obtener imagen para eliminar
                $imagePath = $pdo->query("SELECT image_path FROM home_slider WHERE id = $id")->fetchColumn();
                if ($imagePath && file_exists('../../' . $imagePath)) {
                    unlink('../../' . $imagePath);
                }
                
                $stmt = $pdo->prepare("DELETE FROM home_slider WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = '<div class="alert alert-success fw-bold">Item del slider eliminado exitosamente</div>';
                } else {
                    $message = '<div class="alert alert-danger fw-bold">Error al eliminar item del slider</div>';
                }
                break;
                
            case 'update_slider_order':
                $orders = $_POST['order'] ?? [];
                foreach ($orders as $id => $order) {
                    $stmt = $pdo->prepare("UPDATE home_slider SET display_order = ? WHERE id = ?");
                    $stmt->execute([$order, $id]);
                }
                $message = '<div class="alert alert-success fw-bold">Orden del slider actualizado</div>';
                break;
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger fw-bold">' . $e->getMessage() . '</div>';
    }
}

// Obtener items del slider
$stmt = $pdo->prepare("
    SELECT hs.*, 
           c.title as chapter_title, 
           c.subtitle as chapter_subtitle, 
           s.title as season_title
    FROM home_slider hs
    LEFT JOIN chapters c ON hs.chapter_id = c.id
    LEFT JOIN seasons s ON c.season_id = s.id
    ORDER BY hs.display_order ASC, hs.created_at DESC
");
$stmt->execute();
$sliderItems = $stmt->fetchAll();

// Obtener capítulos para el select
$chapters = $pdo->query("
    SELECT c.id, c.title, s.title as season_title 
    FROM chapters c 
    JOIN seasons s ON c.season_id = s.id 
    WHERE c.is_published = 1 
    ORDER BY s.title, c.title
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión del Slider - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        :root {
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --text-muted: #b0b0b0;
            --bg-dark: #0a0a0a;
            --bg-card: #1a1a1a;
            --bg-card-light: #2a2a2a;
            --border-color: #444;
            --gold-primary: #D4AF37;
            --gold-secondary: #b8941f;
        }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 400;
            line-height: 1.6;
        }
        
        .sidebar { 
            background: #1a1a1a; 
            min-height: 100vh; 
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar .nav-link { 
            color: var(--text-secondary); 
            padding: 12px 20px; 
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active { 
            background: var(--gold-primary); 
            color: #000; 
            transform: translateX(5px);
            font-weight: 600;
        }
        
        .title-font {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            color: var(--gold-primary);
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
        
        /* Tabla mejorada */
        .table-dark { 
            background: var(--bg-card); 
            color: var(--text-primary);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-dark th {
            background: var(--bg-card-light);
            color: var(--text-secondary);
            font-weight: 600;
            border-color: var(--border-color);
            padding: 15px 12px;
        }
        
        .table-dark td {
            border-color: var(--border-color);
            padding: 12px;
            vertical-align: middle;
            font-weight: 400;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.1);
        }
        
        /* Badges mejorados */
        .badge-active { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white;
            font-weight: 600;
        }
        
        .badge-inactive { 
            background: linear-gradient(135deg, #6c757d, #5a6268); 
            color: white;
            font-weight: 600;
        }
        
        /* Elementos de ordenamiento */
        .sortable-handle { 
            cursor: move; 
            color: var(--gold-primary);
            font-size: 1.2rem;
        }
        
        .sortable-ghost { 
            opacity: 0.6; 
            background: rgba(212, 175, 55, 0.2);
        }
        
        /* Imágenes */
        .slider-image { 
            max-width: 150px; 
            max-height: 80px; 
            object-fit: cover; 
            border-radius: 6px;
            border: 2px solid var(--border-color);
        }
        
        .image-preview { 
            max-width: 200px; 
            max-height: 120px; 
            object-fit: cover; 
            border-radius: 8px;
            border: 2px solid var(--gold-primary);
        }
        
        /* Botones mejorados */
        .btn-group-sm > .btn { 
            padding: 0.4rem 0.6rem; 
            font-weight: 500;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            border: none;
            color: #000;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(212, 175, 55, 0.3);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, var(--gold-secondary), var(--gold-primary));
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(212, 175, 55, 0.4);
        }
        
        .btn-outline-warning {
            color: var(--gold-primary);
            border-color: var(--gold-primary);
            font-weight: 500;
        }
        
        .btn-outline-warning:hover {
            background-color: var(--gold-primary);
            color: #000;
            font-weight: 600;
        }
        
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
            font-weight: 500;
        }
        
        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            font-weight: 600;
        }
        
        /* Formularios mejorados */
        .form-control, .form-select {
            background: var(--bg-card-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 6px;
        }
        
        .form-control:focus, .form-select:focus {
            background: var(--bg-card-light);
            border-color: var(--gold-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.3rem rgba(212, 175, 55, 0.2);
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-text {
            color: var(--text-muted);
            font-weight: 400;
            font-size: 0.875rem;
        }
        
        .form-check-input:checked {
            background-color: var(--gold-primary);
            border-color: var(--gold-primary);
        }
        
        .form-check-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Card mejorada */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Modales mejorados */
        .modal-content {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        
        .modal-header {
            background: var(--bg-card-light);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 25px;
        }
        
        .modal-title {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            background: var(--bg-card-light);
            border-top: 1px solid var(--border-color);
            padding: 20px 25px;
        }
        
        .btn-close-white {
            filter: invert(1);
            opacity: 0.8;
        }
        
        /* Alertas mejoradas */
        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Textos mejorados */
        .text-warning {
            color: var(--gold-primary) !important;
            font-weight: 600;
        }
        
        .text-light {
            color: var(--text-primary) !important;
            font-weight: 600;
        }
        
        .text-muted {
            color: var(--text-muted) !important;
            font-weight: 400;
        }
        
        .text-info {
            color: #17a2b8 !important;
            font-weight: 500;
        }
        
        strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        small {
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        /* Mejoras responsive */
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 8px;
            }
            
            .btn-group-sm > .btn {
                padding: 0.3rem 0.5rem;
            }
            
            .slider-image {
                max-width: 120px;
                max-height: 60px;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .table tbody tr {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Input de orden mejorado */
        .form-control-sm {
            background: var(--bg-card-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 500;
            width: 70px !important;
        }
        
        /* Preview de imagen actual */
        #currentImageContainer {
            padding: 10px;
            background: var(--bg-card-light);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        #noImageMessage {
            font-weight: 500;
            color: var(--text-muted);
        }
    </style>
</head>
<body class="bg-dark">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="title-font"><i class="bi bi-images me-2"></i>Gestión del Slider Home</h2>
                    <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#createSliderModal">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Item
                    </button>
                </div>

                <?= $message ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="orderForm">
                            <input type="hidden" name="action" value="update_slider_order">
                            
                            <div class="table-responsive">
                                <table class="table table-dark table-hover" id="sliderTable">
                                    <thead>
                                        <tr>
                                            <th width="50" class="fw-bold">Orden</th>
                                            <th width="120" class="fw-bold">Imagen</th>
                                            <th class="fw-bold">Contenido</th>
                                            <th class="fw-bold">Capítulo Relacionado</th>
                                            <th class="fw-bold">Estado</th>
                                            <th width="120" class="fw-bold">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sliderItems as $item): ?>
                                        <tr data-id="<?= $item['id'] ?>">
                                            <td>
                                                <i class="bi bi-grip-vertical sortable-handle text-warning"></i>
                                                <input type="number" class="form-control form-control-sm d-inline-block fw-medium" 
                                                       name="order[<?= $item['id'] ?>]" value="<?= $item['display_order'] ?>">
                                            </td>
                                            <td>
                                                <?php if ($item['image_path']): ?>
                                                    <img src="../../<?= htmlspecialchars($item['image_path']) ?>" 
                                                         alt="Slider" class="slider-image" 
                                                         onerror="this.style.display='none'">
                                                <?php else: ?>
                                                    <span class="text-muted small fw-medium">Sin imagen</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="mb-1">
                                                    <?php if ($item['title']): ?>
                                                        <strong class="fw-bold"><?= htmlspecialchars($item['title']) ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted fw-medium">Sin título personalizado</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mb-1">
                                                    <?php if ($item['subtitle']): ?>
                                                        <small class="text-muted fw-medium"><?= htmlspecialchars($item['subtitle']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted fw-medium">Sin subtítulo</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <small class="text-info fw-medium">Botón: <?= htmlspecialchars($item['button_text']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($item['chapter_id']): ?>
                                                    <strong class="fw-bold"><?= htmlspecialchars($item['chapter_title'] ?? 'N/A') ?></strong>
                                                    <br><small class="text-muted fw-medium"><?= htmlspecialchars($item['season_title'] ?? '') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted fw-medium">Sin capítulo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['is_active']): ?>
                                                    <span class="badge badge-active">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-warning btn-sm edit-btn fw-medium" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editSliderModal"
                                                            data-id="<?= $item['id'] ?>"
                                                            data-chapter-id="<?= $item['chapter_id'] ?>"
                                                            data-title="<?= htmlspecialchars($item['title']) ?>"
                                                            data-subtitle="<?= htmlspecialchars($item['subtitle']) ?>"
                                                            data-button-text="<?= htmlspecialchars($item['button_text']) ?>"
                                                            data-display-order="<?= $item['display_order'] ?>"
                                                            data-is-active="<?= $item['is_active'] ?>"
                                                            data-image-path="<?= htmlspecialchars($item['image_path']) ?>"
                                                            data-image-caption="<?= htmlspecialchars($item['image_caption']) ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este item del slider?')">
                                                        <input type="hidden" name="action" value="delete_slider_item">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm fw-medium">
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
                            
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-warning fw-bold">
                                    <i class="bi bi-save me-2"></i>Guardar Orden
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Slider Item -->
    <div class="modal fade" id="createSliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Crear Nuevo Item del Slider</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_slider_item">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Imagen del Slider *</label>
                                    <input type="file" class="form-control fw-medium" name="slider_image" accept="image/*" required>
                                    <div class="form-text fw-medium">Formatos: JPG, PNG, GIF, WEBP</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Leyenda de la Imagen (opcional)</label>
                                    <input type="text" class="form-control fw-medium" name="image_caption" placeholder="Texto descriptivo de la imagen">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Capítulo Relacionado (opcional)</label>
                                    <select class="form-control fw-medium" name="chapter_id">
                                        <option value="">Sin capítulo específico</option>
                                        <?php foreach ($chapters as $chapter): ?>
                                            <option value="<?= $chapter['id'] ?>">
                                                <?= htmlspecialchars($chapter['season_title']) ?> - <?= htmlspecialchars($chapter['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Título (opcional)</label>
                                    <input type="text" class="form-control fw-medium" name="title" placeholder="Título que aparecerá en el slider">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Subtítulo (opcional)</label>
                                    <textarea class="form-control fw-medium" name="subtitle" rows="2" placeholder="Texto descriptivo o subtítulo"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Texto del Botón</label>
                                    <input type="text" class="form-control fw-medium" name="button_text" value="Ver Más">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Orden de Visualización</label>
                                            <input type="number" class="form-control fw-medium" name="display_order" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Estado</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="createActive" checked>
                                                <label class="form-check-label fw-medium" for="createActive">Activo</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-medium" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold">Crear Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Slider Item -->
    <div class="modal fade" id="editSliderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar Item del Slider</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_slider_item">
                        <input type="hidden" name="id" id="editId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Imagen Actual</label>
                                    <div id="currentImageContainer" class="mb-2">
                                        <img id="currentImagePreview" class="image-preview" src="" alt="Imagen actual" style="display: none;">
                                        <div id="noImageMessage" class="text-muted fw-medium">No hay imagen actual</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="keep_image" id="keepImage" checked>
                                        <label class="form-check-label fw-medium" for="keepImage">Mantener imagen actual</label>
                                    </div>
                                    
                                    <input type="file" class="form-control fw-medium" name="slider_image" accept="image/*" id="editSliderImage" disabled>
                                    <div class="form-text fw-medium">Desmarcar para cambiar imagen</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Leyenda de la Imagen (opcional)</label>
                                    <input type="text" class="form-control fw-medium" name="image_caption" id="editImageCaption">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Capítulo Relacionado (opcional)</label>
                                    <select class="form-control fw-medium" name="chapter_id" id="editChapterId">
                                        <option value="">Sin capítulo específico</option>
                                        <?php foreach ($chapters as $chapter): ?>
                                            <option value="<?= $chapter['id'] ?>">
                                                <?= htmlspecialchars($chapter['season_title']) ?> - <?= htmlspecialchars($chapter['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Título (opcional)</label>
                                    <input type="text" class="form-control fw-medium" name="title" id="editTitle" placeholder="Título que aparecerá en el slider">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Subtítulo (opcional)</label>
                                    <textarea class="form-control fw-medium" name="subtitle" id="editSubtitle" rows="2" placeholder="Texto descriptivo o subtítulo"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Texto del Botón</label>
                                    <input type="text" class="form-control fw-medium" name="button_text" id="editButtonText" value="Ver Más">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Orden de Visualización</label>
                                            <input type="number" class="form-control fw-medium" name="display_order" id="editDisplayOrder" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Estado</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="editActive">
                                                <label class="form-check-label fw-medium" for="editActive">Activo</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fw-medium" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold">Actualizar Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar Sortable
            const tbody = document.querySelector('#sliderTable tbody');
            if (tbody) {
                new Sortable(tbody, {
                    handle: '.sortable-handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        const rows = tbody.querySelectorAll('tr');
                        rows.forEach((row, index) => {
                            const input = row.querySelector('input[name^="order"]');
                            if (input) {
                                input.value = index + 1;
                            }
                        });
                    }
                });
            }

            // Modal de edición
            const editModal = document.getElementById('editSliderModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    document.getElementById('editId').value = button.getAttribute('data-id');
                    document.getElementById('editChapterId').value = button.getAttribute('data-chapter-id') || '';
                    document.getElementById('editTitle').value = button.getAttribute('data-title') || '';
                    document.getElementById('editSubtitle').value = button.getAttribute('data-subtitle') || '';
                    document.getElementById('editButtonText').value = button.getAttribute('data-button-text') || 'Ver Más';
                    document.getElementById('editDisplayOrder').value = button.getAttribute('data-display-order') || 0;
                    document.getElementById('editActive').checked = button.getAttribute('data-is-active') === '1';
                    document.getElementById('editImageCaption').value = button.getAttribute('data-image-caption') || '';
                    
                    // Manejar imagen actual
                    const imagePath = button.getAttribute('data-image-path');
                    const currentImagePreview = document.getElementById('currentImagePreview');
                    const noImageMessage = document.getElementById('noImageMessage');
                    
                    if (imagePath) {
                        currentImagePreview.src = '../../' + imagePath;
                        currentImagePreview.style.display = 'block';
                        noImageMessage.style.display = 'none';
                    } else {
                        currentImagePreview.style.display = 'none';
                        noImageMessage.style.display = 'block';
                    }
                });
            }

            // Toggle campo de imagen al editar
            const keepImageCheckbox = document.getElementById('keepImage');
            if (keepImageCheckbox) {
                keepImageCheckbox.addEventListener('change', function() {
                    const imageInput = document.getElementById('editSliderImage');
                    if (imageInput) {
                        imageInput.disabled = this.checked;
                        if (!this.checked) {
                            imageInput.required = true;
                        } else {
                            imageInput.required = false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>