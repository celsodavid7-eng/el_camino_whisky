<?php
/**
 * Modelo WhiskyEvent - Gestión de eventos y catas
 */
class WhiskyEvent {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todos los eventos activos (futuros y presentes)
     */
    public function getAllActive() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM events 
                WHERE is_active = 1 
                AND (event_date >= CURDATE())
                ORDER BY event_date ASC, event_time ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting active events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener eventos destacados
     */
    public function getFeaturedEvents() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM events 
                WHERE is_active = 1 
                AND is_featured = 1
                AND (event_date >= CURDATE())
                ORDER BY event_date ASC 
                LIMIT 3
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting featured events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener próximos eventos (limite)
     */
    public function getUpcomingEvents($limit = 6) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM events 
                WHERE is_active = 1 
                AND (event_date >= CURDATE())
                ORDER BY event_date ASC, event_time ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting upcoming events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todos los eventos (para admin)
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM events 
                ORDER BY event_date DESC, event_time DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all events: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener evento por ID
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting event by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear nuevo evento
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO events (title, description, event_type, location, address, event_date, event_time, duration, price, max_participants, image_path, is_active, is_featured, registration_link) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['event_type'],
                $data['location'],
                $data['address'],
                $data['event_date'],
                $data['event_time'],
                $data['duration'],
                $data['price'],
                $data['max_participants'],
                $data['image_path'],
                $data['is_active'],
                $data['is_featured'],
                $data['registration_link']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar evento
     */
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE events 
                SET title = ?, description = ?, event_type = ?, location = ?, address = ?, event_date = ?, event_time = ?, duration = ?, price = ?, max_participants = ?, image_path = ?, is_active = ?, is_featured = ?, registration_link = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['event_type'],
                $data['location'],
                $data['address'],
                $data['event_date'],
                $data['event_time'],
                $data['duration'],
                $data['price'],
                $data['max_participants'],
                $data['image_path'],
                $data['is_active'],
                $data['is_featured'],
                $data['registration_link'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar evento
     */
    public function delete($id) {
        try {
            // Primero obtener la imagen para eliminarla del filesystem
            $event = $this->getById($id);
            if ($event && $event['image_path']) {
                $imagePath = __DIR__ . '/../uploads/events/' . $event['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting event: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manejar upload de imagen
     */
    public function handleImageUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $uploadDir = __DIR__ . '/../uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            return null;
        }
        
        $fileName = 'event_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            return $fileName;
        }
        
        return null;
    }
    
    /**
     * Obtener eventos por mes
     */
    public function getEventsByMonth($year, $month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM events 
                WHERE is_active = 1 
                AND YEAR(event_date) = ? 
                AND MONTH(event_date) = ?
                AND (event_date >= CURDATE())
                ORDER BY event_date ASC, event_time ASC
            ");
            $stmt->execute([$year, $month]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting events by month: " . $e->getMessage());
            return [];
        }
    }
}
?>