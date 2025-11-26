<?php
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Verificar si la tabla existe, si no crearla
try {
    $pdo->query("SELECT 1 FROM project_content LIMIT 1");
} catch (Exception $e) {
    // Crear la tabla si no existe
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `project_content` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `section_title` varchar(255) NOT NULL,
            `section_content` text NOT NULL,
            `display_order` int(11) DEFAULT 0,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createTableSQL);
    
    // Insertar datos iniciales
    $initialData = [
        ['Apreciado lector:', 'Así como vos, un día —hace ya algunos años— decidí que quería conocer más sobre este apreciado y, para muchos, misterioso espirituoso. Pensé que el camino sería fácil: después de todo, hoy existen infinidad de canales de información y personas dispuestas a compartir su experiencia en casi cualquier tema. ¡Pero grande fue mi sorpresa cuando descubrí que no era así!', 1],
        ['El Desafío Inicial', 'Pronto me di cuenta de que no existe un camino claro para quienes desean empezar desde cero. La mayoría de los canales hablan de botellas específicas, describen notas de cata y cuentan detalles sobre las destilerías —algo valioso, sin duda—, pero nadie explica qué botellas comprar, dónde hacerlo o cómo leer una etiqueta. Y si encontrás a alguien que más o menos lo hace, suele referirse a un mercado muy distinto al nuestro.', 2],
        ['La Determinación', 'Aun así, decidí seguir adelante. Con las herramientas que tenía, fui aprendiendo paso a paso, botella a botella, hasta formar mi propio criterio. Cada sorbo era una lección, cada aroma un descubrimiento, y cada botella una nueva página en este libro sensorial que estaba escribiendo con mi paladar.', 3],
        ['El Nacimiento del Proyecto', 'Con el tiempo, nació en mí la idea de compartir ese aprendizaje y crear una guía sencilla que acompañe a cualquiera que quiera iniciar este recorrido tan apasionante. No quería que otros tuvieran que pasar por las mismas dificultades que yo enfrenté. Quería allanar el camino, hacerlo accesible y disfrutable desde el primer momento.', 4],
        ['Nuestra Filosofía', 'Creemos que el whisky no es solo una bebida, sino una experiencia cultural, un viaje sensorial que conecta tradiciones, territorios y personas. Cada botella cuenta una historia, cada destilería tiene su alma, y cada cata es una oportunidad para descubrir algo nuevo sobre nosotros mismos.', 5],
        ['El Mensaje Final', 'Ojalá que esta pequeña guía te ayude a dar tus primeros pasos en este hermoso camino. Que cada sorbo te acerque no solo al entendimiento del whisky, sino al placer de descubrir, aprender y compartir. El camino del whisky es, en definitiva, el camino del conocimiento sensorial.', 6]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO project_content (section_title, section_content, display_order) VALUES (?, ?, ?)");
    foreach ($initialData as $data) {
        $stmt->execute($data);
    }
}

// Manejar operaciones CRUD
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_section'])) {
        $section_title = trim($_POST['section_title']);
        $section_content = trim($_POST['section_content']);
        $display_order = intval($_POST['display_order']);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO project_content (section_title, section_content, display_order) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$section_title, $section_content, $display_order]);
            $message = '<div class="alert alert-success">Sección agregada correctamente.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error al agregar la sección: ' . $e->getMessage() . '</div>';
        }
    }
    
    if (isset($_POST['update_section'])) {
        $id = intval($_POST['section_id']);
        $section_title = trim($_POST['section_title']);
        $section_content = trim($_POST['section_content']);
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE project_content 
                SET section_title = ?, section_content = ?, display_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$section_title, $section_content, $display_order, $is_active, $id]);
            $message = '<div class="alert alert-success">Sección actualizada correctamente.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error al actualizar la sección: ' . $e->getMessage() . '</div>';
        }
    }
    
    if (isset($_POST['delete_section'])) {
        $id = intval($_POST['section_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM project_content WHERE id = ?");
            $stmt->execute([$id]);
            $message = '<div class="alert alert-success">Sección eliminada correctamente.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error al eliminar la sección: ' . $e->getMessage() . '</div>';
        }
    }
}

// Obtener todas las secciones
try {
    $stmt = $pdo->prepare("SELECT * FROM project_content ORDER BY display_order ASC, created_at ASC");
    $stmt->execute();
    $sections = $stmt->fetchAll();
} catch (Exception $e) {
    $sections = [];
    $message = '<div class="alert alert-danger">Error al cargar las secciones: ' . $e->getMessage() . '</div>';
}

// Obtener sección para editar
$editSection = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM project_content WHERE id = ?");
        $stmt->execute([$editId]);
        $editSection = $stmt->fetch();
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error al cargar la sección para editar: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Proyecto - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        .sidebar .nav-link:hover { 
            background: var(--gold-primary); 
            color: #000; 
            transform: translateX(5px);
        }
        .sidebar .nav-link.active { 
            background: var(--gold-primary); 
            color: #000; 
            font-weight: 600;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: var(--bg-card-light);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 600;
        }
        .form-control, .form-select, .form-check-input {
            background: var(--bg-card-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 400;
        }
        .form-control:focus, .form-select:focus {
            background: var(--bg-card-light);
            border-color: var(--gold-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        .form-label, .form-check-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        .form-text {
            color: var(--text-muted);
            font-weight: 400;
        }
        .section-card {
            border-left: 4px solid var(--gold-primary);
            transition: all 0.3s ease;
            background: var(--bg-card-light);
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            border-left-color: var(--gold-secondary);
        }
        .inactive-section {
            opacity: 0.7;
            border-left-color: #6c757d;
        }
        .list-group-item {
            background: var(--bg-card-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .modal-content {
            background: var(--bg-card);
            color: var(--text-primary);
        }
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }
        .btn-close-white {
            filter: invert(1);
        }
        
        /* Mejoras de contraste y legibilidad */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }
        
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
        
        .text-muted.small {
            color: var(--text-secondary) !important;
            font-weight: 400;
        }
        
        /* Botones mejorados */
        .btn-warning {
            background-color: var(--gold-primary);
            border-color: var(--gold-primary);
            color: #000;
            font-weight: 600;
        }
        
        .btn-warning:hover {
            background-color: var(--gold-secondary);
            border-color: var(--gold-secondary);
            color: #000;
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
        
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
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
        
        /* Badges mejorados */
        .badge {
            font-weight: 500;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
        }
        
        /* Alertas mejoradas */
        .alert {
            font-weight: 500;
            border: none;
        }
        
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Mejora de textos en cards */
        .card-title {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .section-card h6 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .section-card p {
            color: var(--text-secondary);
            font-weight: 400;
            line-height: 1.5;
        }
        
        .section-card small {
            color: var(--text-muted);
            font-weight: 400;
        }
        
        /* Mejora del border-bottom del header */
        .border-bottom {
            border-bottom-color: var(--border-color) !important;
        }
        
        /* Mejora de los iconos */
        .bi {
            opacity: 0.9;
        }
        
        /* Mejora del placeholder */
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }
        
        /* Mejora del estado focus para checkboxes */
        .form-check-input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        
        .form-check-input:checked {
            background-color: var(--gold-primary);
            border-color: var(--gold-primary);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-warning fw-bold">Gestionar Contenido del Proyecto</h1>
                </div>

                <?php echo $message; ?>

                <div class="row">
                    <!-- Formulario para agregar/editar -->
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-warning fw-bold">
                                    <i class="bi bi-<?php echo $editSection ? 'pencil' : 'plus'; ?>-circle me-2"></i>
                                    <?php echo $editSection ? 'Editar Sección' : 'Agregar Nueva Sección'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($editSection): ?>
                                        <input type="hidden" name="section_id" value="<?php echo $editSection['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="section_title" class="form-label fw-bold">Título de la Sección</label>
                                        <input type="text" class="form-control fw-medium" id="section_title" name="section_title" 
                                               value="<?php echo $editSection ? htmlspecialchars($editSection['section_title']) : ''; ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="section_content" class="form-label fw-bold">Contenido</label>
                                        <textarea class="form-control fw-medium" id="section_content" name="section_content" 
                                                  rows="6" required><?php echo $editSection ? htmlspecialchars($editSection['section_content']) : ''; ?></textarea>
                                        <div class="form-text fw-medium">Puedes usar saltos de línea para formatear el texto.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="display_order" class="form-label fw-bold">Orden de Visualización</label>
                                        <input type="number" class="form-control fw-medium" id="display_order" name="display_order" 
                                               value="<?php echo $editSection ? $editSection['display_order'] : '0'; ?>" 
                                               min="0" required>
                                        <div class="form-text fw-medium">Número menor = aparece primero</div>
                                    </div>
                                    
                                    <?php if ($editSection): ?>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                                   <?php echo $editSection['is_active'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-medium" for="is_active">Sección Activa</label>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update_section" class="btn btn-warning flex-fill fw-bold">
                                                <i class="bi bi-check-circle me-2"></i>Actualizar Sección
                                            </button>
                                            <a href="project.php" class="btn btn-secondary fw-bold">Cancelar</a>
                                        </div>
                                    <?php else: ?>
                                        <button type="submit" name="add_section" class="btn btn-primary w-100 fw-bold">
                                            <i class="bi bi-plus-circle me-2"></i>Agregar Sección
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de secciones existentes -->
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-warning fw-bold">
                                    <i class="bi bi-list-ul me-2"></i>
                                    Secciones del Proyecto (<?php echo count($sections); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($sections)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox display-1 text-muted"></i>
                                        <p class="mt-3 text-muted fw-medium">No hay secciones creadas aún.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($sections as $section): ?>
                                            <div class="list-group-item section-card <?php echo !$section['is_active'] ? 'inactive-section' : ''; ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 text-light fw-bold">
                                                            <?php echo htmlspecialchars($section['section_title']); ?>
                                                            <?php if (!$section['is_active']): ?>
                                                                <span class="badge bg-secondary ms-2 fw-bold">Inactiva</span>
                                                            <?php endif; ?>
                                                            <small class="text-muted fw-medium">(Orden: <?php echo $section['display_order']; ?>)</small>
                                                        </h6>
                                                        <p class="mb-1 text-muted small fw-medium">
                                                            <?php echo strlen($section['section_content']) > 150 ? 
                                                                substr($section['section_content'], 0, 150) . '...' : 
                                                                $section['section_content']; ?>
                                                        </p>
                                                        <small class="text-muted fw-medium">
                                                            Creado: <?php echo date('d/m/Y H:i', strtotime($section['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?edit=<?php echo $section['id']; ?>" class="btn btn-outline-warning fw-medium">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar esta sección?');">
                                                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                                            <button type="submit" name="delete_section" class="btn btn-outline-danger fw-medium">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>