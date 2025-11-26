<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'writer')");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Credenciales incorrectas";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - El Camino del Whisky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../uploads/favicon.png">
    <style>
        body { background: #0A0A0A; }
        .login-container { max-width: 400px; margin: 100px auto; }
        .login-card { background: #1A1A1A; border-radius: 16px; border: 1px solid #D4AF37; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-droplet-half display-4 text-warning"></i>
                    <h3 class="text-warning mt-2">Admin Panel</h3>
                    <p class="text-light">El Camino del Whisky</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-light">Email</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Contraseña</label>
                        <input type="password" class="form-control bg-dark text-light border-secondary" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>