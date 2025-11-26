// main.js - VERSIÓN COMPLETA SIN LOCALSTORAGE

// Variables globales
let currentUser = null;
let currentPaymentData = null;

// Debug inicial
console.log('🚀 main.js cargado correctamente');

// ==================== SISTEMA DE SESIÓN SIMPLIFICADO ====================

// ✅ VERIFICAR SESIÓN PHP
function isUserLoggedIn() {
    return typeof SITE_CONFIG !== 'undefined' && SITE_CONFIG.user.isLoggedIn;
}

function getCurrentUserId() {
    return isUserLoggedIn() ? SITE_CONFIG.user.id : null;
}

function getCurrentUser() {
    return isUserLoggedIn() ? SITE_CONFIG.user : null;
}

// ==================== NAVEGACIÓN Y UI ====================

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Smooth scrolling corregido
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        
        if (href === '#' || href === '' || !href || href === '#!') {
            e.preventDefault();
            return;
        }
        
        if (href.length > 1) {
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                
                // Cerrar navbar en móvil
                const navbarToggler = document.querySelector('.navbar-toggler');
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarToggler && !navbarToggler.classList.contains('collapsed')) {
                    navbarToggler.click();
                }
                
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        } else {
            e.preventDefault();
        }
    });
});

// Prevenir que el dropdown cierre el navbar en móvil
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('show');
            }
        });
    });
});

// ==================== SISTEMA DE CAPÍTULOS ====================

function loadChapter(chapterId) {
    console.log('🔍 loadChapter llamado con ID:', chapterId);
    
    // Verificar sesión antes de cargar capítulo
    if (!isUserLoggedIn()) {
        console.log('❌ Usuario no logueado, mostrando modal de registro');
        $('#registerModal').modal('show');
        return;
    }
    
    const userId = getCurrentUserId();
    
    $.ajax({
        url: '/ecdw/backend/api/chapters.php',
        method: 'POST',
        dataType: 'json',
        data: { 
            action: 'get_chapter',
            chapter_id: chapterId,
            user_id: userId || 0
        },
        success: function(data) {
            console.log('📦 Respuesta del API:', data);
            
            if (data && data.success) {
                if (data.chapter.is_free || data.has_access) {
                    window.location.href = '/ecdw/frontend/src/pages/chapter.php?id=' + chapterId;
                } else {
                    showPaymentModal(data.chapter.season_id);
                }
            } else {
                const errorMsg = data?.message || 'Error desconocido';
                alert('Error al cargar el capítulo: ' + errorMsg);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            alert('Error de conexión. Intenta nuevamente.');
        }
    });
}

function showChapterModal(chapter) {
    // Crear modal dinámico para mostrar el capítulo
    const modalHtml = `
        <div class="modal fade" id="chapterModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title title-font fs-3">${chapter.title}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-0">
                        ${chapter.subtitle ? `<p class="lead mb-4">${chapter.subtitle}</p>` : ''}
                        
                        <!-- Slider de imágenes -->
                        ${chapter.images && chapter.images.length > 0 ? `
                            <div class="image-slider mb-4">
                                ${chapter.images.map((img, index) => `
                                    <img src="../../backend/uploads/${img.image_path}" 
                                         class="slider-image ${index === 0 ? 'active' : ''}" 
                                         alt="${img.caption || chapter.title}">
                                `).join('')}
                                
                                ${chapter.images.length > 1 ? `
                                    <div class="slider-nav">
                                        ${chapter.images.map((_, index) => `
                                            <div class="slider-dot ${index === 0 ? 'active' : ''}" 
                                                 onclick="changeSlide(${index})"></div>
                                        `).join('')}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                        
                        <!-- Contenido del capítulo -->
                        <div class="chapter-content mb-4">
                            ${chapter.content}
                        </div>
                        
                        <!-- Categorías -->
                        ${chapter.categories && chapter.categories.length > 0 ? `
                            <div class="mb-4">
                                <strong>Categorías:</strong>
                                ${chapter.categories.map(cat => 
                                    `<span class="badge bg-warning text-dark ms-2">${cat.name}</span>`
                                ).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM y mostrarlo
    $('body').append(modalHtml);
    $('#chapterModal').modal('show');
    
    // Remover el modal cuando se cierre
    $('#chapterModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

// Funciones del slider de imágenes
function changeSlide(index) {
    $('.slider-image').removeClass('active');
    $('.slider-dot').removeClass('active');
    $('.slider-image').eq(index).addClass('active');
    $('.slider-dot').eq(index).addClass('active');
}

// ==================== SISTEMA DE PAGOS ====================

// Función global para mostrar modal de pago
window.showPaymentModal = function(seasonId) {
    console.log('💰 Mostrando modal de pago para temporada:', seasonId);
    
    // Verificar sesión
    if (!isUserLoggedIn()) {
        console.log('❌ Usuario no logueado, redirigiendo a login');
        $('#paymentModal').modal('hide');
        $('#loginModal').modal('show');
        return;
    }
    
    $('#paymentModal').modal('show');
};

// Procesar pago final
function processPayment() {
    const selectedPrice = $('#selectedPrice').val();
    const selectedType = $('#selectedType').val();
    const selectedDescription = $('#selectedDescription').val();
    const paymentMethod = $('.payment-method.active').data('method');
    
    if (!selectedPrice || !selectedType) {
        alert('Por favor completa la selección de pago');
        return;
    }
    
    const user = getCurrentUser();
    const whatsappMessage = `Hola! Estoy interesado en adquirir acceso premium a El Camino del Whisky.

📋 Mi información:
• Usuario: ${user ? user.username : 'Nuevo usuario'}
• Producto: ${selectedDescription}
• Monto: $${parseFloat(selectedPrice).toFixed(2)} USD
• Tipo: ${selectedType === 'season' ? 'Temporada' : selectedType === 'chapter' ? 'Capítulos' : 'Combo'}
• Método de pago: ${paymentMethod === 'transfer' ? 'Transferencia bancaria' : 'Efectivo'}

Por favor, envíenme la información completa para realizar el pago.`;

    const encodedMessage = encodeURIComponent(whatsappMessage);
    const whatsappUrl = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodedMessage}`;
    
    window.open(whatsappUrl, '_blank');
    $('#paymentModal').modal('hide');
    
    // Mostrar mensaje de confirmación
    showToast('¡Perfecto!', 'Se abrió WhatsApp para coordinar el pago', 'success');
}

function selectSeason(element, seasonId, price) {
    $('[data-season]').removeClass('active');
    $(element).addClass('active');
    $('#paymentAmount').val(price);
    $('#paymentSeasonId').val(seasonId);
}

// Manejo de métodos de pago
$('.payment-method').click(function() {
    $('.payment-method').removeClass('active');
    $(this).addClass('active');
    const method = $(this).data('method');
    $('#paymentMethod').val(method);
});

// Formulario de pago
$('#paymentForm').submit(function(e) {
    e.preventDefault();
    
    // Verificar sesión antes de procesar pago
    if (!isUserLoggedIn()) {
        alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        $('#paymentModal').modal('hide');
        $('#loginModal').modal('show');
        return;
    }
    
    const userId = $('#paymentUserId').val();
    const seasonId = $('#paymentSeasonId').val();
    const amount = $('#paymentAmount').val();
    const paymentMethod = $('#paymentMethod').val();
    
    if (!userId || !seasonId || !amount) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Procesando...');
    submitBtn.prop('disabled', true);
    
    // Crear mensaje para WhatsApp
    const user = getCurrentUser();
    const whatsappMessage = `Hola! Estoy interesado en adquirir acceso premium a El Camino del Whisky.

📋 Mi información:
• Usuario: ${user ? user.username : 'Nuevo usuario'}
• Producto seleccionado: Temporada ${seasonId}
• Monto: $${amount} USD
• Método de pago: ${paymentMethod === 'transfer' ? 'Transferencia bancaria' : 'Efectivo'}

Por favor, envíenme la información completa para realizar el pago.`;

    const encodedMessage = encodeURIComponent(whatsappMessage);
    const whatsappUrl = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodedMessage}`;
    
    $('#whatsappLink').attr('href', whatsappUrl);
    $('#paymentStep1').hide();
    $('#paymentStep2').show();
    $('#bankInfoReference').hide();
    
    setTimeout(() => {
        window.open(whatsappUrl, '_blank');
    }, 2000);
    
    setTimeout(() => {
        resetPaymentButton(submitBtn, originalText);
    }, 3000);
});

function resetPaymentButton(button, originalText) {
    button.html(originalText);
    button.prop('disabled', false);
}

function resetPaymentModal() {
    $('#paymentStep2').hide();
    $('#paymentStep1').show();
    $('#bankInfoReference').show();
    $('#paymentForm')[0].reset();
    $('#paymentMethod').val('transfer');
    $('.payment-method').removeClass('active');
    $('.payment-method[data-method="transfer"]').addClass('active');
}

function showBankInfo() {
    $('#bankInfoModal').modal('show');
}

// ==================== SISTEMA DE AUTENTICACIÓN ====================

// Formulario de login
// ==================== SISTEMA DE AUTENTICACIÓN ====================

// Formulario de login
$('#loginForm').submit(function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Iniciando...');
    submitBtn.prop('disabled', true);
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: '../../backend/api/auth.php',
        method: 'POST',
        dataType: 'json',
        data: formData,
        success: function(data) {
            if (data.success) {
                $('#loginModal').modal('hide');
                showToast('¡Bienvenido ' + data.user.username + '!', 'success');
                
                // ✅ ACTUALIZAR INTERFAZ INMEDIATAMENTE SIN RECARGAR
                updateUIAfterLogin(data.user);
                
            } else {
                showToast('Error: ' + data.message, 'error');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Login error:', error);
            showToast('Error de conexión. Intenta nuevamente.', 'error');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
});

// Formulario de registro
$('#registerForm').submit(function(e) {
    e.preventDefault();
    
    const password = $('#registerPassword').val();
    const confirmPassword = $('#registerConfirmPassword').val();
    
    if (password !== confirmPassword) {
        showToast('Las contraseñas no coinciden', 'error');
        return;
    }
    
    if (password.length < 6) {
        showToast('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-2"></i>Creando...');
    submitBtn.prop('disabled', true);
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: '../../backend/api/auth.php',
        method: 'POST',
        dataType: 'json',
        data: formData,
        success: function(data) {
            if (data.success) {
                $('#registerModal').modal('hide');
                showToast('¡Cuenta creada exitosamente! Bienvenido ' + data.user.username, 'success');
                
                // ✅ ACTUALIZAR INTERFAZ INMEDIATAMENTE SIN RECARGAR
                updateUIAfterLogin(data.user);
                
            } else {
                showToast('Error: ' + data.message, 'error');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Register error:', error);
            showToast('Error de conexión. Intenta nuevamente.', 'error');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
});

// ==================== ACTUALIZACIÓN DE UI DINÁMICA ====================

function updateUIAfterLogin(userData) {
    console.log('🔄 Actualizando UI después del login:', userData);
    
    // ✅ ACTUALIZAR NAVBAR - Reemplazar botones de login/register por menú de usuario
    const navbarNav = document.querySelector('#navbarNav .navbar-nav');
    
    // Buscar y eliminar botones de login/register
    const authButtons = navbarNav.querySelectorAll('.nav-item:has(a[data-bs-target="#loginModal"]), .nav-item:has(a[data-bs-target="#registerModal"])');
    authButtons.forEach(button => button.remove());
    
    // Crear menú de usuario
    const userMenuHTML = `
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person me-1"></i>${userData.username}
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/profile.php">
                        <i class="bi bi-person me-2"></i>Mi Perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/my_courses.php">
                        <i class="bi bi-play-circle me-2"></i>Mis Cursos
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/../src/pages/messages.php">
                        <i class="bi bi-chat-dots me-2"></i>Mensajes
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                ${userData.role === 'admin' ? `
                    <li>
                        <a class="dropdown-item text-warning" href="<?= BASE_URL ?>/../../backend/admin/">
                            <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                ` : ''}
                <li>
                    <a class="dropdown-item text-danger" href="#" data-logout>
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </a>
                </li>
            </ul>
        </li>
    `;
    
    // Insertar menú de usuario antes del último elemento (para mantener orden)
    navbarNav.insertAdjacentHTML('beforeend', userMenuHTML);
    
    // ✅ ACTUALIZAR BOTONES EN EL CONTENIDO
    updateContentButtons();
    
    // ✅ ACTUALIZAR CONFIGURACIÓN GLOBAL
    if (typeof SITE_CONFIG !== 'undefined') {
        SITE_CONFIG.user = {
            isLoggedIn: true,
            id: userData.id,
            username: userData.username,
            email: userData.email,
            role: userData.role
        };
    }
    
    // ✅ RE-BINDEAR EVENTOS (importante para el logout)
    bindLogoutEvents();
    
    console.log('✅ UI actualizada exitosamente');
}

function updateContentButtons() {
    // ✅ ACTUALIZAR BOTONES EN EL HERO SECTION
    const heroButtons = document.querySelectorAll('#inicio .btn');
    heroButtons.forEach(button => {
        if (button.textContent.includes('Regístrate Gratis') || button.textContent.includes('Comenzar Ahora')) {
            button.textContent = 'Explorar Temporadas';
            button.setAttribute('data-bs-target', '#temporadas');
            button.innerHTML = '<i class="bi bi-play-circle me-2"></i>Explorar Temporadas';
        }
    });
    
    // ✅ ACTUALIZAR BOTONES EN SECCIÓN PROYECTO
    const projectButtons = document.querySelectorAll('#proyecto .btn');
    projectButtons.forEach(button => {
        if (button.textContent.includes('Unirse al Proyecto')) {
            button.style.display = 'none'; // Ocultar botón de registro
        }
    });
    
    // ✅ ACTUALIZAR SECCIÓN DE CONTACTOS
    const contactSection = document.querySelector('#contactos .whisky-card:last-child');
    if (contactSection) {
        const registerButton = contactSection.querySelector('.btn');
        if (registerButton && registerButton.textContent.includes('Registrarse Gratis')) {
            contactSection.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    ¡Ya eres parte de nuestra comunidad!
                </div>
                <a href="<?= BASE_URL ?>/../src/pages/my_courses.php" class="btn btn-gold w-100">
                    <i class="bi bi-play-circle me-2"></i>Ver Mis Cursos
                </a>
            `;
        }
    }
}

function bindLogoutEvents() {
    // ✅ RE-BINDEAR EVENTOS DE LOGOUT PARA EL NUEVO MENÚ
    document.querySelectorAll('[data-logout]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cerrando...';
                this.disabled = true;
                
                const formData = new FormData();
                formData.append('action', 'logout');
                
                fetch('<?= BASE_URL ?>/../../backend/api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showToast('Sesión cerrada', 'Has cerrado sesión correctamente', 'success');
                        // Recargar para restaurar UI completa
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast('Error', result.message || 'Error al cerrar sesión', 'error');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    showToast('Error', 'Error de conexión', 'error');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });
}

// ==================== SISTEMA DE PROGRESO ====================

class ProgressSystem {
    constructor() {
        this.apiUrl = '../../backend/api/progress.php';
    }

    async markChapterCompleted(chapterId, seasonId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_completed');
            formData.append('chapter_id', chapterId);
            formData.append('season_id', seasonId);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error('Error marking chapter completed:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }

    async getUserProgress(seasonId = null) {
        try {
            let url = this.apiUrl + '?action=get_progress';
            if (seasonId) {
                url += `&season_id=${seasonId}`;
            }

            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('Error getting user progress:', error);
            return { success: false, data: [] };
        }
    }

    updateProgressRing(percentage) {
        const progressElements = document.querySelectorAll('.circular-progress');
        
        progressElements.forEach(progress => {
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
    }
}

// ==================== SISTEMA DE MENSAJES ====================

class MessageSystem {
    constructor() {
        this.apiUrl = '../../backend/api/messages.php';
    }

    async sendMessage(receiverId, message) {
        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', receiverId);
            formData.append('message', message);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error('Error sending message:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }

    async getConversations() {
        try {
            const response = await fetch(this.apiUrl + '?action=get_conversations');
            return await response.json();
        } catch (error) {
            console.error('Error getting conversations:', error);
            return { success: false, data: [] };
        }
    }

    async getMessages(userId) {
        try {
            const response = await fetch(this.apiUrl + `?action=get_messages&user_id=${userId}`);
            return await response.json();
        } catch (error) {
            console.error('Error getting messages:', error);
            return { success: false, data: [] };
        }
    }

    async markAsRead(messageId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('message_id', messageId);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error('Error marking message as read:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }

    async getUnreadCount() {
        try {
            const response = await fetch(this.apiUrl + '?action=get_unread_count');
            return await response.json();
        } catch (error) {
            console.error('Error getting unread count:', error);
            return { success: false, count: 0 };
        }
    }
}

// ==================== UTILIDADES Y FUNCIONES GLOBALES ====================

// Función para mostrar notificaciones
function showToast(message, type = 'info') {
    // Usar la función del modal si existe
    if (typeof window.showToast === 'function') {
        window.showToast('Notificación', message, type);
        return;
    }
    
    // Fallback: crear toast básico
    const toast = document.getElementById('liveToast');
    if (toast) {
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        
        const typeConfig = {
            'success': { icon: 'bi-check-circle', color: 'text-success' },
            'error': { icon: 'bi-exclamation-circle', color: 'text-danger' },
            'warning': { icon: 'bi-exclamation-triangle', color: 'text-warning' },
            'info': { icon: 'bi-info-circle', color: 'text-info' }
        };
        
        const config = typeConfig[type] || typeConfig.info;
        
        toastTitle.innerHTML = `<i class="bi ${config.icon} me-2 ${config.color}"></i>Notificación`;
        toastMessage.textContent = message;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    } else {
        // Fallback final: alert básico
        alert(message);
    }
}

// Función para contacto por WhatsApp
function contactWhatsApp(customMessage = '') {
    const message = customMessage || SITE_CONFIG.whatsapp.defaultMessage;
    const url = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}

// Función para mostrar información bancaria rápida
function showQuickBankInfo() {
    const info = `💰 *Información Bancaria Rápida:*
• Banco: ${SITE_CONFIG.bank.name}
• Alias: ${SITE_CONFIG.bank.alias}
• Cuenta: ${SITE_CONFIG.bank.account}
• Titular: ${SITE_CONFIG.bank.holder}

¡Contacta por WhatsApp para confirmar disponibilidad!`;
    
    const whatsappUrl = `https://wa.me/${SITE_CONFIG.whatsapp.number}?text=${encodeURIComponent(info)}`;
    window.open(whatsappUrl, '_blank');
}

// Formatear moneda
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2) + ' USD';
}

// ==================== INICIALIZACIÓN ====================

$(document).ready(function() {
    console.log('🎯 Inicializando main.js...');
    
    // Inicializar sistemas
    window.progressSystem = new ProgressSystem();
    window.messageSystem = new MessageSystem();
    
    // Configurar validación de contraseñas en registro
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
    
    // Configurar métodos de pago si el modal existe
    if ($('.payment-method').length) {
        $('.payment-method').on('click', function() {
            $('.payment-method').removeClass('active');
            $(this).addClass('active');
            const method = $(this).data('method');
            $('#paymentMethod').val(method);
        });
    }
    
    // Actualizar contador de mensajes no leídos cada 30 segundos
    if (isUserLoggedIn()) {
        setInterval(updateUnreadMessagesCount, 30000);
        updateUnreadMessagesCount(); // Ejecutar inmediatamente
    }
    
    console.log('✅ main.js completamente inicializado');
    console.log('📍 Estado de sesión:', {
        logueado: isUserLoggedIn(),
        usuario: getCurrentUser()
    });
});

// Actualizar contador de mensajes no leídos
function updateUnreadMessagesCount() {
    if (!window.messageSystem || !isUserLoggedIn()) return;
    
    window.messageSystem.getUnreadCount()
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

// ==================== ANIMACIONES Y EFECTOS ====================

// Animación de progreso circular al cargar
document.addEventListener('DOMContentLoaded', function() {
    const progressElements = document.querySelectorAll('.circular-progress');
    
    progressElements.forEach(progress => {
        const percentage = parseInt(progress.getAttribute('data-percentage')) || 0;
        const valueElement = progress.querySelector('.progress-value');
        
        if (valueElement) {
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
        }
    });
});

// Efectos hover para tarjetas
document.querySelectorAll('.whisky-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// DEBUG FINAL
console.log('✅ main.js completamente cargado - ' + (isUserLoggedIn() ? 'Usuario logueado' : 'Usuario no logueado'));