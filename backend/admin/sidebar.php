<?php
// Sidebar para el panel de administración

// Determinar la página actual
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar">
    <div class="d-flex flex-column p-3">
        <div class="text-center mb-4">
            <i class="bi bi-droplet-half display-6 text-warning"></i>
            <h5 class="text-warning mt-2">Admin Panel</h5>
            <small class="text-light">El Camino del Whisky</small>
            <div class="mt-2">
                <small class="text-muted">v1.0</small>
            </div>
        </div>
        
        <ul class="nav nav-pills flex-column mb-4">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            
            <!-- Gestión de Contenido -->
            <li class="nav-item">
                <span class="nav-link text-warning fw-bold small text-uppercase mt-3">Gestión de Contenido</span>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'seasons.php' ? 'active' : '' ?>" href="seasons.php">
                    <i class="bi bi-collection-play me-2"></i>Temporadas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'chapters.php' ? 'active' : '' ?>" href="chapters.php">
                    <i class="bi bi-play-btn me-2"></i>Capítulos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'chapter_images.php' ? 'active' : '' ?>" href="chapter_images.php">
                    <i class="bi bi-image me-2"></i>Imágenes Capítulos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                    <i class="bi bi-tags me-2"></i>Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'alliances.php' ? 'active' : '' ?>" href="alliances.php">
                    <i class="bi bi-handshake me-2"></i>Alianzas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="events.php">
                    <i class="bi bi-calendar-event me-2"></i>Eventos y Catas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'slider.php' ? 'active' : '' ?>" href="slider.php">
                    <i class="bi bi-images me-2"></i>Slider Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'project.php' ? 'active' : '' ?>" href="project.php">
                    <i class="bi bi-journal-text me-2"></i>Contenido del Proyecto
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'active' : '' ?>" href="pricing.php">
                    <i class="bi bi-currency-dollar me-2"></i>Gestión de Precios
                </a>
            </li>
            
            <!-- Gestión de Usuarios -->
            <li class="nav-item">
                <span class="nav-link text-warning fw-bold small text-uppercase mt-3">Gestión de Usuarios</span>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'users.php' ? 'active' : '' ?>" href="users.php">
                    <i class="bi bi-people me-2"></i>Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'payments.php' ? 'active' : '' ?>" href="payments.php">
                    <i class="bi bi-credit-card me-2"></i>Pagos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'comments.php' ? 'active' : '' ?>" href="comments.php">
                    <i class="bi bi-chat-dots me-2"></i>Comentarios
                </a>
            </li>
            
            <!-- Comunicación -->
            <li class="nav-item">
                <span class="nav-link text-warning fw-bold small text-uppercase mt-3">Comunicación</span>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'contacts.php' ? 'active' : '' ?>" href="contacts.php">
                    <i class="bi bi-envelope me-2"></i>Contactos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'newsletter.php' ? 'active' : '' ?>" href="newsletter.php">
                    <i class="bi bi-mailbox me-2"></i>Newsletter
                </a>
            </li>
            
            <!-- Configuración -->
            <li class="nav-item">
                <span class="nav-link text-warning fw-bold small text-uppercase mt-3">Configuración</span>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <i class="bi bi-gear me-2"></i>Configuración General
                </a>
            </li>
        </ul>
        
        <!-- Información del Usuario -->
        <div class="mt-auto p-3 bg-dark rounded">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle text-warning me-2"></i>
                <div>
                    <small class="text-light d-block"><strong><?= $_SESSION['user_name'] ?? 'Admin' ?></strong></small>
                    <small class="text-muted d-block"><?= ucfirst($_SESSION['user_role'] ?? 'admin') ?></small>
                </div>
            </div>
            <div class="d-grid">
                <a class="btn btn-outline-danger btn-sm" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
        
        <!-- Enlace al Sitio Principal -->
        <div class="mt-2 text-center">
            <a href="../../../frontend/public/index.php" target="_blank" class="btn btn-outline-warning btn-sm w-100">
                <i class="bi bi-eye me-1"></i>Ver Sitio
            </a>
        </div>
    </div>
</div>