<?php
// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ecdw/frontend/public');
}

// Incluir la conexión a la base de datos
require_once ROOT_DIR . '/backend/config/database.php';

// Incluir el modelo Alliance
require_once ROOT_DIR . '/backend/models/Alliance.php';
// Incluir el modelo WhiskyEvent
require_once ROOT_DIR . '/backend/models/WhiskyEvent.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos del usuario actual
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
    }
}

// Inicializar todas las variables con valores por defecto
$sliderItems = [];
$whatsappConfig = [
    'number' => '595983163300', 
    'default_message' => 'Hola! Estoy interesado en El Camino del Whisky'
];
$bankConfig = [
    'bank_name' => '', 
    'account_number' => '', 
    'alias' => '', 
    'account_holder' => '', 
    'ruc' => ''
];
$alliances = [];
$seasons = [];
$events = [];

// Obtener datos via API o directamente desde BD
try {
    // Obtener slider items
    $sliderItems = getSliderItems() ?? [];
    
    // Obtener configuración
    $whatsappConfig = getWhatsAppConfig() ?? $whatsappConfig;
    $bankConfig = getBankConfig() ?? $bankConfig;
    
    // Obtener alianzas directamente desde el modelo
    $alliances = getAlliancesDirect() ?? [];
    
    // Obtener temporadas para la sección
    $seasons = getPublishedSeasons() ?? [];
    
    // Obtener eventos futuros
    $events = getUpcomingEvents() ?? [];
    
} catch (Exception $e) {
    // Las variables ya tienen valores por defecto
    error_log("Error in index.php: " . $e->getMessage());
}

/**
 * Función helper para acceso seguro a arrays
 */
function safeArrayAccess($array, $key, $default = null) {
    return (isset($array) && is_array($array) && isset($array[$key])) ? $array[$key] : $default;
}

/**
 * Obtener alianzas directamente desde el modelo
 */
function getAlliancesDirect() {
    global $pdo;
    
    try {
        // Crear instancia del modelo Alliance
        $allianceModel = new Alliance($pdo);
        
        // Obtener alianzas activas directamente
        $alliances = $allianceModel->getAllActive();
        
        return is_array($alliances) ? $alliances : [];
        
    } catch (Exception $e) {
        error_log("Error getting alliances directly: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener eventos futuros
 */
function getUpcomingEvents($limit = 6) {
    global $pdo;
    
    try {
        // Crear instancia del modelo WhiskyEvent
        $eventModel = new WhiskyEvent($pdo);
        
        // Obtener eventos futuros
        $events = $eventModel->getUpcomingEvents($limit);
        
        return is_array($events) ? $events : [];
        
    } catch (Exception $e) {
        error_log("Error getting upcoming events: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener secciones del proyecto desde BD
 */
function getProjectSections() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT section_title, section_content 
            FROM project_content 
            WHERE is_active = 1 
            ORDER BY display_order ASC, created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting project sections: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener items del slider desde BD
 */
function getSliderItems() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT hs.*, c.title as chapter_title, c.subtitle as chapter_subtitle, c.id as chapter_id, s.title as season_title
            FROM home_slider hs
            LEFT JOIN chapters c ON hs.chapter_id = c.id
            LEFT JOIN seasons s ON c.season_id = s.id
            WHERE hs.is_active = 1
            ORDER BY hs.display_order ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting slider items: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener configuración de WhatsApp desde BD
 */
function getWhatsAppConfig() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT config_value as number 
            FROM site_config 
            WHERE config_key = 'whatsapp_number'
        ");
        $stmt->execute();
        $number = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT config_value as default_message 
            FROM site_config 
            WHERE config_key = 'whatsapp_message'
        ");
        $stmt->execute();
        $defaultMessage = $stmt->fetchColumn();
        
        return [
            'number' => $number ?: '595983163300',
            'default_message' => $defaultMessage ?: 'Hola! Estoy interesado en El Camino del Whisky'
        ];
    } catch (Exception $e) {
        return [
            'number' => '595983163300',
            'default_message' => 'Hola! Estoy interesado en El Camino del Whisky'
        ];
    }
}

/**
 * Obtener configuración bancaria desde BD
 */
function getBankConfig() {
    global $pdo;
    try {
        $config = [];
        $keys = [
            'bank_name' => 'bank_name',
            'account_number' => 'bank_account',
            'alias' => 'bank_alias', 
            'account_holder' => 'bank_holder',
            'ruc' => 'bank_ruc'
        ];
        
        foreach ($keys as $key => $dbKey) {
            $stmt = $pdo->prepare("SELECT config_value FROM site_config WHERE config_key = ?");
            $stmt->execute([$dbKey]);
            $config[$key] = $stmt->fetchColumn() ?: '';
        }
        
        return $config;
    } catch (Exception $e) {
        return [
            'bank_name' => '',
            'account_number' => '',
            'alias' => '',
            'account_holder' => '',
            'ruc' => ''
        ];
    }
}

/**
 * Obtener temporadas publicadas desde BD
 */
function getPublishedSeasons() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM seasons 
            WHERE is_published = 1 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting published seasons: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener capítulos por temporada desde BD
 */
function getChaptersBySeason($seasonId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM chapters 
            WHERE season_id = ? AND is_published = 1 
            ORDER BY chapter_number ASC
        ");
        $stmt->execute([$seasonId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting chapters by season: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener progreso del usuario actual
 */
function getUserProgress($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT chapter_id, season_id, is_completed 
            FROM user_progress 
            WHERE user_id = ? AND is_completed = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting user progress: " . $e->getMessage());
        return [];
    }
}

// Obtener progreso del usuario si está logueado
$userProgress = [];
if ($currentUser) {
    $userProgress = getUserProgress($currentUser['id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL CAMINO DEL WHISKY | Experiencias de Cata Premium</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/../src/styles/main.css">
    
    <style>
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        .circular-progress {
            position: relative;
            height: 80px;
            width: 80px;
            border-radius: 50%;
            background: conic-gradient(#D4AF37 0deg, rgba(255,255,255,0.1) 0deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .circular-progress::before {
            content: "";
            position: absolute;
            height: 70px;
            width: 70px;
            border-radius: 50%;
            background: var(--dark);
        }
        .progress-value {
            position: relative;
            font-size: 16px;
            font-weight: 600;
            color: #D4AF37;
        }
        .slider-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .slider-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        .slider-content {
            position: relative;
            z-index: 2;
        }
        .season-badge-slider {
            display: inline-block;
            background: rgba(212, 175, 55, 0.2);
            color: #D4AF37;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }
        .event-image {
            border-radius: 8px 8px 0 0;
        }
        .event-placeholder {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        .whisky-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .whisky-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2);
        }
    </style>
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero Section con Slider -->
    <section id="inicio" class="hero-section">
        <div id="mainSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-touch="true" data-bs-pause="false">
            <!-- Indicadores -->
            <?php if (isset($sliderItems) && is_array($sliderItems) && count($sliderItems) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($sliderItems as $index => $item): ?>
                <button type="button" data-bs-target="#mainSlider" data-bs-slide-to="<?php echo $index; ?>" 
                        class="<?php echo $index === 0 ? 'active' : ''; ?>" 
                        <?php if($index === 0): ?>aria-current="true"<?php endif; ?>
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Slides -->
            <div class="carousel-inner">
                <?php if (isset($sliderItems) && is_array($sliderItems) && !empty($sliderItems)): ?>
                    <?php foreach ($sliderItems as $index => $item): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php
                        $backgroundImage = 'https://images.unsplash.com/photo-1531386450450-969d3bdccfe5';
                        if (isset($item['image_path']) && !empty($item['image_path'])) {
                            // RUTA CORREGIDA
                            $backgroundImage = BASE_URL . '/../../' . $item['image_path'];
                        }
                        ?>
                        <div class="slider-background" style="background-image: url('<?php echo $backgroundImage; ?>');"></div>
                        <div class="container">
                            <div class="row align-items-center min-vh-100 py-5">
                                <div class="col-lg-8 mx-auto text-center">
                                    <div class="slider-content">
                                        <?php if (isset($item['season_title']) && !empty($item['season_title'])): ?>
                                        <div class="season-badge-slider mb-3">
                                            <?php echo htmlspecialchars($item['season_title']); ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <h1 class="display-3 title-font fw-bold mb-4 text-white">
                                            <?php 
                                            if (isset($item['title']) && !empty($item['title'])) {
                                                echo htmlspecialchars($item['title']);
                                            } elseif (isset($item['chapter_title']) && !empty($item['chapter_title'])) {
                                                echo htmlspecialchars($item['chapter_title']);
                                            } else {
                                                echo 'EL CAMINO DEL WHISKY';
                                            }
                                            ?>
                                        </h1>
                                        
                                        <?php 
                                        $subtitle = '';
                                        if (isset($item['subtitle']) && !empty($item['subtitle'])) {
                                            $subtitle = $item['subtitle'];
                                        } elseif (isset($item['chapter_subtitle']) && !empty($item['chapter_subtitle'])) {
                                            $subtitle = $item['chapter_subtitle'];
                                        }
                                        ?>
                                        <?php if (!empty($subtitle)): ?>
                                        <p class="lead mb-5 fs-4 text-light">
                                            <?php echo htmlspecialchars($subtitle); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                                            <?php if (isset($item['chapter_id']) && !empty($item['chapter_id'])): ?>
                                            <button class="btn btn-gold btn-lg" onclick="loadChapter(<?php echo $item['chapter_id']; ?>)">
                                                <i class="bi bi-play-circle me-2"></i>
                                                <?php echo htmlspecialchars(safeArrayAccess($item, 'button_text', 'Ver Capítulo')); ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (!$currentUser): ?>
                                            <button class="btn btn-outline-gold btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                                                <i class="bi bi-person-plus me-2"></i>Regístrate Gratis
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Slide por defecto -->
                    <div class="carousel-item active">
                        <div class="slider-background" style="background-image: url('https://images.unsplash.com/photo-1531386450450-969d3bdccfe5');"></div>
                        <div class="container">
                            <div class="row align-items-center min-vh-100 py-5">
                                <div class="col-lg-8 mx-auto text-center">
                                    <div class="slider-content">
                                        <div class="season-badge-slider mb-3">
                                            BIENVENIDO
                                        </div>
                                        
                                        <h1 class="display-3 title-font fw-bold mb-4 text-white">
                                            EL CAMINO DEL WHISKY
                                        </h1>
                                        
                                        <p class="lead mb-5 fs-4 text-light">
                                            Descubre el arte de la cata con nuestro experto catador. Un viaje sensorial a través de los mejores whiskies del mundo.
                                        </p>
                                        
                                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                                        <button class="btn btn-gold btn-lg" data-bs-toggle="modal" data-bs-target="<?= $currentUser ? '#temporadas' : '#registerModal' ?>">
                                                <i class="bi bi-play-circle me-2"></i>
                                                <?= $currentUser ? 'Explorar Temporadas' : 'Comenzar Ahora' ?>
                                            </button>
                                            <?php if (!$currentUser): ?>
                                            <button class="btn btn-outline-gold btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                                                <i class="bi bi-person-plus me-2"></i>Regístrate Gratis
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Controles -->
            <?php if (isset($sliderItems) && is_array($sliderItems) && count($sliderItems) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
            <?php endif; ?>
        </div>
    </section>
    
<!-- Sección: El Proyecto -->
<section id="proyecto" class="py-5" style="background: var(--dark);">
    <div class="container py-5">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="title-font display-4 mb-3">El Proyecto</h2>
                <div class="section-divider mx-auto"></div>
                <p class="lead text-light fs-5">Una travesía sensorial que transforma la curiosidad en maestría</p>
            </div>
        </div>
        
        <!-- Contenido del proyecto desde BD -->
        <?php
        $projectSections = getProjectSections();
        if ($projectSections && count($projectSections) > 0):
            $half = ceil(count($projectSections) / 2);
            $leftColumn = array_slice($projectSections, 0, $half);
            $rightColumn = array_slice($projectSections, $half);
        ?>
        <div class="row align-items-start">
            <!-- Columna Izquierda -->
            <div class="col-lg-6">
                <?php foreach ($leftColumn as $index => $section): ?>
                <div class="proyecto-section mb-5" data-aos="fade-right" data-aos-delay="<?= $index * 100 ?>">
                    <?php if (!empty($section['section_title'])): ?>
                    <h3 class="title-font h3 mb-3 text-warning">
                        <?= htmlspecialchars($section['section_title']) ?>
                    </h3>
                    <?php endif; ?>
                    <div class="proyecto-content">
                        <p class="fs-5 mb-4 lh-lg">
                            <?= nl2br(htmlspecialchars($section['section_content'])) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Columna Derecha -->
            <div class="col-lg-6">
                <?php foreach ($rightColumn as $index => $section): ?>
                <div class="proyecto-section mb-5" data-aos="fade-left" data-aos-delay="<?= ($index + $half) * 100 ?>">
                    <?php if (!empty($section['section_title'])): ?>
                    <h3 class="title-font h3 mb-3 text-warning">
                        <?= htmlspecialchars($section['section_title']) ?>
                    </h3>
                    <?php endif; ?>
                    <div class="proyecto-content">
                        <p class="fs-5 mb-4 lh-lg">
                            <?= nl2br(htmlspecialchars($section['section_content'])) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sección CTA Centrada - NUEVA POSICIÓN -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="proyecto-cta text-center p-5 rounded" style="background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); max-width: 800px; margin: 0 auto;">
                    <h4 class="title-font h2 mb-4 text-warning">¿Listo para comenzar tu viaje?</h4>
                    <p class="mb-4 fs-5">Únete a nuestra comunidad de apasionados por el whisky y descubre un mundo de sabores, aromas y experiencias únicas.</p>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                        <?php if (!$currentUser): ?>
                        <button class="btn btn-gold btn-lg px-4" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="bi bi-person-plus me-2"></i>Unirse al Proyecto
                        </button>
                        <?php else: ?>
                        <a href="#temporadas" class="btn btn-gold btn-lg px-4">
                            <i class="bi bi-play-circle me-2"></i>Continuar Aprendiendo
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-gold btn-lg px-4" onclick="contactWhatsApp()">
                            <i class="bi bi-whatsapp me-2"></i>Consultar por WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Contenido por defecto -->
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="title-font h2 mb-4 text-warning">Apreciado lector:</h3>
                <div class="proyecto-content">
                    <p class="fs-5 mb-4 lh-lg">
                        Así como vos, un día —hace ya algunos años— decidí que quería conocer más sobre este apreciado y, para muchos, misterioso espirituoso. Pensé que el camino sería fácil: después de todo, hoy existen infinidad de canales de información y personas dispuestas a compartir su experiencia en casi cualquier tema. ¡Pero grande fue mi sorpresa cuando descubrí que no era así!
                    </p>
                    <p class="fs-5 mb-4 lh-lg">
                        Pronto me di cuenta de que no existe un camino claro para quienes desean empezar desde cero. La mayoría de los canales hablan de botellas específicas, describen notas de cata y cuentan detalles sobre las destilerías —algo valioso, sin duda—, pero nadie explica qué botellas comprar, dónde hacerlo o cómo leer una etiqueta. Y si encontrás a alguien que más o menos lo hace, suele referirse a un mercado muy distinto al nuestro.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="proyecto-content">
                    <p class="fs-5 mb-4 lh-lg">
                        Aun así, decidí seguir adelante. Con las herramientas que tenía, fui aprendiendo paso a paso, botella a botella, hasta formar mi propio criterio.
                    </p>
                    <p class="fs-5 mb-4 lh-lg">
                        Con el tiempo, nació en mí la idea de compartir ese aprendizaje y crear una guía sencilla que acompañe a cualquiera que quiera iniciar este recorrido tan apasionante.
                    </p>
                    <p class="fs-5 mb-4 fst-italic text-warning lh-lg">
                        Ojalá que esta pequeña guía te ayude a dar tus primeros pasos en este hermoso camino.
                    </p>
                </div>

                <!-- Sección CTA Centrada para contenido por defecto -->
                <div class="proyecto-cta text-center mt-5 p-5 rounded" style="background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3);">
                    <h4 class="title-font h2 mb-4 text-warning">¿Listo para comenzar tu viaje?</h4>
                    <p class="mb-4 fs-5">Únete a nuestra comunidad de apasionados por el whisky y descubre un mundo de sabores, aromas y experiencias únicas.</p>
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                        <?php if (!$currentUser): ?>
                        <button class="btn btn-gold btn-lg px-4" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="bi bi-person-plus me-2"></i>Unirse al Proyecto
                        </button>
                        <?php else: ?>
                        <a href="#temporadas" class="btn btn-gold btn-lg px-4">
                            <i class="bi bi-play-circle me-2"></i>Continuar Aprendiendo
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-gold btn-lg px-4" onclick="contactWhatsApp()">
                            <i class="bi bi-whatsapp me-2"></i>Consultar por WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

    <!-- Temporadas desde BD -->
    <section id="temporadas" class="py-5" style="background: var(--dark);">
        <div class="container py-5">
            <h2 class="text-center title-font display-5 section-title">EXPERIENCIAS EN TEMPORADAS</h2>
            <p class="text-center text-light mb-5 fs-5">Accede a contenido exclusivo organizado en temporadas temáticas</p>
            
            <div class="row g-5">
                <?php if (isset($seasons) && is_array($seasons) && !empty($seasons)): ?>
                    <?php foreach ($seasons as $season): ?>
                        <div class="col-lg-6">
                            <div class="whisky-card h-100 p-4 p-md-5">
                                <h3 class="title-font h2 mb-4 text-warning"><?= htmlspecialchars(safeArrayAccess($season, 'title', '')) ?></h3>
                                <?php if (!empty(safeArrayAccess($season, 'subtitle', ''))): ?>
                                    <p class="mb-4 fs-5"><?= htmlspecialchars($season['subtitle']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Capítulos de esta temporada -->
                                <?php
                                $seasonId = safeArrayAccess($season, 'id');
                                $seasonChapters = $seasonId ? getChaptersBySeason($seasonId) : [];
                                $freeChapters = array_filter($seasonChapters, function($chapter) {
                                    return safeArrayAccess($chapter, 'is_free', 0) == 1;
                                });
                                $paidChapters = array_filter($seasonChapters, function($chapter) {
                                    return safeArrayAccess($chapter, 'is_free', 0) == 0;
                                });
                                
                                // Verificar progreso del usuario en esta temporada
                                $completedInSeason = 0;
                                if ($currentUser && !empty($userProgress)) {
                                    $completedInSeason = count(array_filter($userProgress, function($progress) use ($seasonId) {
                                        return $progress['season_id'] == $seasonId;
                                    }));
                                }
                                $progressPercentage = count($seasonChapters) > 0 ? round(($completedInSeason / count($seasonChapters)) * 100) : 0;
                                ?>
                                
                                <?php if (!empty($freeChapters)): ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="fw-bold text-warning">Capítulos Gratuitos</h4>
                                            <?php if ($currentUser && $completedInSeason > 0): ?>
                                                <span class="badge bg-warning"><?= $completedInSeason ?>/<?= count($freeChapters) ?> completados</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="progress-container mb-3">
                                            <div class="progress-bar" style="width: <?= $progressPercentage ?>%;"></div>
                                        </div>
                                        <?php foreach ($freeChapters as $chapter): ?>
                                            <div class="mt-3">
                                                <h5 class="fw-bold"><?= htmlspecialchars(safeArrayAccess($chapter, 'title', '')) ?></h5>
                                                <?php if (!empty(safeArrayAccess($chapter, 'subtitle', ''))): ?>
                                                    <p class="mb-2"><?= htmlspecialchars($chapter['subtitle']) ?></p>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-gold btn-sm" onclick="loadChapter(<?= safeArrayAccess($chapter, 'id') ?>)">
                                                    <i class="bi bi-play-circle me-1"></i>
                                                    <?= $currentUser ? 'Ver Capítulo' : 'Ver Gratis' ?>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($paidChapters)): ?>
                                    <div class="mb-4">
                                        <h4 class="fw-bold mb-3 text-warning">Capítulos Premium</h4>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: 0%;"></div>
                                        </div>
                                        <ul class="list-unstyled mt-3">
                                            <?php foreach ($paidChapters as $chapter): ?>
                                                <li class="mb-2">
                                                    <i class="bi bi-lock text-warning me-2"></i>
                                                    <?= htmlspecialchars(safeArrayAccess($chapter, 'title', '')) ?>
                                                    <?php 
                                                    $isCompleted = false;
                                                    if ($currentUser && !empty($userProgress)) {
                                                        $isCompleted = in_array($chapter['id'], array_column($userProgress, 'chapter_id'));
                                                    }
                                                    ?>
                                                    <?php if ($isCompleted): ?>
                                                        <span class="badge bg-success ms-2">Completado</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex flex-column flex-md-row gap-3 mt-4">
                                    <?php if (safeArrayAccess($season, 'requires_payment', 0)): ?>
                                        <?php if ($currentUser): ?>
                                            <button class="btn btn-gold flex-fill" onclick="showPaymentModal(<?= safeArrayAccess($season, 'id') ?>)">
                                                <i class="bi bi-credit-card me-2"></i>Comprar Acceso
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-gold flex-fill" data-bs-toggle="modal" data-bs-target="#registerModal">
                                                <i class="bi bi-person-plus me-2"></i>Regístrate para Acceder
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($currentUser): ?>
                                            <a href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= safeArrayAccess($season, 'id') ?>" class="btn btn-gold flex-fill">
                                                <i class="bi bi-play-circle me-2"></i>
                                                <?= $completedInSeason > 0 ? 'Continuar' : 'Comenzar' ?>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-gold flex-fill" data-bs-toggle="modal" data-bs-target="#registerModal">
                                                <i class="bi bi-person-plus me-2"></i>Regístrate para Acceder
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($freeChapters) && $currentUser): ?>
                                        <button class="btn btn-outline-gold flex-fill" onclick="loadChapter(<?= safeArrayAccess($freeChapters[0], 'id') ?>)">
                                            <i class="bi bi-play-btn me-2"></i>Ver Capítulo Gratis
                                        </button>
                                    <?php elseif (!empty($freeChapters)): ?>
                                        <button class="btn btn-outline-gold flex-fill" data-bs-toggle="modal" data-bs-target="#registerModal">
                                            <i class="bi bi-play-btn me-2"></i>Ver Capítulo Gratis
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($currentUser && $completedInSeason > 0): ?>
                                    <div class="mt-3 text-center">
                                        <div class="progress-ring mx-auto">
                                            <div class="circular-progress" data-percentage="<?= $progressPercentage ?>">
                                                <span class="progress-value">0%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">Progreso en esta temporada</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="whisky-card p-5">
                            <i class="bi bi-clock display-1 text-muted mb-3"></i>
                            <h3 class="title-font h2 mb-3">Próximamente</h3>
                            <p class="fs-5 mb-4">Estamos preparando nuevas temporadas con contenido exclusivo para ti.</p>
                            <?php if (!$currentUser): ?>
                            <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#registerModal">
                                <i class="bi bi-bell me-2"></i>Recibir Notificaciones
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección: Eventos y Catas -->
    <section id="eventos" class="py-5" style="background: var(--primary);">
        <div class="container py-5">
            <h2 class="text-center title-font display-5 section-title">PRÓXIMOS EVENTOS Y CATAS</h2>
            <p class="text-center text-light mb-5 fs-5">Vive experiencias únicas y conoce a otros apasionados del whisky</p>
            
            <div class="row g-4 justify-content-center" id="events-container">
                <?php if (isset($events) && is_array($events) && !empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="whisky-card h-100">
                                <?php if (!empty($event['image_path'])): ?>
                                    <img src="<?= BASE_URL ?>/../../backend/uploads/events/<?= $event['image_path'] ?>" 
                                         alt="<?= htmlspecialchars($event['title']) ?>" 
                                         class="card-img-top event-image" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-calendar-event display-1 text-warning"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body p-4">
                                    <!-- Badge de tipo de evento -->
                                    <?php
                                    $typeBadges = [
                                        'tasting' => ['label' => 'CATA', 'class' => 'bg-primary'],
                                        'workshop' => ['label' => 'WORKSHOP', 'class' => 'bg-success'],
                                        'masterclass' => ['label' => 'MASTERCLASS', 'class' => 'bg-purple'],
                                        'social' => ['label' => 'SOCIAL', 'class' => 'bg-info']
                                    ];
                                    $type = $event['event_type'];
                                    ?>
                                    <span class="badge <?= $typeBadges[$type]['class'] ?> mb-2">
                                        <?= $typeBadges[$type]['label'] ?>
                                    </span>
                                    
                                    <?php if ($event['is_featured']): ?>
                                        <span class="badge bg-danger mb-2">DESTACADO</span>
                                    <?php endif; ?>
                                    
                                    <h3 class="title-font h4 mb-3"><?= htmlspecialchars($event['title']) ?></h3>
                                    
                                    <!-- Fecha y Hora -->
                                    <div class="d-flex align-items-center mb-2 text-warning">
                                        <i class="bi bi-calendar-event me-2"></i>
                                        <strong><?= date('d/m/Y', strtotime($event['event_date'])) ?></strong>
                                    </div>
                                    <div class="d-flex align-items-center mb-2 text-light">
                                        <i class="bi bi-clock me-2"></i>
                                        <?= date('H:i', strtotime($event['event_time'])) ?> 
                                        <?php if ($event['duration']): ?> - <?= $event['duration'] ?><?php endif; ?>
                                    </div>
                                    
                                    <!-- Lugar -->
                                    <?php if ($event['location']): ?>
                                    <div class="d-flex align-items-center mb-2 text-light">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <small><?= htmlspecialchars($event['location']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Dirección -->
                                    <?php if ($event['address']): ?>
                                    <div class="d-flex align-items-center mb-2 text-light">
                                        <i class="bi bi-pin-map me-2"></i>
                                        <small><?= htmlspecialchars($event['address']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Descripción -->
                                    <?php if ($event['description']): ?>
                                    <p class="mb-3 small"><?= nl2br(htmlspecialchars(substr($event['description'], 0, 120))) ?>...</p>
                                    <?php endif; ?>
                                    
                                    <!-- Precio y Cupos -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="h5 text-warning mb-0">
                                            <?php if ($event['price'] > 0): ?>
                                                $<?= number_format($event['price'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-success">Gratuito</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($event['max_participants']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i>
                                            <?= $event['current_participants'] ?: 0 ?>/<?= $event['max_participants'] ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botón de registro -->
                                    <div class="d-grid">
                                        <?php if ($event['registration_link']): ?>
                                            <a href="<?= htmlspecialchars($event['registration_link']) ?>" 
                                               target="_blank" 
                                               class="btn btn-gold">
                                                <i class="bi bi-whatsapp me-2"></i>Reservar Mi Lugar
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-gold" onclick="contactWhatsApp('Hola! Me interesa el evento: <?= htmlspecialchars($event['title']) ?> - Fecha: <?= date('d/m/Y', strtotime($event['event_date'])) ?>')">
                                                <i class="bi bi-whatsapp me-2"></i>Consultar por Evento
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Mostrar eventos por defecto cuando no hay eventos futuros -->
                    <div class="col-lg-4 col-md-6">
                        <div class="whisky-card h-100 text-center p-4">
                            <div class="event-placeholder mb-4">
                                <i class="bi bi-calendar-plus display-1 text-warning"></i>
                            </div>
                            <h3 class="title-font h4 mb-3">Nuevos Eventos Próximamente</h3>
                            <p class="mb-4">Estamos organizando nuevas experiencias de cata exclusivas para ti.</p>
                            <button class="btn btn-outline-gold" onclick="contactWhatsApp('Hola! Quiero recibir información sobre próximos eventos y catas.')">
                                <i class="bi bi-bell me-2"></i>Recibir Notificaciones
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6">
                        <div class="whisky-card h-100 text-center p-4">
                            <div class="event-placeholder mb-4">
                                <i class="bi bi-stars display-1 text-warning"></i>
                            </div>
                            <h3 class="title-font h4 mb-3">Cata Personalizada</h3>
                            <p class="mb-4">¿Tienes un grupo? Organizamos catas privadas según tus preferencias.</p>
                            <button class="btn btn-outline-gold" onclick="contactWhatsApp('Hola! Estoy interesado en organizar una cata privada para mi grupo.')">
                                <i class="bi bi-star me-2"></i>Solicitar Cata Privada
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6">
                        <div class="whisky-card h-100 text-center p-4">
                            <div class="event-placeholder mb-4">
                                <i class="bi bi-gift display-1 text-warning"></i>
                            </div>
                            <h3 class="title-font h4 mb-3">Eventos Corporativos</h3>
                            <p class="mb-4">Experiencias de cata para empresas y eventos especiales.</p>
                            <button class="btn btn-outline-gold" onclick="contactWhatsApp('Hola! Necesito información sobre eventos corporativos de cata.')">
                                <i class="bi bi-building me-2"></i>Eventos Corporativos
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Calendario de Eventos -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="whisky-card p-4">
                        <h3 class="title-font h3 text-center mb-4">Calendario de Eventos</h3>
                        <div class="text-center">
                            <p class="mb-4">Mantente informado sobre todos nuestros eventos programados. Recibe notificaciones de nuevos eventos.</p>
                            <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                                <button class="btn btn-gold" onclick="contactWhatsApp('Hola! Quiero conocer el calendario completo de eventos y recibir notificaciones.')">
                                    <i class="bi bi-calendar-week me-2"></i>Consultar Calendario
                                </button>
                                <button class="btn btn-outline-gold" onclick="contactWhatsApp('Hola! Quiero proponer una fecha para un evento/cata.')">
                                    <i class="bi bi-lightbulb me-2"></i>Sugerir un Evento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección: Alianzas -->
    <section id="alianzas" class="py-5" style="background: var(--primary);">
        <div class="container py-5">
            <h2 class="text-center title-font display-5 section-title">ALIANZAS ESTRATÉGICAS</h2>
            <p class="text-center text-light mb-5 fs-5">Colaboraciones que enriquecen nuestra comunidad whiskyera</p>
            
            <div class="row g-4 justify-content-center" id="alliances-container">
                <?php if (isset($alliances) && is_array($alliances) && !empty($alliances)): ?>
                    <?php foreach ($alliances as $alliance): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="whisky-card h-100 p-4 text-center">
                                <?php if (!empty(safeArrayAccess($alliance, 'logo', ''))): ?>
                                    <img src="<?= BASE_URL ?>/../../backend/uploads/alliances/<?= $alliance['logo'] ?>" 
                                         alt="<?= htmlspecialchars(safeArrayAccess($alliance, 'name', '')) ?>" 
                                         class="img-fluid mb-4 alliance-logo" style="max-height: 100px;">
                                <?php else: ?>
                                    <div class="alliance-placeholder mb-4">
                                        <i class="bi bi-handshake display-1 text-warning"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="title-font h4 mb-3"><?= htmlspecialchars(safeArrayAccess($alliance, 'name', '')) ?></h3>
                                
                                <?php if (!empty(safeArrayAccess($alliance, 'description', ''))): ?>
                                    <p class="mb-3"><?= htmlspecialchars($alliance['description']) ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty(safeArrayAccess($alliance, 'website', ''))): ?>
                                    <a href="<?= htmlspecialchars($alliance['website']) ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-gold btn-sm">
                                        <i class="bi bi-globe me-1"></i>Visitar Sitio
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Mostrar El Camino del Whisky como alianza por defecto -->
                    <div class="col-lg-4 col-md-6">
                        <div class="whisky-card h-100 p-4 text-center">
                            <div class="alliance-placeholder mb-4">
                                <i class="bi bi-droplet-half display-1 text-warning"></i>
                            </div>
                            <h3 class="title-font h4 mb-3">EL CAMINO DEL WHISKY</h3>
                            <p class="mb-3">Tu guía definitiva en el mundo del whisky. Desde los fundamentos hasta la maestría en cata.</p>
                            <button class="btn btn-outline-gold btn-sm" onclick="contactWhatsApp()">
                                <i class="bi bi-whatsapp me-1"></i>Contactar
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-12 text-center mt-4">
                        <p class="text-light">¿Quieres ser nuestro aliado?</p>
                        <button class="btn btn-gold" onclick="contactWhatsApp('Hola! Estoy interesado en formar una alianza con El Camino del Whisky.')">
                            <i class="bi bi-handshake me-2"></i>Proponer Alianza
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5" style="background: var(--dark);">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-droplet-half fs-2 me-2" style="color: #D4AF37;"></i>
                        <h3 class="title-font h4 mb-0">EL CAMINO DEL WHISKY</h3>
                    </div>
                    <p class="mb-4">Descubre el arte de la cata con nuestro experto catador. Un viaje sensorial a través de los mejores whiskies del mundo.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-outline-gold btn-sm rounded-circle p-2">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="btn btn-outline-gold btn-sm rounded-circle p-2">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://wa.me/<?= safeArrayAccess($whatsappConfig, 'number', '') ?>" class="btn btn-outline-gold btn-sm rounded-circle p-2">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-warning mb-4">Enlaces</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#inicio" class="text-light text-decoration-none">Inicio</a></li>
                        <li class="mb-2"><a href="#proyecto" class="text-light text-decoration-none">El Proyecto</a></li>
                        <li class="mb-2"><a href="#temporadas" class="text-light text-decoration-none">Temporadas</a></li>
                        <li class="mb-2"><a href="#eventos" class="text-light text-decoration-none">Eventos</a></li>
                        <li class="mb-2"><a href="#alianzas" class="text-light text-decoration-none">Alianzas</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-warning mb-4">Contacto</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-envelope text-warning me-2 mt-1"></i>
                            <span>info@elcaminodelwhisky.com</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-whatsapp text-warning me-2 mt-1"></i>
                            <span>+<?= safeArrayAccess($whatsappConfig, 'number', '') ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-geo-alt text-warning me-2 mt-1"></i>
                            <span>CDE - Paraguay</span>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="text-warning mb-4">Newsletter</h5>
                    <p class="mb-3">Suscríbete para recibir actualizaciones y ofertas exclusivas.</p>
                    <div class="d-flex">
                        <input type="email" class="form-control me-2" placeholder="Tu correo">
                        <button class="btn btn-gold">Enviar</button>
                    </div>
                </div>
            </div>
            <hr class="my-5" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; 2025 El Camino del Whisky. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Incluir Modals -->
    <?php include ROOT_DIR . '/frontend/includes/modals.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS Personalizado -->
    <script src="<?= BASE_URL ?>/../src/js/main.js"></script>
    
    <!-- Configuración global -->
    <script>
    const SITE_CONFIG = {
        whatsapp: {
            number: '<?php echo safeArrayAccess($whatsappConfig, 'number', ''); ?>',
            defaultMessage: '<?php echo safeArrayAccess($whatsappConfig, 'default_message', ''); ?>'
        },
        bank: {
            name: '<?php echo safeArrayAccess($bankConfig, 'bank_name', ''); ?>',
            account: '<?php echo safeArrayAccess($bankConfig, 'account_number', ''); ?>',
            alias: '<?php echo safeArrayAccess($bankConfig, 'alias', ''); ?>',
            holder: '<?php echo safeArrayAccess($bankConfig, 'account_holder', ''); ?>',
            ruc: '<?php echo safeArrayAccess($bankConfig, 'ruc', ''); ?>'
        },
        user: {
            isLoggedIn: <?= $currentUser ? 'true' : 'false' ?>,
            <?php if ($currentUser): ?>
            id: <?= $currentUser['id'] ?>,
            username: '<?= $currentUser['username'] ?>',
            email: '<?= $currentUser['email'] ?>',
            role: '<?= $currentUser['role'] ?>'
            <?php endif; ?>
        }
    };

    // Funciones globales
    function contactWhatsApp(customMessage = '') {
        const message = customMessage || SITE_CONFIG.whatsapp.defaultMessage;
        const url = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
    }

    function loadChapter(chapterId) {
        window.location.href = '<?= BASE_URL ?>/../src/pages/chapter.php?id=' + chapterId;
    }

    function showPaymentModal(seasonId) {
        if (!SITE_CONFIG.user.isLoggedIn) {
            $('#paymentModal').modal('hide');
            $('#loginModal').modal('show');
            return;
        }
        
        if (seasonId) {
            $('#paymentSeasonId').val(seasonId);
        }
        
        $('#paymentModal').modal('show');
    }

    // Animación de progreso circular
    document.addEventListener('DOMContentLoaded', function() {
        const progressElements = document.querySelectorAll('.circular-progress');
        
        progressElements.forEach(progress => {
            const percentage = parseInt(progress.getAttribute('data-percentage'));
            const valueElement = progress.querySelector('.progress-value');
            
            // Configurar el conic-gradient
            progress.style.background = `conic-gradient(#D4AF37 ${percentage * 3.6}deg, rgba(255,255,255,0.1) 0deg)`;
            
            // Animación del porcentaje
            let current = 0;
            const interval = setInterval(() => {
                if (current >= percentage) {
                    clearInterval(interval);
                } else {
                    current++;
                    valueElement.textContent = current + '%';
                }
            }, 20);
        });

        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
    </script>
</body>
</html>