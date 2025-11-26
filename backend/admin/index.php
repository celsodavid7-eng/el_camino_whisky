<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas completas
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_seasons' => $pdo->query("SELECT COUNT(*) FROM seasons WHERE is_active = 1")->fetchColumn(),
    'total_chapters' => $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_active = 1")->fetchColumn(),
    'total_payments' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn(),
    'pending_comments' => $pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0")->fetchColumn(),
    'total_alliances' => $pdo->query("SELECT COUNT(*) FROM alliances WHERE is_active = 1")->fetchColumn(),
    'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'total_slides' => $pdo->query("SELECT COUNT(*) FROM home_slider WHERE is_active = 1")->fetchColumn(),
    'free_chapters' => $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_free = 1 AND is_active = 1")->fetchColumn(),
    'paid_chapters' => $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_free = 0 AND is_active = 1")->fetchColumn(),
    'published_seasons' => $pdo->query("SELECT COUNT(*) FROM seasons WHERE is_published = 1 AND is_active = 1")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn()
];

// Obtener usuarios recientes
$recent_users = $pdo->query("
    SELECT username, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// Obtener últimos pagos
$recent_payments = $pdo->query("
    SELECT u.username, p.amount, p.status, p.created_at, s.title as season_title
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN seasons s ON p.season_id = s.id
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll();

// Obtener comentarios recientes pendientes
$recent_comments = $pdo->query("
    SELECT c.id, c.content, u.username, ch.title as chapter_title, c.created_at
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN chapters ch ON c.chapter_id = ch.id
    WHERE c.is_approved = 0
    ORDER BY c.created_at DESC 
    LIMIT 5
")->fetchAll();

// Obtener actividad reciente (últimos capítulos creados)
$recent_chapters = $pdo->query("
    SELECT c.title, s.title as season_title, c.created_at, u.username as created_by
    FROM chapters c
    JOIN seasons s ON c.season_id = s.id
    JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - El Camino del Whisky</title>
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
            --border-color: #333;
            --gold-primary: #D4AF37;
            --gold-secondary: #b8941f;
        }
        
        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            font-weight: 400;
            line-height: 1.6;
        }
        
        .sidebar { 
            background: linear-gradient(180deg, #1a1a1a 0%, #0d0d0d 100%); 
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
        .stat-card { 
            background: linear-gradient(145deg, var(--bg-card), #151515); 
            border-radius: 12px; 
            padding: 25px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
            height: 100%;
            color: var(--text-primary);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold-primary);
        }
        .title-font {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            color: var(--gold-primary);
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
        .recent-item {
            border-left: 3px solid var(--gold-primary);
            padding-left: 15px;
            margin-bottom: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 0 8px 8px 0;
            padding: 12px 15px;
            transition: background 0.3s ease;
        }
        .recent-item:hover {
            background: rgba(255,255,255,0.08);
        }
        .section-title {
            border-bottom: 2px solid var(--gold-primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .badge-free { background-color: #28a745; color: white; }
        .badge-paid { background-color: #ffc107; color: #000; font-weight: 500; }
        .badge-pending { background-color: #6c757d; color: white; }
        .revenue-card { 
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary)); 
            color: #000; 
            font-weight: 600;
        }
        .revenue-card h3 {
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        /* Mejoras de contraste y legibilidad */
        .text-light { color: var(--text-primary) !important; font-weight: 500; }
        .text-muted { color: var(--text-muted) !important; }
        .text-warning { color: var(--gold-primary) !important; }
        
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        strong {
            color: var(--text-primary);
            font-weight: 600;
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
        
        /* Mejora de contraste para textos pequeños */
        small {
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        /* Mejora de contraste para badges */
        .badge {
            font-weight: 500;
        }
        
        /* Mejora de contraste para cards de contenido */
        .stat-card p {
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        /* Efectos de sombra para mejor legibilidad */
        .stat-card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .recent-item strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        /* Mejora de contraste para enlaces */
        a {
            color: var(--gold-primary);
            text-decoration: none;
        }
        
        a:hover {
            color: var(--gold-secondary);
        }
        
        /* Mejora para textos en cards de revenue */
        .revenue-card p {
            color: rgba(0,0,0,0.8) !important;
            font-weight: 500;
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
                    <h2 class="title-font">Dashboard</h2>
                    <div class="text-end">
                        <span class="text-secondary d-block fw-bold">Bienvenido, <?= $_SESSION['user_name'] ?? 'Admin' ?></span>
                        <small class="text-warning fw-bold"><?= date('d/m/Y H:i') ?></small>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-5">
                    <!-- Ingresos -->
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-currency-dollar display-4 mb-3"></i>
                            <h3>$<?= number_format($stats['total_revenue'], 2) ?></h3>
                            <p class="mb-0 fw-bold">Ingresos Totales</p>
                        </div>
                    </div>
                    
                    <!-- Usuarios -->
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-people display-4"></i>
                            <h3 class="fw-bold"><?= $stats['total_users'] ?></h3>
                            <p class="mb-0 fw-bold">Usuarios Totales</p>
                        </div>
                    </div>
                    
                    <!-- Temporadas -->
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-collection-play display-4 mb-3"></i>
                            <h3 class="fw-bold"><?= $stats['total_seasons'] ?></h3>
                            <p class="mb-0 fw-bold">Temporadas Activas</p>
                        </div>
                    </div>
                    
                    <!-- Capítulos -->
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-play-btn display-4"></i>
                            <h3 class="fw-bold"><?= $stats['total_chapters'] ?></h3>
                            <p class="mb-0 fw-bold">Capítulos Activos</p>
                        </div>
                    </div>
                    
                    <!-- Segunda fila de estadísticas -->
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-chat-dots display-4"></i>
                            <h3 class="fw-bold"><?= $stats['pending_comments'] ?></h3>
                            <p class="mb-0 fw-bold">Comentarios Pendientes</p>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card revenue-card">
                            <i class="bi bi-handshake display-4"></i>
                            <h3 class="fw-bold"><?= $stats['total_alliances'] ?></h3>
                            <p class="mb-0 fw-bold">Alianzas Activas</p>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center revenue-card">
                            <i class="bi bi-tags display-4"></i>
                            <h3 class="fw-bold"><?= $stats['total_categories'] ?></h3>
                            <p class="mb-0 fw-bold">Categorías</p>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card text-center revenue-card">
                            <i class="bi bi-images display-4"></i>
                            <h3 class="fw-bold"><?= $stats['total_slides'] ?></h3>
                            <p class="mb-0 fw-bold">Slides Activos</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row g-4">
                    <!-- Usuarios Recientes -->
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5 class="text-warning mb-4 section-title">
                                <i class="bi bi-person-plus me-2"></i>Usuarios Recientes
                            </h5>
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="recent-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-light"><?= htmlspecialchars($user['username']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                <span class="badge bg-secondary ms-2 fw-bold"><?= $user['role'] ?></span>
                                            </div>
                                            <small class="text-warning fw-bold"><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="users.php" class="btn btn-sm btn-outline-warning fw-bold">Ver Todos los Usuarios</a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center fw-bold">No hay usuarios registrados</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Capítulos Recientes -->
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5 class="text-warning mb-4 section-title">
                                <i class="bi bi-play-circle me-2"></i>Capítulos Recientes
                            </h5>
                            <?php if (!empty($recent_chapters)): ?>
                                <?php foreach ($recent_chapters as $chapter): ?>
                                    <div class="recent-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-light"><?= htmlspecialchars($chapter['title']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($chapter['season_title']) ?></small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-warning d-block fw-bold"><?= date('d/m/Y', strtotime($chapter['created_at'])) ?></small>
                                                <small class="text-muted">por <?= $chapter['created_by'] ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="chapters.php" class="btn btn-sm btn-outline-warning fw-bold">Ver Todos los Capítulos</a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center fw-bold">No hay capítulos recientes</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comentarios Pendientes -->
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5 class="text-warning mb-4 section-title">
                                <i class="bi bi-chat-dots me-2"></i>Comentarios Pendientes
                            </h5>
                            <?php if (!empty($recent_comments)): ?>
                                <?php foreach ($recent_comments as $comment): ?>
                                    <div class="recent-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong class="text-light"><?= htmlspecialchars($comment['username']) ?></strong>
                                                <span class="badge bg-info ms-2 fw-bold"><?= htmlspecialchars($comment['chapter_title']) ?></span>
                                                <p class="text-muted mb-1 small fw-bold"><?= htmlspecialchars(substr($comment['content'], 0, 100)) ?>...</p>
                                            </div>
                                            <small class="text-warning fw-bold"><?= date('d/m/Y', strtotime($comment['created_at'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="comments.php" class="btn btn-warning fw-bold">
                                        <i class="bi bi-check-circle me-1"></i>Revisar Comentarios (<?= $stats['pending_comments'] ?>)
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-check display-1 text-success mb-3"></i>
                                    <h5 class="text-success fw-bold">¡Todo al día!</h5>
                                    <p class="text-muted fw-bold">No hay comentarios pendientes de moderación</p>
                                    <a href="comments.php" class="btn btn-outline-success fw-bold">Ver Todos los Comentarios</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5 class="text-warning mb-4 section-title">
                                <i class="bi bi-lightning me-2"></i>Acciones Rápidas
                            </h5>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="seasons.php?action=create" class="btn btn-outline-warning w-100 mb-2 fw-bold">
                                        <i class="bi bi-plus-circle me-1"></i>Nueva Temporada
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="chapters.php?action=create" class="btn btn-outline-warning w-100 mb-2 fw-bold">
                                        <i class="bi bi-plus-circle me-1"></i>Nuevo Capítulo
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="alliances.php?action=create" class="btn btn-outline-info w-100 mb-2 fw-bold">
                                        <i class="bi bi-handshake me-1"></i>Nueva Alianza
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="slider.php" class="btn btn-outline-info w-100 mb-2 fw-bold">
                                        <i class="bi bi-images me-1"></i>Gestionar Slider
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="categories.php" class="btn btn-outline-secondary w-100 mb-2 fw-bold">
                                        <i class="bi bi-tags me-1"></i>Categorías
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="settings.php" class="btn btn-outline-secondary w-100 mb-2 fw-bold">
                                        <i class="bi bi-gear me-1"></i>Configuración
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Resumen de Contenido -->
                            <div class="mt-4 pt-3 border-top border-secondary">
                                <h6 class="text-warning mb-3 fw-bold">Resumen de Contenido</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block fw-bold">Capítulos Gratis</small>
                                        <span class="text-success fw-bold fs-5"><?= $stats['free_chapters'] ?></span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block fw-bold">Capítulos Premium</small>
                                        <span class="text-warning fw-bold fs-5"><?= $stats['paid_chapters'] ?></span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block fw-bold">Temporadas Public.</small>
                                        <span class="text-info fw-bold fs-5"><?= $stats['published_seasons'] ?></span>
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
        // Actualizar la hora cada minuto
        function updateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.text-warning small');
            if (timeElement) {
                timeElement.textContent = now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
            }
        }
        setInterval(updateTime, 60000);
        
        // Auto-ocultar mensajes de alerta después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>