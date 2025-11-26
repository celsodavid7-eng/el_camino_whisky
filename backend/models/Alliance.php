<?php
/**
 * Modelo Alliance - Gestión de alianzas
 */
class Alliance {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener todas las alianzas activas
     */
    public function getAllActive() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM alliances 
                WHERE is_active = 1 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting active alliances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todas las alianzas (para admin)
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM alliances 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all alliances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener alianza por ID
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM alliances WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting alliance by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear nueva alianza
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO alliances (name, description, website, logo, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['name'],
                $data['description'],
                $data['website'],
                $data['logo'],
                $data['is_active']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating alliance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar alianza
     */
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE alliances 
                SET name = ?, description = ?, website = ?, logo = ?, is_active = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['name'],
                $data['description'],
                $data['website'],
                $data['logo'],
                $data['is_active'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating alliance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar alianza
     */
    public function delete($id) {
        try {
            // Primero obtener el logo para eliminarlo del filesystem
            $alliance = $this->getById($id);
            if ($alliance && $alliance['logo']) {
                $logoPath = __DIR__ . '/../uploads/alliances/' . $alliance['logo'];
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM alliances WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting alliance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manejar upload de logo
     */
    public function handleLogoUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $uploadDir = __DIR__ . '/../uploads/alliances/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            return null;
        }
        
        $fileName = 'alliance_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            return $fileName;
        }
        
        return null;
    }
}
?>