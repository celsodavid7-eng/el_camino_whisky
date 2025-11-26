<?php
// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ecdw/frontend/public');
}

// Incluir database.php
require_once ROOT_DIR . '/backend/config/database.php';

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

// Obtener temporadas publicadas para el dropdown
function getPublishedSeasonsForNavbar() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(c.id) as chapter_count,
                   SUM(CASE WHEN c.is_free = 1 THEN 1 ELSE 0 END) as free_chapters
            FROM seasons s
            LEFT JOIN chapters c ON s.id = c.season_id AND c.is_published = 1
            WHERE s.is_published = 1 
            GROUP BY s.id
            ORDER BY s.display_order ASC, s.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting seasons for navbar: " . $e->getMessage());
        return [];
    }
}

// Obtener capítulos por temporada para el dropdown
function getChaptersBySeasonForNavbar($seasonId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, subtitle, is_free, chapter_number, duration
            FROM chapters 
            WHERE season_id = ? AND is_published = 1 
            ORDER BY chapter_number ASC, display_order ASC
        ");
        $stmt->execute([$seasonId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting chapters for navbar: " . $e->getMessage());
        return [];
    }
}

// Obtener mensajes no leídos para el usuario actual
function getUnreadMessagesCount() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM private_messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result ? $result['unread_count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting unread messages count: " . $e->getMessage());
        return 0;
    }
}

$seasonsForNavbar = getPublishedSeasonsForNavbar();
$unreadMessagesCount = isset($_SESSION['user_id']) ? getUnreadMessagesCount() : 0;
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-droplet-half me-2" style="color: #D4AF37;"></i>
            <span class="title-font" style="color: #ffffff;">EL CAMINO DEL WHISKY</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= BASE_URL ?>/#inicio" style="color: #ffffff;">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/#proyecto" style="color: #e0e0e0;">El Proyecto</a>
                </li>
               
                <!-- Dropdown de Temporadas -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #e0e0e0;">
                        <i class="bi bi-collection-play me-1"></i>Temporadas
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-lg-end">
                        <?php if (!empty($seasonsForNavbar)): ?>
                            <?php foreach ($seasonsForNavbar as $season): ?>
                                <?php
                                $seasonChapters = getChaptersBySeasonForNavbar($season['id']);
                                $hasFreeChapters = false;
                                foreach ($seasonChapters as $chapter) {
                                    if ($chapter['is_free']) {
                                        $hasFreeChapters = true;
                                        break;
                                    }
                                }
                                ?>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" style="color: #ffffff;">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <span>
                                                <i class="bi bi-play-circle me-2"></i>
                                                <?= htmlspecialchars($season['title']) ?>
                                            </span>
                                            <?php if ($hasFreeChapters): ?>
                                                <span class="badge bg-warning text-dark ms-2">Gratis</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu dropdown-submenu">
                                        <!-- Información de la temporada -->
                                        <li>
                                            <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/season_detail.php?id=<?= $season['id'] ?>" style="color: #b0b0b0; font-size: 0.9rem;">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <?= htmlspecialchars($season['subtitle'] ?? 'Descripción no disponible') ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- Capítulos de la temporada -->
                                        <?php if (!empty($seasonChapters)): ?>
                                            <?php foreach ($seasonChapters as $chapter): ?>
                                                <li>
                                                    <a class="dropdown-item chapter-item" 
                                                       href="<?= BASE_URL ?>/../src/pages/chapter.php?id=<?= $chapter['id'] ?>" 
                                                       style="color: #e0e0e0;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <?php if ($chapter['is_free']): ?>
                                                                    <i class="bi bi-unlock text-success me-2"></i>
                                                                <?php else: ?>
                                                                    <i class="bi bi-lock text-warning me-2"></i>
                                                                <?php endif; ?>
                                                                <span class="chapter-title">
                                                                    Cap. <?= $chapter['chapter_number'] ?>: 
                                                                    <?= htmlspecialchars($chapter['title']) ?>
                                                                </span>
                                                            </div>
                                                            <?php if ($chapter['is_free']): ?>
                                                                <span class="badge bg-success badge-sm" style="background: #28a745 !important;">Gratis</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($chapter['subtitle'])): ?>
                                                            <small class="d-block ms-4 mt-1" style="color: #b0b0b0;">
                                                                <?= htmlspecialchars($chapter['subtitle']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($chapter['duration'])): ?>
                                                            <small class="d-block ms-4" style="color: #b0b0b0;">
                                                                <i class="bi bi-clock me-1"></i><?= $chapter['duration'] ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>
                                                <a class="dropdown-item" href="#" style="color: #b0b0b0;">
                                                    <i class="bi bi-clock me-2"></i>
                                                    Próximamente...
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>
                                <a class="dropdown-item" href="#" style="color: #b0b0b0;">
                                    <i class="bi bi-clock me-2"></i>
                                    Próximamente nuevas temporadas...
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Enlace a todas las temporadas -->
                        <li>
                            <a class="dropdown-item fw-bold" href="<?= BASE_URL ?>/../src/pages/my_courses.php" style="color: #D4AF37;">
                                <i class="bi bi-grid-3x3-gap me-2"></i>Ver Todas las Temporadas
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/#alianzas" style="color: #e0e0e0;">Alianzas</a>
                </li>
                
                <?php if ($currentUser): ?>
                <!-- Menú de Usuario Logueado -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="color: #ffffff;">
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($currentUser['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/profile.php" style="color: #e0e0e0;">
                                <i class="bi bi-person me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/my_courses.php" style="color: #e0e0e0;">
                                <i class="bi bi-play-circle me-2"></i>Mi Camino
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/messages.php" style="color: #e0e0e0;">
                                <i class="bi bi-chat-dots me-2"></i>Mensajes
                                <?php if ($unreadMessagesCount > 0): ?>
                                    <span class="badge bg-warning ms-2" id="unreadMessagesCount" style="background: #D4AF37 !important; color: #000;"><?= $unreadMessagesCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/../../backend/admin/" style="color: #D4AF37;">
                                    <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="#" data-logout style="color: #dc3545;">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Menú para Usuarios No Logueados -->
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal" style="color: #e0e0e0;">
                        <i class="bi bi-person me-1"></i>Ingresar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#registerModal" style="color: #e0e0e0;">
                        <i class="bi bi-person-plus me-1"></i>Registrarse
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Estilos para el navbar */
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

#navbar {
    background: rgba(13, 13, 13, 0.95) !important;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

#navbar.navbar-scrolled {
    background: rgba(13, 13, 13, 0.98) !important;
    padding: 10px 0;
    box-shadow: 0 2px 20px rgba(0,0,0,0.3);
}

.navbar-brand {
    font-size: 1.5rem;
    font-weight: 700;
}

.nav-link {
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: var(--gold-primary) !important;
    transform: translateY(-1px);
}

.nav-link.active {
    color: var(--gold-primary) !important;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 2px;
    background: var(--gold-primary);
    border-radius: 2px;
}

/* Estilos para el dropdown de temporadas */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu .dropdown-toggle::after {
    transform: rotate(-90deg);
    position: absolute;
    right: 10px;
    top: 50%;
    margin-top: -6px;
}

.dropdown-submenu:hover > .dropdown-menu {
    display: block;
    left: 100%;
    top: 0;
    margin-left: 0;
    margin-top: -1px;
    border-radius: 0 10px 10px 10px;
}

.dropdown-menu-lg-end {
    min-width: 380px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    background: var(--bg-card) !important;
}

.dropdown-submenu .dropdown-menu {
    min-width: 320px;
    border-radius: 10px;
    background: var(--bg-card) !important;
}

.chapter-item {
    padding: 10px 15px;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    border-radius: 5px;
    margin: 2px 5px;
}

.chapter-item:hover {
    background-color: rgba(212, 175, 55, 0.1);
    border-left-color: var(--gold-primary);
    transform: translateX(5px);
    color: var(--text-primary) !important;
}

.chapter-title {
    font-size: 0.9rem;
    font-weight: 500;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 3px 6px;
}

.dropdown-item {
    transition: all 0.3s ease;
    border-radius: 5px;
    margin: 2px 5px;
}

.dropdown-item:hover {
    background-color: rgba(212, 175, 55, 0.1);
    transform: translateX(3px);
    color: var(--text-primary) !important;
}

.dropdown-divider {
    border-color: var(--border-color);
}

/* Responsive */
@media (max-width: 768px) {
    .dropdown-submenu:hover > .dropdown-menu {
        position: static;
        margin-left: 15px;
        border-radius: 10px;
    }
    
    .dropdown-menu-lg-end {
        min-width: 280px;
    }
    
    .navbar-nav {
        text-align: center;
    }
    
    .nav-link.active::after {
        display: none;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 1.2rem;
    }
    
    .dropdown-menu-lg-end {
        min-width: 250px;
    }
}

/* Animaciones */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu {
    animation: slideDown 0.3s ease;
}

/* Badge de notificaciones */
#unreadMessagesCount {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

/* Mejoras de colores para mejor contraste */
.dropdown-menu-dark {
    --bs-dropdown-color: #e0e0e0;
    --bs-dropdown-bg: #1a1a1a;
    --bs-dropdown-border-color: #444;
    --bs-dropdown-link-color: #e0e0e0;
    --bs-dropdown-link-hover-color: #ffffff;
    --bs-dropdown-link-hover-bg: rgba(212, 175, 55, 0.1);
    --bs-dropdown-link-active-color: #000000;
    --bs-dropdown-link-active-bg: #D4AF37;
}

.navbar-toggler {
    border-color: var(--border-color);
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
}
</style>

<script>
// JavaScript para el navbar
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('navbar');
    
    // Efecto de scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });

    // Manejo de dropdowns en móviles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                const parent = this.closest('.dropdown-submenu');
                if (parent) {
                    e.preventDefault();
                    const submenu = parent.querySelector('.dropdown-menu');
                    if (submenu) {
                        submenu.classList.toggle('show');
                    }
                }
            }
        });
    });

    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    // Cerrar navbar en móviles
                    const navbarCollapse = document.getElementById('navbarNav');
                    if (navbarCollapse.classList.contains('show')) {
                        new bootstrap.Collapse(navbarCollapse).toggle();
                    }
                    
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Sistema de logout - CORREGIDO
    document.querySelectorAll('[data-logout]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cerrando sesión...';
                this.disabled = true;
                
                const formData = new FormData();
                formData.append('action', 'logout');
                
                fetch('<?= BASE_URL ?>/../../backend/api/auth.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Usar función de toast si existe, sino usar alert
                        if (typeof showToast === 'function') {
                            showToast('Sesión cerrada', 'Has cerrado sesión correctamente', 'success');
                        } else {
                            alert('Sesión cerrada correctamente');
                        }
                        
                        // Redirección forzada para limpiar cache
                        setTimeout(() => {
                            window.location.href = '<?= BASE_URL ?>/?logout=' + new Date().getTime();
                        }, 1000);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast('Error', result.message || 'Error al cerrar sesión', 'error');
                        } else {
                            alert('Error: ' + (result.message || 'Error al cerrar sesión'));
                        }
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error', 'Error de conexión', 'error');
                    } else {
                        alert('Error de conexión');
                    }
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });

    // Función para mostrar notificaciones (compatible con modals.php)
    function showToast(title, message, type = 'info') {
        // Usar la función del modal si existe, sino usar alert nativo
        if (typeof window.showToast === 'function') {
            window.showToast(title, message, type);
        } else {
            alert(`${title}: ${message}`);
        }
    }
});

// Actualizar contador de mensajes no leídos
function updateUnreadMessagesCount() {
    fetch('<?= BASE_URL ?>/../../backend/api/messages.php?action=get_unread_count')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const badge = document.getElementById('unreadMessagesCount');
                if (badge) {
                    if (result.count > 0) {
                        badge.textContent = result.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Error updating message count:', error));
}

// Actualizar cada 30 segundos si el usuario está logueado
<?php if ($currentUser): ?>
setInterval(updateUnreadMessagesCount, 30000);
<?php endif; ?>
</script>