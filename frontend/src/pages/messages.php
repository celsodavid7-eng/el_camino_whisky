<?php
// frontend/src/pages/messages.php

// Configuración directa de rutas - SOLO DEFINIR SI NO EXISTEN
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT'] . '/ecdw');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ecdw/frontend/public');
}

// Incluir conexión a BD
require_once ROOT_DIR . '/backend/config/database.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener conversaciones del usuario
try {
    // Obtener lista de administradores
    $adminsStmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE role = 'admin' AND is_active = 1");
    $adminsStmt->execute();
    $admins = $adminsStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error loading messages: " . $e->getMessage());
    die('Error al cargar los mensajes');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes | El Camino del Whisky</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../../uploads/favicon.png">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/../src/styles/main.css">
    
    <style>
        .messages-hero {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 120px 0 60px;
        }
        .messages-container {
            height: 600px;
            background: var(--dark);
        }
        .conversations-list {
            border-right: 1px solid rgba(255,255,255,0.1);
            height: 100%;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .conversation-item:hover, .conversation-item.active {
            background: rgba(255,255,255,0.05);
        }
        .messages-area {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .messages-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        .message.sent {
            margin-left: auto;
            background: var(--primary);
            color: white;
            border-radius: 15px 15px 0 15px;
        }
        .message.received {
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 15px 15px 15px 0;
        }
        .message-input {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 15px;
        }
    </style>
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include ROOT_DIR . '/frontend/includes/navbar.php'; ?>

    <!-- Hero de Mensajes -->
    <section class="messages-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 title-font fw-bold mb-3">Mensajes</h1>
                    <p class="lead fs-4">Comunícate con nuestra comunidad</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="messages-icon display-1 text-warning">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sistema de Mensajes -->
    <section class="py-5" style="background: var(--dark);">
        <div class="container">
            <div class="whisky-card messages-container">
                <div class="row h-100">
                    <!-- Lista de Conversaciones -->
                    <div class="col-md-4 conversations-list">
                        <div class="p-3 border-bottom border-secondary">
                            <h5 class="title-font mb-0">Conversaciones</h5>
                        </div>
                        
                        <!-- Administradores -->
                        <div class="p-3">
                            <h6 class="text-warning mb-3">Soporte</h6>
                            <?php foreach ($admins as $admin): ?>
                                <div class="conversation-item" data-user-id="<?= $admin['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder me-3">
                                            <i class="bi bi-person-circle fs-4 text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($admin['username']) ?></h6>
                                            <small class="text-muted">Administrador</small>
                                        </div>
                                        <span class="badge bg-warning unread-badge d-none">0</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Otras conversaciones se cargarán dinámicamente -->
                        <div id="conversationsList">
                            <!-- Las conversaciones se cargan via JavaScript -->
                        </div>
                    </div>

                    <!-- Área de Mensajes -->
                    <div class="col-md-8">
                        <div class="messages-area">
                            <!-- Encabezado de conversación -->
                            <div class="conversation-header p-3 border-bottom border-secondary d-flex align-items-center">
                                <div class="avatar-placeholder me-3">
                                    <i class="bi bi-person-circle fs-3 text-warning"></i>
                                </div>
                                <div>
                                    <h5 class="title-font mb-0" id="currentConversationUser">Selecciona una conversación</h5>
                                    <small class="text-muted" id="currentConversationStatus">-</small>
                                </div>
                            </div>

                            <!-- Lista de Mensajes -->
                            <div class="messages-list" id="messagesList">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-chat-quote display-4 mb-3"></i>
                                    <p>Selecciona una conversación para comenzar a chatear</p>
                                </div>
                            </div>

                            <!-- Input de Mensaje -->
                            <div class="message-input">
                                <form id="messageForm" class="d-flex gap-2">
                                    <input type="hidden" id="currentReceiverId" name="receiver_id">
                                    <input type="text" 
                                           class="form-control" 
                                           id="messageInput" 
                                           placeholder="Escribe un mensaje..." 
                                           disabled>
                                    <button type="submit" class="btn btn-gold" disabled>
                                        <i class="bi bi-send"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Incluir Modals -->
    <?php include ROOT_DIR . '/frontend/includes/modals.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS Personalizado -->
    <script src="<?= BASE_URL ?>/../src/js/main.js"></script>
    
    <script>
    // Configuración global
    const SITE_CONFIG = {
        whatsapp: {
            number: '<?= $whatsappConfig['number'] ?>',
            defaultMessage: '<?= $whatsappConfig['default_message'] ?>'
        },
        user: {
            id: <?= $user_id ?>
        }
    };

    // Sistema de mensajes
    class MessageApp {
        constructor() {
            this.currentConversation = null;
            this.pollingInterval = null;
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadConversations();
            this.startPolling();
        }

        bindEvents() {
            // Selección de conversación
            $(document).on('click', '.conversation-item', (e) => {
                const userId = $(e.currentTarget).data('user-id');
                this.selectConversation(userId);
            });

            // Envío de mensajes
            $('#messageForm').on('submit', (e) => this.sendMessage(e));
        }

        async selectConversation(userId) {
            this.currentConversation = userId;
            $('.conversation-item').removeClass('active');
            $(`.conversation-item[data-user-id="${userId}"]`).addClass('active');
            
            // Habilitar input
            $('#messageInput').prop('disabled', false);
            $('#messageForm button').prop('disabled', false);
            
            await this.loadMessages(userId);
            this.scrollToBottom();
        }

        async loadConversations() {
            try {
                const response = await window.messageSystem.getConversations();
                if (response.success) {
                    this.renderConversations(response.data);
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        async loadMessages(userId) {
            try {
                const response = await window.messageSystem.getMessages(userId);
                if (response.success) {
                    this.renderMessages(response.data);
                    this.updateConversationHeader(userId);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        async sendMessage(e) {
            e.preventDefault();
            
            const message = $('#messageInput').val().trim();
            const receiverId = this.currentConversation;
            
            if (!message || !receiverId) return;

            try {
                const response = await window.messageSystem.sendMessage(receiverId, message);
                if (response.success) {
                    $('#messageInput').val('');
                    await this.loadMessages(receiverId);
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }

        renderConversations(conversations) {
            const container = $('#conversationsList');
            container.empty();

            conversations.forEach(conv => {
                const unreadBadge = conv.unread_count > 0 ? 
                    `<span class="badge bg-warning">${conv.unread_count}</span>` : '';
                
                const item = `
                    <div class="conversation-item" data-user-id="${conv.id}">
                        <div class="d-flex align-items-center">
                            <div class="avatar-placeholder me-3">
                                <i class="bi bi-person-circle fs-4 text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${conv.username}</h6>
                                <small class="text-muted">${conv.last_message || 'Sin mensajes'}</small>
                            </div>
                            ${unreadBadge}
                        </div>
                    </div>
                `;
                container.append(item);
            });
        }

        renderMessages(messages) {
            const container = $('#messagesList');
            container.empty();

            if (messages.length === 0) {
                container.html(`
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-quote display-4 mb-3"></i>
                        <p>No hay mensajes aún. ¡Sé el primero en escribir!</p>
                    </div>
                `);
                return;
            }

            messages.forEach(msg => {
                const isSent = msg.sender_id == SITE_CONFIG.user.id;
                const messageClass = isSent ? 'sent' : 'received';
                const time = new Date(msg.created_at).toLocaleTimeString('es-ES', { 
                    hour: '2-digit', minute: '2-digit' 
                });

                const messageHtml = `
                    <div class="message ${messageClass} p-3">
                        <div class="message-content">${this.escapeHtml(msg.message)}</div>
                        <small class="message-time d-block mt-1 text-end">${time}</small>
                    </div>
                `;
                container.append(messageHtml);
            });
        }

        updateConversationHeader(userId) {
            // En una implementación real, obtendrías los datos del usuario
            const conversationItem = $(`.conversation-item[data-user-id="${userId}"]`);
            const username = conversationItem.find('h6').text();
            
            $('#currentConversationUser').text(username);
            $('#currentConversationStatus').text('En línea');
            $('#currentReceiverId').val(userId);
        }

        scrollToBottom() {
            const container = $('#messagesList');
            container.scrollTop(container[0].scrollHeight);
        }

        startPolling() {
            // Polling para nuevos mensajes cada 5 segundos
            this.pollingInterval = setInterval(() => {
                if (this.currentConversation) {
                    this.loadMessages(this.currentConversation);
                }
                this.loadConversations();
            }, 5000);
        }

        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    }

    // Inicializar la app de mensajes cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        window.messageApp = new MessageApp();
    });
    </script>
</body>
</html>