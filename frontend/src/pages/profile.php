<?php
// frontend/src/pages/profile.php

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

// Obtener datos del usuario
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
    
    // Obtener estadísticas del usuario
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(up.id) as total_chapters,
            COUNT(DISTINCT up.season_id) as total_seasons,
            MAX(up.completed_at) as last_activity
        FROM user_progress up 
        WHERE up.user_id = ? AND up.is_completed = 1
    ");
    $statsStmt->execute([$user_id]);
    $user_stats = $statsStmt->fetch();
    
    // Obtener progreso del usuario con más detalles
    $progressStmt = $pdo->prepare("
        SELECT up.*, c.title as chapter_title, c.chapter_number, s.title as season_title, s.id as season_id
        FROM user_progress up 
        JOIN chapters c ON up.chapter_id = c.id 
        JOIN seasons s ON up.season_id = s.id 
        WHERE up.user_id = ? AND up.is_completed = 1
        ORDER BY up.completed_at DESC
    ");
    $progressStmt->execute([$user_id]);
    $user_progress = $progressStmt->fetchAll();
    
    // Obtener temporadas con progreso
    $seasonsProgressStmt = $pdo->prepare("
        SELECT 
            s.id,
            s.title,
            s.subtitle,
            COUNT(c.id) as total_chapters,
            COUNT(up.chapter_id) as completed_chapters,
            (COUNT(up.chapter_id) * 100 / COUNT(c.id)) as progress_percentage
        FROM seasons s
        LEFT JOIN chapters c ON s.id = c.season_id AND c.is_published = 1
        LEFT JOIN user_progress up ON c.id = up.chapter_id AND up.user_id = ? AND up.is_completed = 1
        WHERE s.is_published = 1
        GROUP BY s.id
        HAVING completed_chapters > 0
        ORDER BY progress_percentage DESC
    ");
    $seasonsProgressStmt->execute([$user_id]);
    $seasons_progress = $seasonsProgressStmt->fetchAll();
    
    // Obtener pagos del usuario
    $paymentsStmt = $pdo->prepare("
        SELECT p.*, s.title as season_title, s.subtitle as season_subtitle
        FROM payments p 
        LEFT JOIN seasons s ON p.season_id = s.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $paymentsStmt->execute([$user_id]);
    $user_payments = $paymentsStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error loading profile: " . $e->getMessage());
    die('Error al cargar el perfil');
}

// Función helper para acceso seguro
function safeAccess($data, $key, $default = '') {
    return (isset($data[$key]) && !empty($data[$key])) ? $data[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | El Camino del Whisky</title>
    
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
        
        .profile-hero {
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--gold-primary) 100%);
            color: var(--text-primary);
            padding: 120px 0 60px;
        }
        
        .stats-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s ease;
            color: var(--text-primary);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold-primary);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }
        
        .stats-card i {
            color: var(--gold-primary);
            margin-bottom: 15px;
        }
        
        .stats-card h4 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stats-card p {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0;
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .circular-progress {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(var(--gold-primary) 0%, var(--bg-card-light) 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 1s ease;
        }
        
        .circular-progress::before {
            content: '';
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--bg-card);
        }
        
        .progress-value {
            position: relative;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            z-index: 1;
        }
        
        .nav-pills .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .nav-pills .nav-link:hover {
            color: var(--text-primary);
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--gold-primary);
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            color: #000;
            border-color: var(--gold-primary);
            font-weight: 600;
        }
        
        .table-dark {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-dark th {
            background: var(--bg-card-light);
            color: var(--text-primary);
            font-weight: 600;
            border: none;
            padding: 15px 12px;
        }
        
        .table-dark td {
            color: var(--text-secondary);
            border-color: var(--border-color);
            padding: 12px;
            vertical-align: middle;
        }
        
        .table-dark tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }
        
        .season-progress-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .season-progress-card:hover {
            border-color: var(--gold-primary);
            transform: translateY(-2px);
        }
        
        .progress-bar-custom {
            background: var(--bg-card-light);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-bar-custom .progress-bar {
            background: linear-gradient(90deg, var(--gold-primary), var(--gold-secondary));
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }
        
        .recent-activity-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .recent-activity-item:hover {
            border-color: var(--gold-primary);
            transform: translateX(5px);
        }
        
        .avatar-placeholder {
            color: var(--gold-primary);
        }
        
        .form-control {
            background: var(--bg-card-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .form-control:focus {
            background: var(--bg-card-light);
            border-color: var(--gold-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .form-text {
            color: var(--text-muted);
        }
        
        .badge-sm {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero del Perfil -->
    <section class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 title-font fw-bold mb-3">Mi Perfil</h1>
                    <p class="lead fs-4 mb-0" style="color: var(--text-secondary);">
                        Bienvenido de vuelta, <span style="color: var(--gold-primary);"><?= htmlspecialchars($user['username']) ?></span>
                    </p>
                    <?php if ($user_stats['last_activity']): ?>
                        <p class="text-light mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Última actividad: <?= date('d/m/Y H:i', strtotime($user_stats['last_activity'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-center">
                    <div class="avatar-placeholder display-1">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="mt-3">
                        <span class="badge premium-badge fw-bold">
                            <i class="bi bi-star-fill me-1"></i>
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido del Perfil -->
    <section class="py-5" style="background: var(--bg-dark);">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3 mb-4">
                    <div class="whisky-card p-4">
                        <div class="nav flex-column nav-pills" id="profileTabs" role="tablist">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                                <i class="bi bi-speedometer2 me-2"></i>Resumen General
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#progress" type="button">
                                <i class="bi bi-graph-up me-2"></i>Mi Progreso
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#seasons" type="button">
                                <i class="bi bi-collection-play me-2"></i>Mis Temporadas
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#payments" type="button">
                                <i class="bi bi-credit-card me-2"></i>Mis Pagos
                            </button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#settings" type="button">
                                <i class="bi bi-gear me-2"></i>Configuración
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenido Principal -->
                <div class="col-lg-9">
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- Pestaña: Resumen General -->
                        <div class="tab-pane fade show active" id="overview">
                            <div class="whisky-card p-4">
                                <h3 class="title-font h4 mb-4" style="color: var(--text-primary);">Resumen de Actividad</h3>
                                
                                <div class="row g-4 mb-5">
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <i class="bi bi-play-circle display-5"></i>
                                            <h4><?= $user_stats['total_chapters'] ?? 0 ?></h4>
                                            <p>Capítulos Completados</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <i class="bi bi-collection-play display-5"></i>
                                            <h4><?= $user_stats['total_seasons'] ?? 0 ?></h4>
                                            <p>Temporadas Activas</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <i class="bi bi-credit-card display-5"></i>
                                            <h4><?= count($user_payments) ?></h4>
                                            <p>Pagos Realizados</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <i class="bi bi-calendar-check display-5"></i>
                                            <h4><?= date('d/m/Y', strtotime($user['created_at'])) ?></h4>
                                            <p>Miembro desde</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progreso Reciente -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="title-font h5 mb-3" style="color: var(--text-primary);">Actividad Reciente</h5>
                                        <?php if (!empty($user_progress)): ?>
                                            <?php foreach (array_slice($user_progress, 0, 5) as $progress): ?>
                                                <div class="recent-activity-item">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1" style="color: var(--text-primary);">
                                                                <?= htmlspecialchars($progress['chapter_title']) ?>
                                                            </h6>
                                                            <small style="color: var(--text-muted);">
                                                                <?= htmlspecialchars($progress['season_title']) ?> • 
                                                                Capítulo <?= $progress['chapter_number'] ?>
                                                            </small>
                                                        </div>
                                                        <small style="color: var(--text-muted);">
                                                            <?= date('d/m/Y H:i', strtotime($progress['completed_at'])) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-inbox display-4 mb-3"></i>
                                                <p style="color: var(--text-muted);">Aún no has completado ningún capítulo.</p>
                                                <a href="<?= BASE_URL ?>/index.php#temporadas" class="btn btn-gold">Comenzar a Aprender</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h5 class="title-font h5 mb-3" style="color: var(--text-primary);">Progreso General</h5>
                                        <?php if (!empty($seasons_progress)): ?>
                                            <?php foreach ($seasons_progress as $season): ?>
                                                <div class="season-progress-card">
                                                    <h6 style="color: var(--text-primary); margin-bottom: 10px;">
                                                        <?= htmlspecialchars($season['title']) ?>
                                                    </h6>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small style="color: var(--text-muted);">
                                                            <?= $season['completed_chapters'] ?> de <?= $season['total_chapters'] ?> capítulos
                                                        </small>
                                                        <strong style="color: var(--gold-primary);">
                                                            <?= round($season['progress_percentage']) ?>%
                                                        </strong>
                                                    </div>
                                                    <div class="progress-bar-custom">
                                                        <div class="progress-bar" style="width: <?= $season['progress_percentage'] ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-graph-up display-4 mb-3"></i>
                                                <p style="color: var(--text-muted);">Comienza tu primer temporada</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña: Progreso Detallado -->
                        <div class="tab-pane fade" id="progress">
                            <div class="whisky-card p-4">
                                <h3 class="title-font h4 mb-4" style="color: var(--text-primary);">Mi Progreso Detallado</h3>
                                
                                <?php if (!empty($user_progress)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-dark">
                                            <thead>
                                                <tr>
                                                    <th>Capítulo</th>
                                                    <th>Temporada</th>
                                                    <th>Número</th>
                                                    <th>Estado</th>
                                                    <th>Fecha Completado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($user_progress as $progress): ?>
                                                <tr>
                                                    <td style="color: var(--text-primary); font-weight: 500;">
                                                        <?= htmlspecialchars($progress['chapter_title']) ?>
                                                    </td>
                                                    <td style="color: var(--text-secondary);">
                                                        <?= htmlspecialchars($progress['season_title']) ?>
                                                    </td>
                                                    <td style="color: var(--text-muted);">
                                                        #<?= $progress['chapter_number'] ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-lg me-1"></i>Completado
                                                        </span>
                                                    </td>
                                                    <td style="color: var(--text-muted);">
                                                        <?= date('d/m/Y H:i', strtotime($progress['completed_at'])) ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-graph-up display-1 mb-3"></i>
                                        <h5 style="color: var(--text-muted);">Aún no tienes progreso</h5>
                                        <p class="mb-4" style="color: var(--text-muted);">Comienza tu viaje en el mundo del whisky completando algunos capítulos.</p>
                                        <a href="<?= BASE_URL ?>/index.php#temporadas" class="btn btn-gold">
                                            <i class="bi bi-play-circle me-2"></i>Explorar Temporadas
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pestaña: Mis Temporadas -->
                        <div class="tab-pane fade" id="seasons">
                            <div class="whisky-card p-4">
                                <h3 class="title-font h4 mb-4" style="color: var(--text-primary);">Mis Temporadas</h3>
                                
                                <?php if (!empty($seasons_progress)): ?>
                                    <div class="row">
                                        <?php foreach ($seasons_progress as $season): ?>
                                            <div class="col-md-6 mb-4">
                                                <div class="season-progress-card">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <h5 style="color: var(--text-primary); margin-bottom: 0;">
                                                            <?= htmlspecialchars($season['title']) ?>
                                                        </h5>
                                                        <span class="badge bg-warning text-dark">
                                                            <?= round($season['progress_percentage']) ?>%
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if (!empty($season['subtitle'])): ?>
                                                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px;">
                                                            <?= htmlspecialchars($season['subtitle']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <small style="color: var(--text-muted);">Progreso</small>
                                                            <small style="color: var(--gold-primary); font-weight: 600;">
                                                                <?= $season['completed_chapters'] ?>/<?= $season['total_chapters'] ?>
                                                            </small>
                                                        </div>
                                                        <div class="progress-bar-custom">
                                                            <div class="progress-bar" style="width: <?= $season['progress_percentage'] ?>%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $season['id'] ?>" class="btn btn-outline-gold btn-sm flex-fill">
                                                            <i class="bi bi-eye me-1"></i>Ver Temporada
                                                        </a>
                                                        <?php if ($season['progress_percentage'] < 100): ?>
                                                            <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $season['id'] ?>" class="btn btn-gold btn-sm flex-fill">
                                                                <i class="bi bi-play-circle me-1"></i>Continuar
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-success flex-fill text-center py-2">
                                                                <i class="bi bi-check-lg me-1"></i>Completada
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-collection-play display-1 mb-3"></i>
                                        <h5 style="color: var(--text-muted);">Aún no tienes temporadas activas</h5>
                                        <p class="mb-4" style="color: var(--text-muted);">Adquiere tu primera temporada para comenzar tu camino.</p>
                                        <a href="<?= BASE_URL ?>/index.php#temporadas" class="btn btn-gold">
                                            <i class="bi bi-credit-card me-2"></i>Ver Temporadas Disponibles
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pestaña: Pagos -->
                        <div class="tab-pane fade" id="payments">
                            <div class="whisky-card p-4">
                                <h3 class="title-font h4 mb-4" style="color: var(--text-primary);">Historial de Pagos</h3>
                                
                                <?php if (!empty($user_payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-dark">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Monto</th>
                                                    <th>Método</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($user_payments as $payment): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong style="color: var(--text-primary);">
                                                                <?= htmlspecialchars($payment['season_title'] ?? 'Acceso General') ?>
                                                            </strong>
                                                            <?php if (!empty($payment['season_subtitle'])): ?>
                                                                <br><small style="color: var(--text-muted);"><?= htmlspecialchars($payment['season_subtitle']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td style="color: var(--gold-primary); font-weight: 600;">
                                                        $<?= number_format($payment['amount'], 2) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= ucfirst($payment['payment_method']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($payment['status'] === 'completed'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-lg me-1"></i>Completado
                                                            </span>
                                                        <?php elseif ($payment['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="bi bi-clock me-1"></i>Pendiente
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">
                                                                <i class="bi bi-x-circle me-1"></i>Fallido
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="color: var(--text-muted);">
                                                        <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($payment['status'] === 'completed' && $payment['season_id']): ?>
                                                            <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $payment['season_id'] ?>" class="btn btn-outline-gold btn-sm">
                                                                <i class="bi bi-play-circle me-1"></i>Acceder
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-credit-card display-1 mb-3"></i>
                                        <h5 style="color: var(--text-muted);">Aún no has realizado pagos</h5>
                                        <p class="mb-4" style="color: var(--text-muted);">Accede a contenido premium adquiriendo nuestras temporadas.</p>
                                        <a href="<?= BASE_URL ?>/index.php#temporadas" class="btn btn-gold">
                                            <i class="bi bi-credit-card me-2"></i>Ver Temporadas Premium
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pestaña: Configuración -->
                        <div class="tab-pane fade" id="settings">
                            <div class="whisky-card p-4">
                                <h3 class="title-font h4 mb-4" style="color: var(--text-primary);">Configuración de Cuenta</h3>
                                
                                <form id="profileForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nombre de Usuario</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Rol</label>
                                            <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Estado</label>
                                            <input type="text" class="form-control" value="<?= $user['is_active'] ? 'Activo' : 'Inactivo' ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Miembro desde</label>
                                            <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>" readonly>
                                        </div>
                                        <?php if ($user['last_login']): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Último acceso</label>
                                            <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>" readonly>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                                        <button type="button" class="btn btn-outline-gold me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="bi bi-key me-2"></i>Cambiar Contraseña
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" data-logout>
                                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title title-font">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" name="new_password" required>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" name="confirm_new_password" required>
                        </div>
                        <button type="submit" class="btn btn-gold w-100">Cambiar Contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
        user: {
            id: <?= $user['id'] ?>,
            username: '<?= $user['username'] ?>',
            email: '<?= $user['email'] ?>',
            role: '<?= $user['role'] ?>'
        }
    };

    // Cambiar contraseña
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'change_password');
        formData.append('user_id', <?= $user['id'] ?>);
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Cambiando...');
        submitBtn.prop('disabled', true);
        
        fetch('<?= BASE_URL ?>/../../backend/api/auth.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast('¡Éxito!', 'Contraseña cambiada correctamente', 'success');
                $('#changePasswordModal').modal('hide');
                this.reset();
            } else {
                showToast('Error', result.message || 'Error al cambiar contraseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Error de conexión', 'error');
        })
        .finally(() => {
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
    });

    // Función para mostrar notificaciones
    function showToast(title, message, type = 'info') {
        // Usar la función del modal si existe
        if (typeof window.showToast === 'function') {
            window.showToast(title, message, type);
            return;
        }
        
        // Fallback: crear toast básico
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remover el toast después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Animación de barras de progreso
    document.addEventListener('DOMContentLoaded', function() {
        // Animar barras de progreso cuando sean visibles
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
        
        // Animar progreso circular si existe
        const progressElements = document.querySelectorAll('.circular-progress');
        progressElements.forEach(progress => {
            const percentage = parseInt(progress.getAttribute('data-percentage')) || 0;
            const valueElement = progress.querySelector('.progress-value');
            
            if (valueElement) {
                progress.style.background = `conic-gradient(var(--gold-primary) 0%, var(--bg-card-light) 0%)`;
                
                let current = 0;
                const interval = setInterval(() => {
                    if (current >= percentage) {
                        clearInterval(interval);
                    } else {
                        current++;
                        valueElement.textContent = current + '%';
                        progress.style.background = `conic-gradient(var(--gold-primary) ${current}%, var(--bg-card-light) 0%)`;
                    }
                }, 20);
            }
        });
    });
    </script>
</body>
</html>