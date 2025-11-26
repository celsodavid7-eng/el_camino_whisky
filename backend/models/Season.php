<?php
class Season {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, 
                   COUNT(c.id) as chapter_count
            FROM seasons s 
            LEFT JOIN chapters c ON s.id = c.season_id 
            GROUP BY s.id 
            ORDER BY s.display_order ASC, s.created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM seasons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getPublished() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM seasons 
            WHERE is_published = 1 AND is_active = 1 
            ORDER BY display_order ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO seasons (title, subtitle, description, price, requires_payment, display_order, is_published, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['title'],
            $data['subtitle'] ?? '',
            $data['description'] ?? '',
            $data['price'] ?? 0,
            $data['requires_payment'] ?? 0,
            $data['display_order'] ?? 0,
            $data['is_published'] ?? 0,
            $_SESSION['user_id'] ?? 1
        ]);
    }
    
    public function update($id, $data) {
        $allowedFields = ['title', 'subtitle', 'description', 'price', 'requires_payment', 'display_order', 'is_published'];
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $params[] = $id;
        $sql = "UPDATE seasons SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        // Verificar si hay capítulos asociados
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM chapters WHERE season_id = ?");
        $stmt->execute([$id]);
        $chapterCount = $stmt->fetchColumn();
        
        if ($chapterCount > 0) {
            return false; // No se puede eliminar si tiene capítulos
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM seasons WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function togglePublish($id) {
        $stmt = $this->pdo->prepare("UPDATE seasons SET is_published = NOT is_published WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>