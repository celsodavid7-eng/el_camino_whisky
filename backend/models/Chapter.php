<?php
class Chapter {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, s.title as season_title 
            FROM chapters c 
            LEFT JOIN seasons s ON c.season_id = s.id 
            ORDER BY c.created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPublishedBySeason($seasonId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM chapters 
            WHERE season_id = ? AND is_published = 1 
            ORDER BY chapter_number ASC
        ");
        $stmt->execute([$seasonId]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chapters (season_id, title, subtitle, content, chapter_number, is_free, display_order, is_published, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['season_id'],
            $data['title'],
            $data['subtitle'] ?? '',
            $data['content'],
            $data['chapter_number'],
            $data['is_free'] ?? 0,
            $data['display_order'] ?? 0,
            $data['is_published'] ?? 0,
            $_SESSION['user_id'] ?? 1
        ]);
    }
    
    public function update($id, $data) {
        $allowedFields = ['season_id', 'title', 'subtitle', 'content', 'chapter_number', 'is_free', 'is_published', 'display_order'];
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
        $sql = "UPDATE chapters SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        // Primero eliminar imágenes asociadas
        $stmt = $this->pdo->prepare("SELECT image_path FROM chapter_images WHERE chapter_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            $filePath = '../../uploads/' . $image['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Eliminar registros de imágenes
        $this->pdo->prepare("DELETE FROM chapter_images WHERE chapter_id = ?")->execute([$id]);
        
        // Eliminar categorías asociadas
        $this->pdo->prepare("DELETE FROM chapter_categories WHERE chapter_id = ?")->execute([$id]);
        
        // Eliminar comentarios
        $this->pdo->prepare("DELETE FROM comments WHERE chapter_id = ?")->execute([$id]);
        
        // Finalmente eliminar el capítulo
        $stmt = $this->pdo->prepare("DELETE FROM chapters WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>