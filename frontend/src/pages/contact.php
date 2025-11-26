<?php
require_once '../../backend/config/database.php';

$messageSent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Todos los campos son requeridos';
    } else {
        // En una implementación real, aquí enviarías el email
        // Por ahora solo marcamos como enviado
        $messageSent = true;
        
        // También podrías guardar en la base de datos
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
    }
}

// Obtener configuración de contacto
$whatsappConfig = getWhatsAppConfig();
$bankConfig = getBankConfig();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - El Camino del Whisky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="icon" type="image/x-icon" href="../../../uploads/favicon.png">

</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h1 class="title-font display-4 mb-3">Contacto</h1>
                <p class="lead text-light">¿Tienes preguntas? Estamos aquí para ayudarte</p>
            </div>
        </div>
        
        <div class="row g-5">
            <!-- Formulario de Contacto -->
            <div class="col-lg-8">
                <div class="whisky-card p-4 p-md-5">
                    <h3 class="text-warning mb-4">
                        <i class="bi bi-envelope me-2"></i>Envíanos un Mensaje
                    </h3>
                    
                    <?php if ($messageSent): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            ¡Mensaje enviado exitosamente! Te contactaremos pronto.
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="name" required 
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Asunto *</label>
                            <select class="form-control" name="subject" required>
                                <option value="">Seleccionar asunto...</option>
                                <option value="Consulta general" <?= ($_POST['subject'] ?? '') === 'Consulta general' ? 'selected' : '' ?>>Consulta general</option>
                                <option value="Problemas con pagos" <?= ($_POST['subject'] ?? '') === 'Problemas con pagos' ? 'selected' : '' ?>>Problemas con pagos</option>
                                <option value="Soporte técnico" <?= ($_POST['subject'] ?? '') === 'Soporte técnico' ? 'selected' : '' ?>>Soporte técnico</option>
                                <option value="Sugerencias" <?= ($_POST['subject'] ?? '') === 'Sugerencias' ? 'selected' : '' ?>>Sugerencias</option>
                                <option value="Colaboraciones" <?= ($_POST['subject'] ?? '') === 'Colaboraciones' ? 'selected' : '' ?>>Colaboraciones</option>
                                <option value="Otro" <?= ($_POST['subject'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mensaje *</label>
                            <textarea class="form-control" name="message" rows="6" required
                                      placeholder="Describe tu consulta en detalle..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-send me-2"></i>Enviar Mensaje
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Información de Contacto -->
            <div class="col-lg-4">
                <!-- Contacto Directo -->
                <div class="whisky-card p-4 mb-4">
                    <h5 class="text-warning mb-4">
                        <i class="bi bi-telephone me-2"></i>Contacto Directo
                    </h5>
                    <div class="space-y-3">
                        <a href="https://wa.me/<?= $whatsappConfig['number'] ?>" 
                           class="btn btn-success w-100 d-flex align-items-center justify-content-center"
                           target="_blank">
                            <i class="bi bi-whatsapp me-2 fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">WhatsApp</div>
                                <small>Respuesta inmediata</small>
                            </div>
                        </a>
                        
                        <a href="mailto:info@elcaminodelwhisky.com" 
                           class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center">
                            <i class="bi bi-envelope me-2 fs-5"></i>
                            <div class="text-start">
                                <div class="fw-bold">Email</div>
                                <small>info@elcaminodelwhisky.com</small>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Información Bancaria -->
                <div class="whisky-card p-4 mb-4">
                    <h5 class="text-warning mb-4">
                        <i class="bi bi-bank me-2"></i>Información Bancaria
                    </h5>
                    <div class="space-y-2">
                        <div>
                            <small class="text-muted d-block">Banco</small>
                            <strong><?= htmlspecialchars($bankConfig['bank_name']) ?></strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Cuenta</small>
                            <strong><?= htmlspecialchars($bankConfig['account_number']) ?></strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Alias</small>
                            <strong class="text-warning"><?= htmlspecialchars($bankConfig['alias']) ?></strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Titular</small>
                            <strong><?= htmlspecialchars($bankConfig['account_holder']) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Preguntas Frecuentes -->
                <div class="whisky-card p-4">
                    <h5 class="text-warning mb-4">
                        <i class="bi bi-question-circle me-2"></i>Preguntas Frecuentes
                    </h5>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item bg-dark border-secondary">
                            <h6 class="accordion-header">
                                <button class="accordion-button bg-dark text-light" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq1">
                                    ¿Cómo compro acceso a una temporada?
                                </button>
                            </h6>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Selecciona la temporada, haz clic en "Comprar Acceso" y sigue el proceso de pago por WhatsApp.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-dark border-secondary">
                            <h6 class="accordion-header">
                                <button class="accordion-button collapsed bg-dark text-light" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                    ¿Qué métodos de pago aceptan?
                                </button>
                            </h6>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Aceptamos transferencias bancarias y pagos en efectivo. Toda la información en la sección de pagos.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-dark border-secondary">
                            <h6 class="accordion-header">
                                <button class="accordion-button collapsed bg-dark text-light" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                    ¿Puedo acceder desde múltiples dispositivos?
                                </button>
                            </h6>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Sí, puedes acceder desde cualquier dispositivo con tu cuenta de usuario.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/modals.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>