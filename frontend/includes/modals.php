<?php
// frontend/includes/modals.php

// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ecdw/frontend/public');
}

// Incluir database.php si es necesario
require_once ROOT_DIR . '/backend/config/database.php';

// Obtener configuración desde la base de datos
try {
    $configStmt = $pdo->query("SELECT config_key, config_value FROM site_config");
    $configData = $configStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Configuración de WhatsApp
    $whatsappConfig = [
        'number' => $configData['whatsapp_number'] ?? '595983163300',
        'default_message' => $configData['whatsapp_message'] ?? 'Hola! Estoy interesado en El Camino del Whisky'
    ];
    
    // Configuración bancaria
    $bankConfig = [
        'bank_name' => $configData['bank_name'] ?? '',
        'account_number' => $configData['bank_account'] ?? '',
        'alias' => $configData['bank_alias'] ?? '',
        'account_holder' => $configData['bank_holder'] ?? ''
    ];
    
} catch (Exception $e) {
    error_log("Error loading config: " . $e->getMessage());
    // Valores por defecto si hay error
    $whatsappConfig = [
        'number' => '595983163300',
        'default_message' => 'Hola! Estoy interesado en El Camino del Whisky'
    ];
    
    $bankConfig = [
        'bank_name' => '',
        'account_number' => '',
        'alias' => '',
        'account_holder' => ''
    ];
}
?>

<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font">Iniciar Sesión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe">Recordarme</label>
                    </div>
                    <input type="hidden" name="action" value="login">
                    <button type="submit" class="btn btn-gold w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Iniciar Sesión
                    </button>
                </form>
                <div class="text-center mt-3">
                    <small>¿No tienes cuenta? <a href="#" class="text-warning" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Regístrate aquí</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Registro -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font">Crear Cuenta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" id="registerPassword" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" name="confirm_password" id="registerConfirmPassword" required>
                    </div>
                    <input type="hidden" name="action" value="register">
                    <button type="submit" class="btn btn-gold w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Crear Cuenta
                    </button>
                </form>
                <div class="text-center mt-3">
                    <small>¿Ya tienes cuenta? <a href="#" class="text-warning" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Inicia sesión aquí</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font fw-bold">Acceso Premium</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Paso 1: Selección de tipo de acceso -->
                <div id="paymentStep1">
                    <div class="mb-4">
                        <p class="text-light fw-medium mb-3">Elige la opción que mejor se adapte a tus necesidades:</p>
                        <div class="row" id="pricingOptions">
                            <!-- Las opciones de precio se cargan dinámicamente -->
                        </div>
                    </div>
                    
                    <input type="hidden" id="selectedPrice" value="">
                    <input type="hidden" id="selectedType" value="">
                    <input type="hidden" id="selectedDescription" value="">
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-gold fw-bold" id="continuePaymentBtn" disabled onclick="continueToPayment()">
                            Continuar <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Paso 2: Método de pago -->
                <div id="paymentStep2" style="display: none;">
                    <div class="mb-4">
                        <h6 class="fw-bold text-warning mb-3">Resumen de Compra</h6>
                        <div class="whisky-card p-3 mb-3" id="paymentSummary">
                            <!-- Resumen se carga dinámicamente -->
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Método de Pago:</label>
                        <div class="d-flex gap-3 mb-3">
                            <div class="payment-method active" data-method="transfer" style="cursor: pointer;">
                                <div class="whisky-card p-3 text-center">
                                    <i class="bi bi-bank display-6 text-warning mb-2"></i>
                                    <div class="fw-bold">Transferencia</div>
                                </div>
                            </div>
                            <div class="payment-method" data-method="cash" style="cursor: pointer;">
                                <div class="whisky-card p-3 text-center">
                                    <i class="bi bi-cash-coin display-6 text-warning mb-2"></i>
                                    <div class="fw-bold">Efectivo</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Bancaria (siempre visible en paso 2) -->
                    <div id="bankInfoSection" class="mb-4">
                        <h6 class="fw-bold text-warning mb-3">Información Bancaria</h6>
                        <div class="whisky-card p-4">
                            <div class="mb-3">
                                <strong class="text-warning fw-bold">Banco:</strong>
                                <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['bank_name']) ?></div>
                            </div>
                            <div class="mb-3">
                                <strong class="text-warning fw-bold">Número de Cuenta:</strong>
                                <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['account_number']) ?></div>
                            </div>
                            <div class="mb-3">
                                <strong class="text-warning fw-bold">Alias:</strong>
                                <div class="text-light fw-bold"><?= htmlspecialchars($bankConfig['alias']) ?></div>
                            </div>
                            <div class="mb-3">
                                <strong class="text-warning fw-bold">Titular:</strong>
                                <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['account_holder']) ?></div>
                            </div>
                           
                        </div>
                        <div class="alert alert-info mt-3">
                            <small class="fw-medium">
                                <i class="bi bi-info-circle me-2"></i>
                                Realiza la transferencia con los datos proporcionados y luego confirma por WhatsApp.
                            </small>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-gold flex-fill" onclick="backToPricing()">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </button>
                        <button type="button" class="btn btn-gold flex-fill fw-bold" onclick="processPayment()">
                            <i class="bi bi-whatsapp me-2"></i>Confirmar por WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Información Bancaria (independiente) -->
<div class="modal fade" id="bankInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font fw-bold">Información Bancaria</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="whisky-card p-4">
                    <div class="mb-3">
                        <strong class="text-warning fw-bold">Banco:</strong>
                        <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['bank_name']) ?></div>
                    </div>
                    <div class="mb-3">
                        <strong class="text-warning fw-bold">Número de Cuenta:</strong>
                        <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['account_number']) ?></div>
                    </div>
                    <div class="mb-3">
                        <strong class="text-warning fw-bold">Alias:</strong>
                        <div class="text-light fw-bold"><?= htmlspecialchars($bankConfig['alias']) ?></div>
                    </div>
                    <div class="mb-3">
                        <strong class="text-warning fw-bold">Titular:</strong>
                        <div class="text-light fw-medium"><?= htmlspecialchars($bankConfig['account_holder']) ?></div>
                    </div>
                    
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-gold fw-bold" onclick="contactWhatsApp('Hola! Necesito confirmar la información bancaria para realizar el pago.')">
                        <i class="bi bi-whatsapp me-2"></i>Contactar por WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cambio de Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font">Cambiar Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" name="confirm_new_password" required>
                    </div>
                    <button type="submit" class="btn btn-gold w-100">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title title-font" id="confirmationModalTitle">Confirmación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-question-circle display-1 text-warning mb-3"></i>
                <p id="confirmationModalMessage">¿Estás seguro de que quieres realizar esta acción?</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-gold" id="confirmationModalConfirm">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Alertas Toast -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-info-circle me-2 text-warning"></i>
            <strong class="me-auto" id="toastTitle">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Mensaje de notificación
        </div>
    </div>
</div>

<script>
// Funciones globales para modals
function showConfirmation(title, message, confirmCallback) {
    $('#confirmationModalTitle').text(title);
    $('#confirmationModalMessage').text(message);
    $('#confirmationModalConfirm').off('click').on('click', confirmCallback);
    $('#confirmationModal').modal('show');
}

function showToast(title, message, type = 'info') {
    const toast = document.getElementById('liveToast');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    
    // Configurar colores según el tipo
    const typeConfig = {
        'success': { icon: 'bi-check-circle', color: 'text-success' },
        'error': { icon: 'bi-exclamation-circle', color: 'text-danger' },
        'warning': { icon: 'bi-exclamation-triangle', color: 'text-warning' },
        'info': { icon: 'bi-info-circle', color: 'text-info' }
    };
    
    const config = typeConfig[type] || typeConfig.info;
    
    // Actualizar contenido
    toastTitle.innerHTML = `<i class="bi ${config.icon} me-2 ${config.color}"></i>${title}`;
    toastMessage.textContent = message;
    
    // Mostrar toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function contactWhatsApp(customMessage = '') {
    const message = customMessage || '<?= $whatsappConfig['default_message'] ?>';
    const url = `https://wa.me/<?= $whatsappConfig['number'] ?>?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}

function showBankInfo() {
    $('#bankInfoModal').modal('show');
}

// Inicialización de modals
document.addEventListener('DOMContentLoaded', function() {
    // Métodos de pago
    $('.payment-method').on('click', function() {
        $('.payment-method').removeClass('active');
        $(this).addClass('active');
    });

    // Validación de contraseñas en registro
    $('#registerPassword, #registerConfirmPassword').on('input', function() {
        const password = $('#registerPassword').val();
        const confirm = $('#registerConfirmPassword').val();
        
        if (password && confirm) {
            if (password !== confirm) {
                $('#registerConfirmPassword').addClass('is-invalid');
            } else {
                $('#registerConfirmPassword').removeClass('is-invalid');
            }
        }
    });

    // Configurar modal de pago
    $('#paymentModal').on('show.bs.modal', function() {
        // Resetear selección
        $('.pricing-option').removeClass('selected');
        $('#selectedPrice').val('');
        $('#selectedType').val('');
        $('#selectedDescription').val('');
        $('#continuePaymentBtn').prop('disabled', true);
        
        // Resetear métodos de pago
        $('.payment-method').removeClass('active');
        $('.payment-method[data-method="transfer"]').addClass('active');
        
        // Mostrar paso 1
        $('#paymentStep2').hide();
        $('#paymentStep1').show();
    });
});
</script>