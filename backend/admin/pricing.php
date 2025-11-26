<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_pricing':
                $season_id = !empty($_POST['season_id']) ? $_POST['season_id'] : null;
                $chapter_price = floatval($_POST['chapter_price'] ?? 0);
                $season_price = floatval($_POST['season_price'] ?? 0);
                $bundle_price = floatval($_POST['bundle_price'] ?? 0);
                
                // Verificar si ya existe configuración para esta temporada
                $checkStmt = $pdo->prepare("SELECT id FROM payment_configs WHERE season_id " . ($season_id ? "= ?" : "IS NULL"));
                $checkParams = $season_id ? [$season_id] : [];
                $checkStmt->execute($checkParams);
                $existingConfig = $checkStmt->fetch();
                
                if ($existingConfig) {
                    // Actualizar configuración existente
                    $stmt = $pdo->prepare("
                        UPDATE payment_configs 
                        SET chapter_price = ?, season_price = ?, bundle_price = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE season_id " . ($season_id ? "= ?" : "IS NULL")
                    );
                    $params = [$chapter_price, $season_price, $bundle_price];
                    if ($season_id) $params[] = $season_id;
                } else {
                    // Crear nueva configuración
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_configs (season_id, chapter_price, season_price, bundle_price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $params = [$season_id, $chapter_price, $season_price, $bundle_price];
                }
                
                if ($stmt->execute($params)) {
                    $message = '<div class="alert alert-success fw-bold">Configuración de precios actualizada exitosamente</div>';
                } else {
                    $message = '<div class="alert alert-danger fw-bold">Error al actualizar precios</div>';
                }
                break;
                
            case 'toggle_active':
                $id = $_POST['id'];
                $is_active = $_POST['is_active'] ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE payment_configs SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$is_active, $id])) {
                    $message = '<div class="alert alert-success fw-bold">Estado actualizado</div>';
                } else {
                    $message = '<div class="alert alert-danger fw-bold">Error al actualizar estado</div>';
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Pricing error: " . $e->getMessage());
        $message = '<div class="alert alert-danger fw-bold">Error: ' . $e->getMessage() . '</div>';
    }
}

// Obtener temporadas
$seasons = $pdo->query("SELECT id, title FROM seasons WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

// Obtener configuración de precios
$pricingConfigs = $pdo->query("
    SELECT pc.*, s.title as season_title 
    FROM payment_configs pc 
    LEFT JOIN seasons s ON pc.season_id = s.id 
    ORDER BY 
        CASE WHEN pc.season_id IS NULL THEN 0 ELSE 1 END,
        s.title ASC
")->fetchAll();

// Organizar configuraciones
$globalConfig = null;
$seasonConfigs = [];

foreach ($pricingConfigs as $config) {
    if ($config['season_id'] === null) {
        $globalConfig = $config;
    } else {
        $seasonConfigs[$config['season_id']] = $config;
    }
}

// Si no existe configuración global, crear una por defecto
if (!$globalConfig) {
    $globalConfig = [
        'id' => null,
        'season_id' => null,
        'chapter_price' => 0,
        'season_price' => 0,
        'bundle_price' => 0,
        'is_active' => 1,
        'season_title' => 'Configuración Global'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Precios - Admin Panel</title>
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
        }
        
        .config-group h5 { 
            border-bottom: 3px solid var(--gold-primary); 
            padding-bottom: 12px; 
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .table-dark { 
            background: var(--bg-card); 
            color: var(--text-primary);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-dark th {
            background: var(--bg-card-light);
            color: var(--text-secondary);
            font-weight: 600;
            border-color: var(--border-color);
        }
        
        .table-dark td {
            border-color: var(--border-color);
            vertical-align: middle;
        }
        
        .form-control {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-control:focus {
            background: var(--bg-card);
            border-color: var(--gold-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.3rem rgba(212, 175, 55, 0.2);
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            border: none;
            color: #000;
            font-weight: 700;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, var(--gold-secondary), var(--gold-primary));
            color: #000;
        }
        
        .badge-active { background: #28a745; }
        .badge-inactive { background: #6c757d; }
        
        .title-font {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            color: var(--gold-primary);
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
                    <h2 class="title-font"><i class="bi bi-currency-dollar me-2"></i>Gestión de Precios</h2>
                </div>

                <?= $message ?>

                <!-- Configuración Global -->
                <div class="config-group">
                    <h5 class="text-warning mb-4 fw-bold">
                        <i class="bi bi-globe me-2"></i>Configuración Global de Precios
                    </h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_pricing">
                        <input type="hidden" name="season_id" value="">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Precio por Capítulo (USD)</label>
                                <input type="number" class="form-control fw-medium" name="chapter_price" 
                                       value="<?= $globalConfig['chapter_price'] ?>" step="0.01" min="0">
                                <div class="form-text fw-medium">Precio para comprar capítulos individuales</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Precio por Temporada (USD)</label>
                                <input type="number" class="form-control fw-medium" name="season_price" 
                                       value="<?= $globalConfig['season_price'] ?>" step="0.01" min="0">
                                <div class="form-text fw-medium">Precio base para temporadas (se puede sobreescribir por temporada)</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Precio Combo Completo (USD)</label>
                                <input type="number" class="form-control fw-medium" name="bundle_price" 
                                       value="<?= $globalConfig['bundle_price'] ?>" step="0.01" min="0">
                                <div class="form-text fw-medium">Precio para acceso a todas las temporadas</div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning fw-bold">
                                <i class="bi bi-save me-2"></i>Guardar Configuración Global
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Precios por Temporada -->
                <div class="config-group">
                    <h5 class="text-warning mb-4 fw-bold">
                        <i class="bi bi-collection-play me-2"></i>Precios por Temporada
                    </h5>
                    
                    <?php foreach ($seasons as $season): ?>
                        <?php 
                        $config = $seasonConfigs[$season['id']] ?? [
                            'season_price' => 0,
                            'is_active' => 1
                        ];
                        ?>
                        <div class="whisky-card p-4 mb-4">
                            <h6 class="fw-bold text-light mb-3"><?= htmlspecialchars($season['title']) ?></h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_pricing">
                                <input type="hidden" name="season_id" value="<?= $season['id'] ?>">
                                
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Precio de la Temporada (USD)</label>
                                        <input type="number" class="form-control fw-medium" name="season_price" 
                                               value="<?= $config['season_price'] ?>" step="0.01" min="0">
                                        <div class="form-text fw-medium">Precio específico para esta temporada</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Estado</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" 
                                                   id="active_<?= $season['id'] ?>" 
                                                   <?= $config['is_active'] ? 'checked' : '' ?>
                                                   onchange="togglePricingConfig(<?= $config['id'] ?? 'null' ?>, this.checked, <?= $season['id'] ?>)">
                                            <label class="form-check-label fw-medium" for="active_<?= $season['id'] ?>">
                                                Configuración activa
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <button type="submit" class="btn btn-outline-warning w-100 fw-bold">
                                            <i class="bi bi-save me-2"></i>Guardar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumen de Configuraciones -->
                <div class="config-group">
                    <h5 class="text-warning mb-4 fw-bold">
                        <i class="bi bi-list-check me-2"></i>Resumen de Configuraciones
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Precio Capítulo</th>
                                    <th>Precio Temporada</th>
                                    <th>Precio Combo</th>
                                    <th>Estado</th>
                                    <th>Actualizado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Configuración Global -->
                                <tr>
                                    <td>
                                        <strong>Configuración Global</strong>
                                    </td>
                                    <td>$<?= number_format($globalConfig['chapter_price'], 2) ?></td>
                                    <td>$<?= number_format($globalConfig['season_price'], 2) ?></td>
                                    <td>$<?= number_format($globalConfig['bundle_price'], 2) ?></td>
                                    <td>
                                        <span class="badge badge-active">Activa</span>
                                    </td>
                                    <td>
                                        <?= $globalConfig['updated_at'] ? date('d/m/Y H:i', strtotime($globalConfig['updated_at'])) : 'Nunca' ?>
                                    </td>
                                </tr>
                                
                                <!-- Configuraciones por Temporada -->
                                <?php foreach ($seasons as $season): ?>
                                    <?php $config = $seasonConfigs[$season['id']] ?? null; ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($season['title']) ?></strong>
                                        </td>
                                        <td>-</td>
                                        <td>
                                            <?php if ($config): ?>
                                                $<?= number_format($config['season_price'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted fw-medium">Usa precio global</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>-</td>
                                        <td>
                                            <?php if ($config): ?>
                                                <span class="badge <?= $config['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <?= $config['is_active'] ? 'Activa' : 'Inactiva' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">No configurada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $config && $config['updated_at'] ? date('d/m/Y H:i', strtotime($config['updated_at'])) : 'Nunca' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePricingConfig(configId, isActive, seasonId) {
        if (!configId) return; // No hacer nada si no hay configuración guardada
        
        const formData = new FormData();
        formData.append('action', 'toggle_active');
        formData.append('id', configId);
        formData.append('is_active', isActive);
        
        fetch('pricing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Recargar la página para mostrar el mensaje
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado');
        });
    }
    
    // Validación de precios
    document.addEventListener('DOMContentLoaded', function() {
        const priceInputs = document.querySelectorAll('input[type="number"]');
        priceInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });
    });
    </script>
</body>
</html>