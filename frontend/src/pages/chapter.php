<?php
// frontend/src/pages/chapter.php

// Configuración directa de rutas
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

// Verificar si se proporcionó un ID de capítulo
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$chapter_id = intval($_GET['id']);
$chapterData = [];
$seasonData = [];
$user_has_access = false;

try {
    // Obtener datos del capítulo y su temporada
    $stmt = $pdo->prepare("
        SELECT c.*, s.title as season_title, s.requires_payment, s.id as season_id
        FROM chapters c 
        JOIN seasons s ON c.season_id = s.id 
        WHERE c.id = ? AND c.is_published = 1 AND s.is_published = 1
    ");
    $stmt->execute([$chapter_id]);
    $chapterData = $stmt->fetch();
    
    if (!$chapterData) {
        die('Capítulo no encontrado');
    }
    
    // CORRECCIÓN IMPORTANTE: Lógica de acceso corregida
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Para capítulos gratuitos O temporadas gratuitas
        if ($chapterData['is_free'] == 1 || $chapterData['requires_payment'] == 0) {
            $user_has_access = true;
        } else {
            // Para capítulos premium en temporadas premium, verificar pago
            $paymentStmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND season_id = ? AND status = 'completed'");
            $paymentStmt->execute([$user_id, $chapterData['season_id']]);
            $user_has_access = (bool)$paymentStmt->fetch();
        }
    }
    
    // Si no tiene acceso, redirigir
    if (!$user_has_access) {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . '/index.php?login_required=1');
        } else {
            header('Location: ' . BASE_URL . '/../src/pages/season_detail.php?id=' . $chapterData['season_id'] . '&access_denied=1');
        }
        exit();
    }
    
    // Obtener imágenes del capítulo
    $imagesStmt = $pdo->prepare("SELECT * FROM chapter_images WHERE chapter_id = ? ORDER BY image_order DESC, created_at DESC");
    $imagesStmt->execute([$chapter_id]);
    $chapterImages = $imagesStmt->fetchAll();
    
    // Separar imágenes por orden
    $featuredImages = array_filter($chapterImages, function($image) {
        return $image['image_order'] == 1;
    });
    
    $normalImages = array_filter($chapterImages, function($image) {
        return $image['image_order'] == 0;
    });
    
    // Obtener comentarios aprobados
    $commentsStmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.chapter_id = ? AND c.is_approved = 1 
        ORDER BY c.created_at DESC
    ");
    $commentsStmt->execute([$chapter_id]);
    $comments = $commentsStmt->fetchAll();
    
    // Marcar como completado si es un usuario logueado
    if (isset($_SESSION['user_id']) && $user_has_access) {
        // Verificar si ya está marcado como completado
        $progressStmt = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND chapter_id = ?");
        $progressStmt->execute([$user_id, $chapter_id]);
        
        if (!$progressStmt->fetch()) {
            // Marcar como completado
            $insertStmt = $pdo->prepare("
                INSERT INTO user_progress (user_id, chapter_id, season_id, is_completed, completed_at, progress_percentage) 
                VALUES (?, ?, ?, 1, NOW(), 100)
            ");
            $insertStmt->execute([$user_id, $chapter_id, $chapterData['season_id']]);
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading chapter: " . $e->getMessage());
    die('Error al cargar el capítulo');
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
    <title><?= htmlspecialchars(safeAccess($chapterData, 'title', 'Capítulo')) ?> | El Camino del Whisky</title>
    
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
        .chapter-hero {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 120px 0 60px;
        }
        .chapter-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }
        .chapter-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }
        .image-slider {
            position: relative;
            margin: 30px 0;
        }
        .slider-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
            display: none;
        }
        .slider-image.active {
            display: block;
        }
        .slider-nav {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            gap: 10px;
        }
        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #666;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .slider-dot.active {
            background: #D4AF37;
        }
        .normal-image {
            text-align: center;
        }
        .normal-image img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
        }
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #D4AF37;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: bold;
        }
        .rating-star {
            cursor: pointer;
            font-size: 1.5rem;
            color: #666;
            transition: color 0.2s ease;
        }
        .rating-star:hover {
            color: #D4AF37;
        }
    </style>
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero del Capítulo -->
    <section class="chapter-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?= BASE_URL ?>/index.php" class="text-warning">Inicio</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $chapterData['season_id'] ?>" class="text-warning">
                                    <?= htmlspecialchars($chapterData['season_title']) ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-light">Capítulo <?= $chapterData['chapter_number'] ?></li>
                        </ol>
                    </nav>
                    
                    <h1 class="display-4 title-font fw-bold mb-3">
                        Capítulo <?= $chapterData['chapter_number'] ?>: <?= htmlspecialchars(safeAccess($chapterData, 'title', 'Capítulo')) ?>
                    </h1>
                    
                    <?php if (safeAccess($chapterData, 'subtitle')): ?>
                    <p class="lead fs-3 mb-4">
                        <?= htmlspecialchars($chapterData['subtitle']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <?php if ($chapterData['is_free']): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-unlock me-1"></i>Gratis
                            </span>
                        <?php else: ?>
                            <span class="badge premium-badge">
                                <i class="bi bi-star-fill me-1"></i>Premium
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($chapterData['duration'])): ?>
                            <span class="text-light">
                                <i class="bi bi-clock me-1"></i><?= $chapterData['duration'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="chapter-icon">
                        <i class="bi bi-journal-text display-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido del Capítulo -->
    <section class="py-5" style="background: var(--dark);">
        <div class="container">
            <div class="row">
                <!-- Contenido Principal -->
                <div class="col-lg-8">
                    <div class="whisky-card p-4 p-md-5">
                        <!-- Imágenes destacadas (orden 1) - ARRIBA del texto -->
                        <?php if (!empty($featuredImages)): ?>
                            <div class="image-slider mb-5">
                                <?php foreach ($featuredImages as $index => $image): ?>
                                    <img src="<?= BASE_URL ?>/../../uploads/<?= $image['image_path'] ?>" 
                                         class="slider-image <?= $index === 0 ? 'active' : '' ?>" 
                                         alt="<?= htmlspecialchars($image['caption'] ?? $chapterData['title']) ?>">
                                    
                                    <?php if (!empty($image['caption'])): ?>
                                        <div class="text-center text-muted mt-2">
                                            <small><?= htmlspecialchars($image['caption']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($featuredImages) > 1): ?>
                                    <div class="slider-nav">
                                        <?php foreach ($featuredImages as $index => $image): ?>
                                            <div class="slider-dot <?= $index === 0 ? 'active' : '' ?>" 
                                                 onclick="changeSlide(<?= $index ?>)"></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Contenido del capítulo -->
                        <div class="chapter-content text-light">
                            <?= nl2br(htmlspecialchars($chapterData['content'])) ?>
                        </div>

                        <!-- Imágenes normales (orden 0) - DEBAJO del texto -->
                        <?php if (!empty($normalImages)): ?>
                            <div class="normal-images mt-5">
                                <?php foreach ($normalImages as $image): ?>
                                    <div class="normal-image mb-4">
                                        <img src="<?= BASE_URL ?>/../../uploads/<?= $image['image_path'] ?>" 
                                             alt="<?= htmlspecialchars($image['caption'] ?? $chapterData['title']) ?>">
                                        
                                        <?php if (!empty($image['caption'])): ?>
                                            <div class="text-center text-muted mt-2">
                                                <small><?= htmlspecialchars($image['caption']) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Navegación entre capítulos -->
                        <div class="d-flex justify-content-between mt-5 pt-4 border-top">
                            <?php
                            // Obtener capítulo anterior
                            $prevStmt = $pdo->prepare("
                                SELECT id, title FROM chapters 
                                WHERE season_id = ? AND chapter_number < ? AND is_published = 1 
                                ORDER BY chapter_number DESC LIMIT 1
                            ");
                            $prevStmt->execute([$chapterData['season_id'], $chapterData['chapter_number']]);
                            $prevChapter = $prevStmt->fetch();

                            // Obtener siguiente capítulo
                            $nextStmt = $pdo->prepare("
                                SELECT id, title FROM chapters 
                                WHERE season_id = ? AND chapter_number > ? AND is_published = 1 
                                ORDER BY chapter_number ASC LIMIT 1
                            ");
                            $nextStmt->execute([$chapterData['season_id'], $chapterData['chapter_number']]);
                            $nextChapter = $nextStmt->fetch();
                            ?>

                            <?php if ($prevChapter): ?>
                                <a href="<?= BASE_URL ?>/../src/pages/chapter.php?id=<?= $prevChapter['id'] ?>" class="btn btn-outline-gold">
                                    <i class="bi bi-arrow-left me-2"></i>Anterior
                                </a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $chapterData['season_id'] ?>" class="btn btn-outline-gold">
                                <i class="bi bi-list-ul me-2"></i>Lista de Capítulos
                            </a>

                            <?php if ($nextChapter): ?>
                                <a href="<?= BASE_URL ?>/../src/pages/chapter.php?id=<?= $nextChapter['id'] ?>" class="btn btn-gold">
                                    Siguiente<i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="whisky-card p-4 p-md-5 mt-4">
                        <h3 class="title-font h4 mb-4">Comentarios</h3>
                        
                        <?php if (!empty($comments)): ?>
                            <div class="comments-list">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item mb-4 pb-3 border-bottom">
                                        <div class="d-flex align-items-start">
                                            <div class="comment-avatar me-3">
                                                <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-warning"><?= htmlspecialchars($comment['username']) ?></strong>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-2"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                                <?php if ($comment['rating']): ?>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $comment['rating'] ? '-fill' : '' ?> text-warning"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-chat-dots display-6 mb-3"></i>
                                <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de comentario -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="add-comment mt-4">
                                <h5 class="mb-3">Agregar Comentario</h5>
                                <form id="commentForm">
                                    <input type="hidden" name="chapter_id" value="<?= $chapter_id ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Tu comentario</label>
                                        <textarea class="form-control" name="content" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Calificación (opcional)</label>
                                        <div class="rating-select d-flex gap-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" name="rating" value="<?= $i ?>" id="rating<?= $i ?>" class="d-none">
                                                <label for="rating<?= $i ?>" class="rating-star">
                                                    <i class="bi bi-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-gold">
                                        <i class="bi bi-send me-2"></i>Enviar Comentario
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="alert-link">Inicia sesión</a> para dejar un comentario.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Información del capítulo -->
                    <div class="whisky-card p-4 mb-4">
                        <h4 class="title-font h5 mb-3">Información</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong class="text-warning">Temporada:</strong><br>
                                <?= htmlspecialchars($chapterData['season_title']) ?>
                            </li>
                            <li class="mb-2">
                                <strong class="text-warning">Número:</strong><br>
                                Capítulo <?= $chapterData['chapter_number'] ?>
                            </li>
                            <li class="mb-2">
                                <strong class="text-warning">Tipo:</strong><br>
                                <?= $chapterData['is_free'] ? 'Gratuito' : 'Premium' ?>
                            </li>
                            <?php if (!empty($chapterData['duration'])): ?>
                            <li class="mb-2">
                                <strong class="text-warning">Duración:</strong><br>
                                <?= $chapterData['duration'] ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Progreso -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="whisky-card p-4">
                            <h4 class="title-font h5 mb-3">Tu Progreso</h4>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Capítulo completado
                            </div>
                            <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $chapterData['season_id'] ?>" class="btn btn-outline-gold w-100">
                                <i class="bi bi-arrow-left me-2"></i>Volver a la Temporada
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
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
            id: <?= $_SESSION['user_id'] ?? 'null' ?>
        }
    };

    // Funciones del slider para imágenes destacadas
    function changeSlide(index) {
        document.querySelectorAll('.slider-image').forEach(img => img.classList.remove('active'));
        document.querySelectorAll('.slider-dot').forEach(dot => dot.classList.remove('active'));
        
        document.querySelectorAll('.slider-image')[index].classList.add('active');
        document.querySelectorAll('.slider-dot')[index].classList.add('active');
    }

    // Sistema de calificación
    document.querySelectorAll('.rating-star').forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            
            // Actualizar estrellas
            document.querySelectorAll('.rating-star').forEach((s, i) => {
                if (i < rating) {
                    s.innerHTML = '<i class="bi bi-star-fill text-warning"></i>';
                } else {
                    s.innerHTML = '<i class="bi bi-star"></i>';
                }
            });
            
            // Marcar el radio button
            document.getElementById('rating' + rating).checked = true;
        });
    });

    // Formulario de comentarios
    document.getElementById('commentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_comment');
        
        fetch('<?= BASE_URL ?>/../../backend/api/comments.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Comentario enviado correctamente');
                this.reset();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    });

    // Auto-slide para imágenes destacadas
    <?php if (!empty($featuredImages) && count($featuredImages) > 1): ?>
    let currentSlide = 0;
    const totalSlides = <?= count($featuredImages) ?>;
    
    function autoSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        changeSlide(currentSlide);
    }
    
    // Cambiar slide cada 5 segundos
    setInterval(autoSlide, 5000);
    <?php endif; ?>
    </script>
</body>
</html>