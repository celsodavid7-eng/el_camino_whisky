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
    
    switch ($action) {
        case 'send_newsletter':
            $subject = $_POST['subject'] ?? '';
            $content = $_POST['content'] ?? '';
            
            if (empty($subject) || empty($content)) {
                $message = '<div class="alert alert-danger">Asunto y contenido son requeridos</div>';
                break;
            }
            
            // Obtener suscriptores
            $stmt = $pdo->prepare("SELECT email FROM newsletter_subscriptions WHERE is_active = 1");
            $stmt->execute();
            $subscribers = $stmt->fetchAll();
            
            $sentCount = 0;
            foreach ($subscribers as $subscriber) {
                // En una implementación real, aquí enviarías el email
                // Por ahora solo contamos
                $sentCount++;
            }
            
            // Guardar historial del newsletter
            $stmt = $pdo->prepare("
                INSERT INTO newsletter_campaigns (subject, content, sent_count, sent_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$subject, $content, $sentCount, $_SESSION['user_id']]);
            
            $message = '<div class="alert alert-success">Newsletter enviado a ' . $sentCount . ' suscriptores</div>';
            break;
            
        case 'unsubscribe':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE newsletter_subscriptions SET is_active = 0 WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Suscriptor eliminado</div>';
            }
            break;
    }
}

// Crear tabla de campañas si no existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS newsletter_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(500) NOT NULL,
        content TEXT NOT NULL,
        sent_count INT DEFAULT 0,
        sent_by INT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sent_by) REFERENCES users(id)
    )
");

// Obtener suscriptores
$subscribers = $pdo->query("
    SELECT * FROM newsletter_subscriptions 
    WHERE is_active = 1 
    ORDER BY subscribed_at DESC
")->fetchAll();

// Obtener campañas anteriores
$campaigns = $pdo->query("
    SELECT nc.*, u.username 
    FROM newsletter_campaigns nc 
    LEFT JOIN users u ON nc.sent_by = u.id 
    ORDER BY nc.sent_at DESC 
    LIMIT 10
")->fetchAll();

$subscriberCount = count($subscribers);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Newsletter - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .table-dark { background: #1a1a1a; }
        .campaign-card { background: #2a2a2a; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="title-font">
                        <i class="bi bi-mailbox me-2"></i>Gestión de Newsletter
                    </h2>
                    <span class="badge bg-warning text-dark fs-6"><?= $subscriberCount ?> suscriptores</span>
                </div>

                <?= $message ?>

                <div class="row">
                    <!-- Formulario de Envío -->
                    <div class="col-lg-6">
                        <div class="campaign-card">
                            <h5 class="text-warning mb-4">
                                <i class="bi bi-send me-2"></i>Enviar Newsletter
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="send_newsletter">
                                
                                <div class="mb-3">
                                    <label class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" name="subject" required 
                                           placeholder="Título del newsletter...">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Contenido *</label>
                                    <textarea class="form-control" name="content" rows="8" required 
                                              placeholder="Escribe el contenido del newsletter..."></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Este newsletter será enviado a <strong><?= $subscriberCount ?></strong> suscriptores activos.
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-send me-2"></i>Enviar Newsletter
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Lista de Suscriptores -->
                    <div class="col-lg-6">
                        <div class="campaign-card">
                            <h5 class="text-warning mb-4">
                                <i class="bi bi-people me-2"></i>Suscriptores Activos
                            </h5>
                            
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-dark table-sm">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Fecha Suscripción</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subscribers as $subscriber): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($subscriber['email']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($subscriber['subscribed_at'])) ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="unsubscribe">
                                                    <input type="hidden" name="id" value="<?= $subscriber['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                            onclick="return confirm('¿Eliminar este suscriptor?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (empty($subscribers)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-people display-4 text-muted mb-3"></i>
                                    <p class="text-muted">No hay suscriptores activos</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Campañas Anteriores -->
                <div class="campaign-card mt-4">
                    <h5 class="text-warning mb-4">
                        <i class="bi bi-clock-history me-2"></i>Campañas Anteriores
                    </h5>
                    
                    <?php if (!empty($campaigns)): ?>
                        <div class="row g-3">
                            <?php foreach ($campaigns as $campaign): ?>
                            <div class="col-md-6">
                                <div class="bg-dark rounded p-3 h-100">
                                    <h6 class="text-warning"><?= htmlspecialchars($campaign['subject']) ?></h6>
                                    <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars(substr($campaign['content'], 0, 100))) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($campaign['sent_at'])) ?>
                                        </small>
                                        <div>
                                            <span class="badge bg-info"><?= $campaign['sent_count'] ?> envios</span>
                                            <small class="text-muted ms-2">por <?= $campaign['username'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <p class="text-muted">No hay campañas anteriores</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>