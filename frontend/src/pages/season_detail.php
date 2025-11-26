<?php
// frontend/src/pages/season_detail.php

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

// Verificar si se proporcionó un ID de temporada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$season_id = intval($_GET['id']);
$seasonData = [];
$chapters = [];
$user_has_access = false;
$is_logged_in = isset($_SESSION['user_id']);

try {
    // Obtener datos de la temporada
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE id = ? AND is_published = 1");
    $stmt->execute([$season_id]);
    $seasonData = $stmt->fetch();
    
    if (!$seasonData) {
        die('Temporada no encontrada');
    }
    
    // Obtener capítulos de la temporada
    $chaptersStmt = $pdo->prepare("
        SELECT * FROM chapters 
        WHERE season_id = ? AND is_published = 1 
        ORDER BY chapter_number ASC
    ");
    $chaptersStmt->execute([$season_id]);
    $chapters = $chaptersStmt->fetchAll();
    
    // Verificar acceso del usuario
    if ($is_logged_in) {
        $user_id = $_SESSION['user_id'];
        
        // CORRECCIÓN: Para temporadas gratuitas, acceso total
        if ($seasonData['requires_payment'] == 0) {
            $user_has_access = true;
        } else {
            // Para temporadas premium, verificar pago
            $paymentStmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND season_id = ? AND status = 'completed'");
            $paymentStmt->execute([$user_id, $season_id]);
            $user_has_access = (bool)$paymentStmt->fetch();
        }
        
        // Obtener progreso del usuario
        $progressStmt = $pdo->prepare("SELECT chapter_id FROM user_progress WHERE user_id = ? AND season_id = ? AND is_completed = 1");
        $progressStmt->execute([$user_id, $season_id]);
        $completed_chapters = array_column($progressStmt->fetchAll(), 'chapter_id');
    } else {
        $completed_chapters = [];
    }
    
} catch (Exception $e) {
    error_log("Error loading season: " . $e->getMessage());
    die('Error al cargar la temporada');
}

// Obtener configuración de precios
$pricingStmt = $pdo->prepare("
    SELECT pc.*, s.title as season_title 
    FROM payment_configs pc 
    LEFT JOIN seasons s ON pc.season_id = s.id 
    WHERE (pc.is_active = 1 OR pc.is_active IS NULL)
    ORDER BY 
        CASE WHEN pc.season_id IS NULL THEN 0 ELSE 1 END,
        pc.season_id ASC
");
$pricingStmt->execute();
$pricingConfigs = $pricingStmt->fetchAll();

// Organizar precios
$seasonPrices = [];
$chapterPrice = 0;
$bundlePrice = 0;

foreach ($pricingConfigs as $config) {
    if ($config['season_id'] === null) {
        // Configuración global
        $chapterPrice = $config['chapter_price'];
        $bundlePrice = $config['bundle_price'];
    } else {
        // Precios por temporada
        $seasonPrices[$config['season_id']] = [
            'season_price' => $config['season_price'],
            'season_title' => $config['season_title']
        ];
    }
}

// Obtener configuración del sitio
$configStmt = $pdo->query("SELECT config_key, config_value FROM site_config");
$configData = $configStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Configuración bancaria
$bankConfig = [
    'bank_name' => $configData['bank_name'] ?? '',
    'account_number' => $configData['bank_account'] ?? '',
    'alias' => $configData['bank_alias'] ?? '',
    'account_holder' => $configData['bank_holder'] ?? ''
    
];

// Configuración de WhatsApp
$whatsappConfig = [
    'number' => $configData['whatsapp_number'] ?? '595983163300',
    'default_message' => $configData['whatsapp_message'] ?? 'Hola! Estoy interesado en El Camino del Whisky'
];

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
    <title><?= htmlspecialchars(safeAccess($seasonData, 'title', 'Temporada')) ?> | El Camino del Whisky</title>
    
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
        
        .season-hero {
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--gold-primary) 100%);
            color: var(--text-primary);
            padding: 120px 0 60px;
        }
        
        .chapter-item {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            background: var(--bg-card-light);
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
        }
        
        .chapter-item:hover {
            border-left-color: var(--gold-primary);
            background: var(--bg-card);
            transform: translateX(5px);
        }
        
        .chapter-item.completed {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .progress-ring {
            width: 100px;
            height: 100px;
        }
        
        .circular-progress {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(var(--gold-primary) 0%, var(--bg-card) 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 1s ease;
        }
        
        .circular-progress::before {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-card);
        }
        
        .progress-value {
            position: relative;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.2rem;
            z-index: 1;
        }
        
        /* Mejoras de contraste y legibilidad */
        .title-font {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--text-primary);
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .whisky-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .text-light {
            color: var(--text-secondary) !important;
            font-weight: 400;
        }
        
        .text-muted {
            color: var(--text-muted) !important;
            font-weight: 400;
        }
        
        .text-warning {
            color: var(--gold-primary) !important;
            font-weight: 600;
        }
        
        .text-success {
            color: #28a745 !important;
            font-weight: 500;
        }
        
        /* Badges mejorados */
        .badge {
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
        }
        
        .premium-badge {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            color: #000;
        }
        
        .bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            color: white;
        }
        
        .bg-warning {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary)) !important;
            color: #000 !important;
        }
        
        /* Botones mejorados */
        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            border: none;
            color: #000;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(212, 175, 55, 0.3);
        }
        
        .btn-gold:hover {
            background: linear-gradient(135deg, var(--gold-secondary), var(--gold-primary));
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(212, 175, 55, 0.4);
        }
        
        .btn-outline-gold {
            color: var(--gold-primary);
            border: 2px solid var(--gold-primary);
            background: transparent;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-gold:hover {
            background: var(--gold-primary);
            color: #000;
            transform: translateY(-1px);
        }
        
        /* Mejora de textos */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .display-4 {
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .lead {
            color: var(--text-secondary);
            font-weight: 400;
            line-height: 1.6;
        }
        
        strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        /* Lista de capítulos mejorada */
        .chapters-list {
            margin-top: 20px;
        }
        
        .chapter-item h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .chapter-item p {
            color: var(--text-secondary);
            font-weight: 400;
            line-height: 1.5;
        }
        
        .chapter-item .small {
            color: var(--text-muted);
            font-weight: 400;
        }
        
        /* Iconos mejorados */
        .bi {
            opacity: 0.9;
        }
        
        .bi-check-circle-fill {
            color: #28a745;
        }
        
        .bi-play-circle {
            color: var(--gold-primary);
        }
        
        .bi-lock {
            color: var(--text-muted);
        }
        
        .bi-star-fill, .bi-star {
            color: var(--gold-primary);
        }
        
        .bi-unlock {
            color: #28a745;
        }
        
        /* Estados de acceso */
        .access-granted {
            color: #28a745;
            font-weight: 600;
        }
        
        .access-limited {
            color: var(--gold-primary);
            font-weight: 600;
        }
        
        /* Opciones de precio */
        .pricing-option {
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .pricing-option:hover {
            border-color: var(--gold-primary);
            transform: translateY(-2px);
        }
        
        .pricing-option.selected {
            border-color: var(--gold-primary);
            background: rgba(212, 175, 55, 0.1);
        }
        
        .price-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold-primary);
        }
        
        .price-free {
            color: #28a745 !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .season-hero {
                padding: 100px 0 40px;
            }
            
            .display-4 {
                font-size: 2.5rem;
            }
            
            .progress-ring {
                width: 80px;
                height: 80px;
            }
            
            .circular-progress::before {
                width: 60px;
                height: 60px;
            }
            
            .progress-value {
                font-size: 1rem;
            }
        }
        
        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chapter-item {
            animation: fadeInUp 0.5s ease-out;
        }
        
        .chapter-item:nth-child(1) { animation-delay: 0.1s; }
        .chapter-item:nth-child(2) { animation-delay: 0.2s; }
        .chapter-item:nth-child(3) { animation-delay: 0.3s; }
        .chapter-item:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero de la Temporada -->
    <section class="season-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 title-font fw-bold mb-3">
                        <?= htmlspecialchars(safeAccess($seasonData, 'title', 'Temporada')) ?>
                    </h1>
                    
                    <?php if (safeAccess($seasonData, 'subtitle')): ?>
                    <p class="lead fs-3 mb-4">
                        <?= htmlspecialchars($seasonData['subtitle']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <?php if ($seasonData['requires_payment']): ?>
                            <span class="badge premium-badge fw-bold">
                                <i class="bi bi-star-fill me-1"></i>Premium
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success fw-bold">
                                <i class="bi bi-unlock me-1"></i>Gratis
                            </span>
                        <?php endif; ?>
                        
                        <span class="text-light fw-medium">
                            <i class="bi bi-play-circle me-1"></i>
                            <?= count($chapters) ?> capítulos
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="season-icon">
                        <i class="bi bi-collection-play display-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido de la Temporada -->
    <section class="py-5" style="background: var(--bg-dark);">
        <div class="container">
            <div class="row">
                <!-- Información de la Temporada -->
                <div class="col-lg-4 mb-4">
                    <div class="whisky-card p-4 mb-4">
                        <h4 class="title-font h5 mb-3 fw-bold">Información</h4>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <strong class="text-warning fw-bold">Capítulos:</strong><br>
                                <span class="text-light fw-medium"><?= count($chapters) ?> lecciones</span>
                            </li>
                            <li class="mb-3">
                                <strong class="text-warning fw-bold">Acceso:</strong><br>
                                <span class="text-light fw-medium"><?= $seasonData['requires_payment'] ? 'Premium' : 'Gratuito' ?></span>
                            </li>
                            <?php if ($seasonData['requires_payment']): ?>
                                <?php 
                                $currentSeasonPrice = $seasonPrices[$season_id]['season_price'] ?? $seasonData['price'];
                                $freeChaptersCount = count(array_filter($chapters, function($chapter) {
                                    return $chapter['is_free'] == 1;
                                }));
                                $premiumChaptersCount = count($chapters) - $freeChaptersCount;
                                ?>
                                <li class="mb-3">
                                    <strong class="text-warning fw-bold">Precios:</strong><br>
                                    <?php if ($currentSeasonPrice > 0): ?>
                                        <span class="text-light fw-medium">Temporada: $<?= number_format($currentSeasonPrice, 2) ?></span><br>
                                    <?php endif; ?>
                                    <?php if ($chapterPrice > 0 && $premiumChaptersCount > 0): ?>
                                        <span class="text-light fw-medium">Por capítulo: $<?= number_format($chapterPrice, 2) ?></span><br>
                                    <?php endif; ?>
                                    <?php if ($bundlePrice > 0): ?>
                                        <span class="text-light fw-medium">Combo completo: $<?= number_format($bundlePrice, 2) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                            <li class="mb-3">
                                <strong class="text-warning fw-bold">Estado:</strong><br>
                                <?php if ($user_has_access): ?>
                                    <span class="access-granted fw-bold">✓ Acceso concedido</span>
                                <?php else: ?>
                                    <span class="access-limited fw-bold">Acceso limitado</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                        
                        <!-- CORRECCIÓN: Lógica mejorada para mostrar botones -->
                        <?php if (!$user_has_access): ?>
                            <?php if ($seasonData['requires_payment']): ?>
                                <?php if ($is_logged_in): ?>
                                    <!-- Usuario logueado pero sin acceso premium -->
                                    <button class="btn btn-gold w-100 mt-3 fw-bold" onclick="window.showPaymentModal(<?= $season_id ?>)">
                                        <i class="bi bi-credit-card me-2"></i>Comprar Acceso
                                    </button>
                                <?php else: ?>
                                    <!-- Usuario no logueado en temporada premium -->
                                    <button class="btn btn-gold w-100 mt-3 fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal">
                                        <i class="bi bi-person-plus me-2"></i>Iniciar Sesión para Comprar
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Temporada gratuita pero usuario no logueado -->
                                <button class="btn btn-gold w-100 mt-3 fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <i class="bi bi-person-plus me-2"></i>Iniciar Sesión para Acceder
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Progreso -->
                    <?php if ($is_logged_in && $user_has_access): ?>
                        <?php
                        $completed_count = count($completed_chapters);
                        $total_count = count($chapters);
                        $progress_percentage = $total_count > 0 ? round(($completed_count / $total_count) * 100) : 0;
                        ?>
                        <div class="whisky-card p-4">
                            <h4 class="title-font h5 mb-3 fw-bold">Tu Progreso</h4>
                            <div class="text-center">
                                <div class="progress-ring mb-3 mx-auto">
                                    <div class="circular-progress" data-percentage="<?= $progress_percentage ?>">
                                        <span class="progress-value">0%</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold text-light"><?= $progress_percentage ?>%</h5>
                                <p class="text-muted mb-0 fw-medium">
                                    <?= $completed_count ?> de <?= $total_count ?> capítulos completados
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Lista de Capítulos -->
                <div class="col-lg-8">
                    <div class="whisky-card p-4">
                        <h3 class="title-font h4 mb-4 fw-bold">Capítulos</h3>
                        
                        <?php if (!empty($chapters)): ?>
                            <div class="chapters-list">
                                <?php foreach ($chapters as $index => $chapter): ?>
                                    <?php
                                    $is_completed = in_array($chapter['id'], $completed_chapters);
                                    
                                    // CORRECCIÓN IMPORTANTE: Lógica de acceso corregida
                                    if ($user_has_access) {
                                        // Si tiene acceso a la temporada, acceso total
                                        $can_access = true;
                                    } else {
                                        // Si no tiene acceso a la temporada, solo capítulos gratuitos
                                        $can_access = ($chapter['is_free'] == 1);
                                    }
                                    
                                    $chapter_class = $is_completed ? 'completed' : '';
                                    ?>
                                    
                                    <div class="chapter-item p-4 rounded <?= $chapter_class ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-start">
                                                    <?php if ($is_completed): ?>
                                                        <i class="bi bi-check-circle-fill text-success me-3 mt-1 fs-5"></i>
                                                    <?php elseif ($can_access): ?>
                                                        <i class="bi bi-play-circle text-warning me-3 mt-1 fs-5"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-lock text-muted me-3 mt-1 fs-5"></i>
                                                    <?php endif; ?>
                                                    
                                                    <div>
                                                        <h5 class="mb-1 fw-bold">
                                                            Capítulo <?= $chapter['chapter_number'] ?>: 
                                                            <?= htmlspecialchars($chapter['title']) ?>
                                                        </h5>
                                                        <?php if (!empty($chapter['subtitle'])): ?>
                                                            <p class="text-muted mb-2 fw-medium"><?= htmlspecialchars($chapter['subtitle']) ?></p>
                                                        <?php endif; ?>
                                                        <div class="d-flex gap-3 text-muted small fw-medium">
                                                            <?php if (!empty($chapter['duration'])): ?>
                                                                <span><i class="bi bi-clock me-1"></i><?= $chapter['duration'] ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($chapter['is_free']): ?>
                                                                <span class="text-success fw-bold"><i class="bi bi-unlock me-1"></i>Gratis</span>
                                                            <?php else: ?>
                                                                <span class="text-warning fw-bold"><i class="bi bi-star me-1"></i>Premium</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <?php if ($can_access): ?>
                                                    <a href="<?= BASE_URL ?>/../src/pages/chapter.php?id=<?= $chapter['id'] ?>" class="btn btn-outline-gold fw-bold">
                                                        <?php if ($is_completed): ?>
                                                            <i class="bi bi-arrow-repeat me-2"></i>Repasar
                                                        <?php else: ?>
                                                            <i class="bi bi-play-circle me-2"></i>Ver Capítulo
                                                        <?php endif; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark fw-bold">
                                                        <i class="bi bi-lock me-1"></i>Premium
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-1 mb-3"></i>
                                <p class="fw-medium">No hay capítulos disponibles en esta temporada.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
        bank: {
            name: '<?= $bankConfig['bank_name'] ?>',
            account: '<?= $bankConfig['account_number'] ?>',
            alias: '<?= $bankConfig['alias'] ?>',
            holder: '<?= $bankConfig['account_holder'] ?>'
        },
        user: {
            id: <?= $_SESSION['user_id'] ?? 'null' ?>,
            isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
            username: '<?= $_SESSION['user_name'] ?? 'Usuario' ?>',
            email: '<?= $_SESSION['user_email'] ?? '' ?>'
        },
        pricing: {
            chapterPrice: <?= $chapterPrice ?>,
            bundlePrice: <?= $bundlePrice ?>,
            seasonPrices: <?= json_encode($seasonPrices) ?>
        },
        currentSeason: {
            id: <?= $season_id ?>,
            title: '<?= addslashes($seasonData['title']) ?>',
            price: <?= $seasonPrices[$season_id]['season_price'] ?? $seasonData['price'] ?? 0 ?>
        }
    };

    // Función global para mostrar modal de pago
    window.showPaymentModal = function(seasonId) {
        console.log('💰 Mostrando modal de pago para temporada:', seasonId);
        
        // Verificar sesión
        if (!SITE_CONFIG.user.isLoggedIn) {
            console.log('❌ Usuario no logueado, redirigiendo a login');
            $('#paymentModal').modal('hide');
            $('#loginModal').modal('show');
            return;
        }
        
        // Cargar opciones de precio automáticamente
        loadPricingOptions(seasonId);
        
        $('#paymentModal').modal('show');
    };

    // Cargar opciones de precio
    function loadPricingOptions(seasonId) {
        const seasonPrice = SITE_CONFIG.currentSeason.price;
        const chapterPrice = SITE_CONFIG.pricing.chapterPrice;
        const bundlePrice = SITE_CONFIG.pricing.bundlePrice;
        const seasonTitle = SITE_CONFIG.currentSeason.title;
        
        let optionsHtml = '';
        
        // Opción: Temporada completa
        if (seasonPrice > 0) {
            optionsHtml += `
                <div class="col-md-6 mb-3">
                    <div class="pricing-option whisky-card p-3 text-center selected" 
                         data-type="season" 
                         data-price="${seasonPrice}" 
                         onclick="selectPricingOption(this, 'season', ${seasonPrice}, '${seasonTitle}')">
                        <h6 class="fw-bold">Temporada Completa</h6>
                        <div class="price-amount">$${seasonPrice.toFixed(2)}</div>
                        <small class="text-muted">Acceso a todos los capítulos premium</small>
                    </div>
                </div>
            `;
        }
        
        // Opción: Por capítulo (si hay capítulos premium)
        if (chapterPrice > 0) {
            optionsHtml += `
                <div class="col-md-6 mb-3">
                    <div class="pricing-option whisky-card p-3 text-center" 
                         data-type="chapter" 
                         data-price="${chapterPrice}" 
                         onclick="selectPricingOption(this, 'chapter', ${chapterPrice}, 'Capítulos Individuales')">
                        <h6 class="fw-bold">Por Capítulo</h6>
                        <div class="price-amount">$${chapterPrice.toFixed(2)}</div>
                        <small class="text-muted">Compra capítulos individuales</small>
                    </div>
                </div>
            `;
        }
        
        // Opción: Combo completo
        if (bundlePrice > 0) {
            optionsHtml += `
                <div class="col-md-6 mb-3">
                    <div class="pricing-option whisky-card p-3 text-center" 
                         data-type="bundle" 
                         data-price="${bundlePrice}" 
                         onclick="selectPricingOption(this, 'bundle', ${bundlePrice}, 'Combo Completo')">
                        <h6 class="fw-bold">Combo Completo</h6>
                        <div class="price-amount">$${bundlePrice.toFixed(2)}</div>
                        <small class="text-muted">Todas las temporadas premium</small>
                    </div>
                </div>
            `;
        }
        
        // Si no hay opciones de pago (todo gratis)
        if (optionsHtml === '') {
            optionsHtml = `
                <div class="col-12">
                    <div class="alert alert-success text-center">
                        <i class="bi bi-gift-fill me-2"></i>
                        <strong>¡Acceso Gratuito!</strong><br>
                        Todos los contenidos están disponibles sin costo.
                    </div>
                </div>
            `;
        }
        
        $('#pricingOptions').html(optionsHtml);
        
        // Seleccionar automáticamente la temporada completa
        if (seasonPrice > 0) {
            $('#selectedPrice').val(seasonPrice);
            $('#selectedType').val('season');
            $('#selectedDescription').val(seasonTitle);
            $('#continuePaymentBtn').prop('disabled', false);
        }
    }

    // Seleccionar opción de precio
    function selectPricingOption(element, type, price, description) {
        $('.pricing-option').removeClass('selected');
        $(element).addClass('selected');
        
        $('#selectedPrice').val(price);
        $('#selectedType').val(type);
        $('#selectedDescription').val(description);
        
        // Habilitar botón de continuar
        $('#continuePaymentBtn').prop('disabled', false);
    }

    // Continuar al método de pago
    function continueToPayment() {
        const selectedPrice = $('#selectedPrice').val();
        const selectedType = $('#selectedType').val();
        const selectedDescription = $('#selectedDescription').val();
        
        if (!selectedPrice || !selectedType) {
            alert('Por favor selecciona una opción de pago');
            return;
        }
        
        $('#paymentStep1').hide();
        $('#paymentStep2').show();
        
        // Actualizar información en el paso 2
        $('#paymentSummary').html(`
            <strong>Producto:</strong> ${selectedDescription}<br>
            <strong>Monto:</strong> $${parseFloat(selectedPrice).toFixed(2)} USD<br>
            <strong>Tipo:</strong> ${selectedType === 'season' ? 'Temporada' : selectedType === 'chapter' ? 'Capítulos' : 'Combo'}
        `);
    }

    // Volver atrás en el proceso de pago
    function backToPricing() {
        $('#paymentStep2').hide();
        $('#paymentStep1').show();
    }

    // Procesar pago con WhatsApp
    function processPayment() {
        const selectedPrice = $('#selectedPrice').val();
        const selectedType = $('#selectedType').val();
        const selectedDescription = $('#selectedDescription').val();
        const paymentMethod = $('.payment-method.active').data('method');
        
        if (!selectedPrice || !selectedType) {
            alert('Por favor completa todos los campos');
            return;
        }
        
        const user = SITE_CONFIG.user;
        const bankInfo = SITE_CONFIG.bank;
        
        let whatsappMessage = `Hola! Estoy interesado en adquirir acceso premium a El Camino del Whisky.

📋 Mi información:
• Usuario: ${user.username}
• Email: ${user.email}
• Producto: ${selectedDescription}
• Monto: $${parseFloat(selectedPrice).toFixed(2)} USD
• Tipo: ${selectedType === 'season' ? 'Temporada' : selectedType === 'chapter' ? 'Capítulos' : 'Combo'}
• Método de pago: ${paymentMethod === 'transfer' ? 'Transferencia bancaria' : 'Efectivo'}`;

        // Si es transferencia, agregar información bancaria
        if (paymentMethod === 'transfer') {
            whatsappMessage += `

🏦 *Información Bancaria:*
• Banco: ${bankInfo.name}
• Número de cuenta: ${bankInfo.account}
• Alias: ${bankInfo.alias}
• Titular: ${bankInfo.holder}


*Ya realicé la transferencia* 💰`;
        } else {
            whatsappMessage += `

💵 *Método de pago: Efectivo*
*Ya tengo el efectivo listo* 💵`;
        }

        whatsappMessage += `

Por favor, confirmen la recepción del pago y activen mi acceso premium.`;

        const encodedMessage = encodeURIComponent(whatsappMessage);
        const whatsappUrl = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodedMessage}`;
        
        window.open(whatsappUrl, '_blank');
        $('#paymentModal').modal('hide');
        
        // Mostrar mensaje de confirmación
        showToast('¡Perfecto!', 'Se abrió WhatsApp para confirmar tu pago', 'success');
    }

    // Mostrar información bancaria
    function showBankInfo() {
        $('#bankInfoModal').modal('show');
    }

    // Función para mostrar notificaciones
    function showToast(title, message, type = 'info') {
        // Crear toast dinámico si no existe
        let toast = document.getElementById('dynamicToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'dynamicToast';
            toast.className = 'toast align-items-center text-white bg-' + 
                (type === 'success' ? 'success' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info') + 
                ' border-0';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.querySelector('.toast-container').appendChild(toast);
        }
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    // Animación de progreso circular
    document.addEventListener('DOMContentLoaded', function() {
        const progressElements = document.querySelectorAll('.circular-progress');
        
        progressElements.forEach(progress => {
            const percentage = parseInt(progress.getAttribute('data-percentage'));
            const valueElement = progress.querySelector('.progress-value');
            
            // Configurar el conic-gradient inicial
            progress.style.background = `conic-gradient(var(--gold-primary) 0%, var(--bg-card) 0%)`;
            
            // Animación del porcentaje y del círculo
            let current = 0;
            const interval = setInterval(() => {
                if (current >= percentage) {
                    clearInterval(interval);
                } else {
                    current++;
                    valueElement.textContent = current + '%';
                    // Actualizar el conic-gradient
                    progress.style.background = `conic-gradient(var(--gold-primary) ${current}%, var(--bg-card) 0%)`;
                }
            }, 30);
        });
    });

    // Inicialización de métodos de pago
    $(document).ready(function() {
        $('.payment-method').on('click', function() {
            $('.payment-method').removeClass('active');
            $(this).addClass('active');
        });

        // Resetear modal cuando se cierra
        $('#paymentModal').on('hidden.bs.modal', function() {
            $('#paymentStep2').hide();
            $('#paymentStep1').show();
            $('.payment-method').removeClass('active');
            $('.payment-method[data-method="transfer"]').addClass('active');
        });
    });
    </script>
</body>
</html>