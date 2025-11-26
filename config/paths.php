<?php
// config/paths.php - ARCHIVO CENTRAL DE CONFIGURACIÓN
define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
define('BASE_URL', '/ecdw/frontend/src');
define('BACKEND_URL', '/ecdw/backend');
define('UPLOADS_URL', '/ecdw/uploads');

// Configuración para desarrollo
define('IS_DEV', true);

// Incluir autoloader si es necesario
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
}
?>