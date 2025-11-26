<?php
// frontend/src/pages/my_courses.php

// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ecdw/frontend/public');
}

// Incluir conexión a BD
require_once ROOT_DIR . '/backend/config/database.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener temporadas y progreso del usuario
try {
    // Obtener temporadas gratuitas O temporadas de pago que el usuario ya pagó
    $seasonsStmt = $pdo->prepare("
        SELECT s.* 
        FROM seasons s 
        WHERE s.is_published = 1 
        AND (s.requires_payment = 0 OR s.id IN (
            SELECT p.season_id 
            FROM payments p 
            WHERE p.user_id = ? AND p.status = 'completed'
        ))
        ORDER BY s.display_order ASC
    ");
    $seasonsStmt->execute([$user_id]);
    $seasons = $seasonsStmt->fetchAll();
    
    // Obtener progreso del usuario por temporada
    $progressStmt = $pdo->prepare("
        SELECT up.season_id, 
               COUNT(up.chapter_id) as completed_chapters,
               (SELECT COUNT(*) FROM chapters c WHERE c.season_id = up.season_id AND c.is_published = 1) as total_chapters
        FROM user_progress up 
        WHERE up.user_id = ? AND up.is_completed = 1
        GROUP BY up.season_id
    ");
    $progressStmt->execute([$user_id]);
    $user_progress = $progressStmt->fetchAll();
    
    // Crear array de progreso por temporada
    $progress_by_season = [];
    foreach ($user_progress as $progress) {
        $progress_by_season[$progress['season_id']] = [
            'completed' => $progress['completed_chapters'],
            'total' => $progress['total_chapters'],
            'percentage' => round(($progress['completed_chapters'] / $progress['total_chapters']) * 100)
        ];
    }
    
    // Obtener pagos del usuario (para lógica de acceso)
    $paymentsStmt = $pdo->prepare("SELECT season_id FROM payments WHERE user_id = ? AND status = 'completed'");
    $paymentsStmt->execute([$user_id]);
    $paid_seasons = array_column($paymentsStmt->fetchAll(), 'season_id');
    
} catch (Exception $e) {
    error_log("Error loading courses: " . $e->getMessage());
    die('Error al cargar mi camino');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Camino | El Camino del Whisky</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../../uploads/favicon.png">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/../src/styles/main.css">
    
    <style>
        .courses-hero {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 120px 0 60px;
        }
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero de Cursos -->
    <section class="courses-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 title-font fw-bold mb-3">Mi Camino</h1>
                    <p class="lead fs-4">Continúa tu viaje en el mundo del whisky</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="courses-icon display-1 text-warning">
                        <i class="bi bi-journals"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lista de Cursos -->
    <section class="py-5" style="background: var(--dark);">
        <div class="container">
            <div class="row g-4">
                <?php if (!empty($seasons)): ?>
                    <?php foreach ($seasons as $season): ?>
                        <?php
                        $season_id = $season['id'];
                        $has_access = true; // Ya que solo mostramos temporadas a las que tiene acceso
                        $progress = $progress_by_season[$season_id] ?? ['completed' => 0, 'total' => 0, 'percentage' => 0];
                        $percentage = $progress['percentage'];
                        ?>
                        
                        <div class="col-lg-6">
                            <div class="whisky-card course-card h-100 p-4 p-md-5">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <h3 class="title-font h2 text-warning"><?= htmlspecialchars($season['title']) ?></h3>
                                    <?php if ($season['requires_payment']): ?>
                                        <span class="badge premium-badge">
                                            <i class="bi bi-star-fill me-1"></i>Premium
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($season['subtitle'])): ?>
                                    <p class="mb-4 fs-5"><?= htmlspecialchars($season['subtitle']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Barra de progreso -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-light">Progreso</span>
                                        <span class="text-warning fw-bold"><?= $percentage ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?= $percentage ?>%;" 
                                             aria-valuenow="<?= $percentage ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $progress['completed'] ?> de <?= $progress['total'] ?> capítulos completados
                                    </small>
                                </div>
                                
                                <!-- Estado de acceso -->
                                <div class="mb-4">
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <span>Tienes acceso completo a esta temporada</span>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="d-flex flex-column flex-md-row gap-3">
                                    <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $season_id ?>" 
                                       class="btn btn-gold flex-fill">
                                        <i class="bi bi-play-circle me-2"></i>
                                        <?= $progress['completed'] > 0 ? 'Continuar' : 'Comenzar' ?>
                                    </a>
                                    
                                    <?php if ($progress['completed'] > 0): ?>
                                        <a href="<?= BASE_URL ?>/../src/pages/profile.php#progress" 
                                           class="btn btn-outline-gold flex-fill">
                                            <i class="bi bi-graph-up me-2"></i>Ver Progreso
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="whisky-card p-5">
                            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                            <h3 class="title-font h2 mb-3">Aún no tienes Mi Camino</h3>
                            <p class="fs-5 mb-4">Explora nuestras temporadas y comienza tu viaje en el mundo del whisky.</p>
                            <a href="<?= BASE_URL ?>/index.php#temporadas" class="btn btn-gold btn-lg">
                                <i class="bi bi-compass me-2"></i>Explorar Temporadas
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Incluir Modals -->
    <?php include ROOT_DIR . '/frontend/includes/modals.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS Personalizado -->
    <script src="<?= BASE_URL ?>/../src/js/main.js"></script>
    
    <script>
    // Configuración global
    const SITE_CONFIG = {
        whatsapp: {
            number: '<?= $whatsappConfig['number'] ?>',
            defaultMessage: '<?= $whatsappConfig['default_message'] ?>'
        },
        user: {
            id: <?= $user_id ?>
        }
    };
    </script>
</body>
</html>