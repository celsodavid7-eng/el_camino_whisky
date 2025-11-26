<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $configKey = substr($key, 7);
                $stmt = $pdo->prepare("UPDATE site_config SET config_value = ? WHERE config_key = ?");
                $stmt->execute([$value, $configKey]);
            }
        }
        $message = '<div class="alert alert-success fw-bold">Configuración actualizada exitosamente</div>';
    }
}

// Obtener configuración actual
$stmt = $pdo->query("SELECT * FROM site_config ORDER BY config_key");
$configs = $stmt->fetchAll();

// Organizar configuración por grupos
$configGroups = [
    'whatsapp' => [],
    'bank' => [],
    'site' => []
];

foreach ($configs as $config) {
    if (strpos($config['config_key'], 'whatsapp') === 0) {
        $configGroups['whatsapp'][] = $config;
    } elseif (strpos($config['config_key'], 'bank') === 0) {
        $configGroups['bank'][] = $config;
    } else {
        $configGroups['site'][] = $config;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Admin Panel</title>
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
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active { 
            background: var(--gold-primary); 
            color: #000; 
            transform: translateX(5px);
            font-weight: 600;
        }
        
        .config-group { 
            background: var(--bg-card-light); 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .config-group:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            border-color: var(--gold-primary);
        }
        
        .config-group h5 { 
            border-bottom: 3px solid var(--gold-primary); 
            padding-bottom: 12px; 
            margin-bottom: 25px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Mejoras de contraste y legibilidad */
        .title-font {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            color: var(--gold-primary);
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 500;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: var(--bg-card);
            border-color: var(--gold-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.3rem rgba(212, 175, 55, 0.2);
            font-weight: 500;
        }
        
        .form-text {
            color: var(--text-muted);
            font-weight: 400;
            font-size: 0.875rem;
            margin-top: 6px;
        }
        
        .text-warning {
            color: var(--gold-primary) !important;
            font-weight: 600;
        }
        
        .text-muted {
            color: var(--text-muted) !important;
            font-weight: 400;
        }
        
        /* Botones mejorados */
        .btn-warning {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            border: none;
            color: #000;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(212, 175, 55, 0.3);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, var(--gold-secondary), var(--gold-primary));
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(212, 175, 55, 0.4);
        }
        
        /* Alertas mejoradas */
        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        /* Mejora de iconos */
        .bi {
            opacity: 0.9;
        }
        
        /* Mejora de espaciado */
        .mb-3 {
            margin-bottom: 1.5rem !important;
        }
        
        .mb-4 {
            margin-bottom: 2rem !important;
        }
        
        /* Mejora del placeholder */
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
            font-weight: 400;
        }
        
        /* Efectos de hover para grupos de configuración */
        .config-group .form-control {
            background: var(--bg-card);
        }
        
        .config-group .form-control:focus {
            background: var(--bg-card);
        }
        
        /* Mejora de los títulos de grupos */
        .config-group h5 i {
            color: var(--gold-primary);
            margin-right: 10px;
        }
        
        /* Mejora del contenedor principal */
        .ms-sm-auto {
            background: var(--bg-dark);
        }
        
        /* Mejora de los textos en general */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        /* Mejora del footer de botones */
        .text-end {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .config-group {
                padding: 20px 15px;
            }
            
            .btn-warning {
                width: 100%;
                padding: 15px;
            }
            
            .col-md-6 {
                margin-bottom: 1rem;
            }
        }
        
        /* Animación sutil para los grupos */
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
        
        .config-group {
            animation: fadeInUp 0.5s ease-out;
        }
        
        .config-group:nth-child(1) { animation-delay: 0.1s; }
        .config-group:nth-child(2) { animation-delay: 0.2s; }
        .config-group:nth-child(3) { animation-delay: 0.3s; }
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
                    <h2 class="title-font"><i class="bi bi-gear me-2"></i>Configuración del Sitio</h2>
                </div>

                <?= $message ?>

                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">

                    <!-- Configuración de WhatsApp -->
                    <div class="config-group">
                        <h5 class="text-warning mb-4 fw-bold">
                            <i class="bi bi-whatsapp me-2"></i>Configuración de WhatsApp
                        </h5>
                        <div class="row">
                            <?php foreach ($configGroups['whatsapp'] as $config): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><?= ucfirst(str_replace('_', ' ', $config['config_key'])) ?></label>
                                <input type="text" class="form-control fw-medium" name="config_<?= $config['config_key'] ?>" 
                                       value="<?= htmlspecialchars($config['config_value']) ?>">
                                <?php if ($config['description']): ?>
                                    <div class="form-text text-muted fw-medium"><?= $config['description'] ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Configuración Bancaria -->
                    <div class="config-group">
                        <h5 class="text-warning mb-4 fw-bold">
                            <i class="bi bi-bank me-2"></i>Configuración Bancaria
                        </h5>
                        <div class="row">
                            <?php foreach ($configGroups['bank'] as $config): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><?= ucfirst(str_replace('_', ' ', $config['config_key'])) ?></label>
                                <input type="text" class="form-control fw-medium" name="config_<?= $config['config_key'] ?>" 
                                       value="<?= htmlspecialchars($config['config_value']) ?>">
                                <?php if ($config['description']): ?>
                                    <div class="form-text text-muted fw-medium"><?= $config['description'] ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Configuración del Sitio -->
                    <div class="config-group">
                        <h5 class="text-warning mb-4 fw-bold">
                            <i class="bi bi-globe me-2"></i>Configuración del Sitio
                        </h5>
                        <div class="row">
                            <?php foreach ($configGroups['site'] as $config): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold"><?= ucfirst(str_replace('_', ' ', $config['config_key'])) ?></label>
                                <input type="text" class="form-control fw-medium" name="config_<?= $config['config_key'] ?>" 
                                       value="<?= htmlspecialchars($config['config_value']) ?>">
                                <?php if ($config['description']): ?>
                                    <div class="form-text text-muted fw-medium"><?= $config['description'] ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning btn-lg fw-bold">
                            <i class="bi bi-save me-2"></i>Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Efecto de foco mejorado para inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            // Validación básica del formulario
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredInputs = document.querySelectorAll('.form-control[required]');
                
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Mostrar mensaje de error
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger fw-bold';
                    alertDiv.textContent = 'Por favor, complete todos los campos requeridos.';
                    form.insertBefore(alertDiv, form.firstChild);
                    
                    // Auto-remover el mensaje después de 5 segundos
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            });
        });
    </script>
</body>
</html>