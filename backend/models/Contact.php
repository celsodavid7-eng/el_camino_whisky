<?php
class Contact {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Crear tabla si no existe
        $this->createTable();
    }
    
    private function createTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(500) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }
    
    public function create($name, $email, $subject, $message) {
        $stmt = $this->pdo->prepare("
            INSERT INTO contact_messages (name, email, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $email, $subject, $message]);
    }
    
    public function getAll($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM contact_messages 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getUnreadCount() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function markAsRead($id) {
        $stmt = $this->pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>