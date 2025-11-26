<?php
session_start();
require_once '../config/database.php';

// Verificar si es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Incluir el modelo WhislyEvents
require_once '../models/WhiskyEvent.php';
$eventModel = new WhiskyEvent($pdo);

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_event'])) {
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'event_type' => $_POST['event_type'],
            'location' => $_POST['location'],
            'address' => $_POST['address'],
            'event_date' => $_POST['event_date'],
            'event_time' => $_POST['event_time'],
            'duration' => $_POST['duration'],
            'price' => $_POST['price'],
            'max_participants' => $_POST['max_participants'],
            'image_path' => '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'registration_link' => $_POST['registration_link']
        ];
        
        // Manejar upload de imagen
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $data['image_path'] = $eventModel->handleImageUpload($_FILES['image']);
        }
        
        if ($eventModel->create($data)) {
            $_SESSION['success'] = 'Evento agregado correctamente';
        } else {
            $_SESSION['error'] = 'Error al agregar el evento';
        }
    }
    
    if (isset($_POST['update_event'])) {
        $id = $_POST['id'];
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'event_type' => $_POST['event_type'],
            'location' => $_POST['location'],
            'address' => $_POST['address'],
            'event_date' => $_POST['event_date'],
            'event_time' => $_POST['event_time'],
            'duration' => $_POST['duration'],
            'price' => $_POST['price'],
            'max_participants' => $_POST['max_participants'],
            'image_path' => $_POST['current_image'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'registration_link' => $_POST['registration_link']
        ];
        
        // Manejar upload de nueva imagen
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            // Eliminar imagen anterior si existe
            if ($data['image_path'] && file_exists('../uploads/events/' . $data['image_path'])) {
                unlink('../uploads/events/' . $data['image_path']);
            }
            $data['image_path'] = $eventModel->handleImageUpload($_FILES['image']);
        }
        
        if ($eventModel->update($id, $data)) {
            $_SESSION['success'] = 'Evento actualizado correctamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar el evento';
        }
    }
    
    if (isset($_POST['delete_event'])) {
        $id = $_POST['id'];
        
        if ($eventModel->delete($id)) {
            $_SESSION['success'] = 'Evento eliminado correctamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el evento';
        }
    }
    
    header('Location: events.php');
    exit;
}

// Obtener eventos
$events = $eventModel->getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">

    <style>
        .sidebar { background: #1a1a1a; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 12px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #D4AF37; color: #000; }
        .event-image-admin { max-height: 80px; max-width: 120px; object-fit: cover; border-radius: 4px; }
        .event-type-badge { font-size: 0.7rem; }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Incluir Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="title-font">Gestión de Eventos y Catas</h2>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="bi bi-calendar-plus me-2"></i>Nuevo Evento
                    </button>
                </div>

                <!-- Mensajes de éxito/error -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Tabla de Eventos -->
                <div class="card bg-dark border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Lista de Eventos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Fecha y Hora</th>
                                        <th>Lugar</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($events)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-calendar-x display-4 d-block mb-2"></i>
                                                No hay eventos registrados
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($event['image_path']): ?>
                                                        <img src="../uploads/events/<?= $event['image_path'] ?>" 
                                                             alt="<?= htmlspecialchars($event['title']) ?>" 
                                                             class="event-image-admin">
                                                    <?php else: ?>
                                                        <i class="bi bi-image text-warning fs-4"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($event['title']) ?></strong>
                                                    <?php if ($event['is_featured']): ?>
                                                        <span class="badge bg-danger ms-1">Destacado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $typeBadges = [
                                                        'tasting' => ['label' => 'Cata', 'class' => 'bg-primary'],
                                                        'workshop' => ['label' => 'Workshop', 'class' => 'bg-success'],
                                                        'masterclass' => ['label' => 'Masterclass', 'class' => 'bg-purple'],
                                                        'social' => ['label' => 'Social', 'class' => 'bg-info']
                                                    ];
                                                    $type = $event['event_type'];
                                                    ?>
                                                    <span class="badge event-type-badge <?= $typeBadges[$type]['class'] ?>">
                                                        <?= $typeBadges[$type]['label'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= date('d/m/Y', strtotime($event['event_date'])) ?><br>
                                                        <?= date('H:i', strtotime($event['event_time'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($event['location']) ?></small>
                                                </td>
                                                <td>
                                                    <strong class="text-warning">$<?= number_format($event['price'], 2) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $event['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $event['is_active'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editEventModal"
                                                                onclick="editEvent(<?= htmlspecialchars(json_encode($event)) ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                                            <button type="submit" name="delete_event" 
                                                                    class="btn btn-outline-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar este evento?')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Evento -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-warning">
                    <h5 class="modal-title">Agregar Nuevo Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título del Evento *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Evento</label>
                                    <select class="form-select" name="event_type">
                                        <option value="tasting">Cata</option>
                                        <option value="workshop">Workshop</option>
                                        <option value="masterclass">Masterclass</option>
                                        <option value="social">Evento Social</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lugar/Nombre</label>
                                    <input type="text" class="form-control" name="location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" name="address">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha *</label>
                                    <input type="date" class="form-control" name="event_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Hora *</label>
                                    <input type="time" class="form-control" name="event_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duración</label>
                                    <input type="text" class="form-control" name="duration" placeholder="Ej: 2 horas">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio ($)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo de Participantes</label>
                                    <input type="number" class="form-control" name="max_participants">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Imagen del Evento</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Link de Registro (WhatsApp)</label>
                            <input type="url" class="form-control" name="registration_link" placeholder="https://wa.me/...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Evento Activo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                    <label class="form-check-label" for="is_featured">Evento Destacado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-warning">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_event" class="btn btn-warning">Guardar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Evento -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-warning">
                    <h5 class="modal-title">Editar Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título del Evento *</label>
                                    <input type="text" class="form-control" name="title" id="edit_title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Evento</label>
                                    <select class="form-select" name="event_type" id="edit_event_type">
                                        <option value="tasting">Cata</option>
                                        <option value="workshop">Workshop</option>
                                        <option value="masterclass">Masterclass</option>
                                        <option value="social">Evento Social</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lugar/Nombre</label>
                                    <input type="text" class="form-control" name="location" id="edit_location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" name="address" id="edit_address">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha *</label>
                                    <input type="date" class="form-control" name="event_date" id="edit_event_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Hora *</label>
                                    <input type="time" class="form-control" name="event_time" id="edit_event_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duración</label>
                                    <input type="text" class="form-control" name="duration" id="edit_duration" placeholder="Ej: 2 horas">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio ($)</label>
                                    <input type="number" class="form-control" name="price" id="edit_price" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo de Participantes</label>
                                    <input type="number" class="form-control" name="max_participants" id="edit_max_participants">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Imagen del Evento</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="current_image_preview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Link de Registro (WhatsApp)</label>
                            <input type="url" class="form-control" name="registration_link" id="edit_registration_link" placeholder="https://wa.me/...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">Evento Activo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured">
                                    <label class="form-check-label" for="edit_is_featured">Evento Destacado</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-warning">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_event" class="btn btn-warning">Actualizar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEvent(event) {
            document.getElementById('edit_id').value = event.id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_description').value = event.description || '';
            document.getElementById('edit_event_type').value = event.event_type;
            document.getElementById('edit_location').value = event.location || '';
            document.getElementById('edit_address').value = event.address || '';
            document.getElementById('edit_event_date').value = event.event_date;
            document.getElementById('edit_event_time').value = event.event_time;
            document.getElementById('edit_duration').value = event.duration || '';
            document.getElementById('edit_price').value = event.price;
            document.getElementById('edit_max_participants').value = event.max_participants || '';
            document.getElementById('edit_registration_link').value = event.registration_link || '';
            document.getElementById('edit_is_active').checked = event.is_active == 1;
            document.getElementById('edit_is_featured').checked = event.is_featured == 1;
            document.getElementById('edit_current_image').value = event.image_path || '';
            
            // Mostrar preview de la imagen actual
            const preview = document.getElementById('current_image_preview');
            if (event.image_path) {
                preview.innerHTML = `
                    <small class="text-muted">Imagen actual:</small><br>
                    <img src="../uploads/events/${event.image_path}" 
                         alt="${event.title}" 
                         class="event-image-admin mt-1">
                `;
            } else {
                preview.innerHTML = '<small class="text-muted">No hay imagen actual</small>';
            }
            
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }
    </script>
</body>
</html>