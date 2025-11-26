<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    $response['message'] = 'Email y contraseña requeridos';
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_username'] = $user['username'];
                    
                    $response['success'] = true;
                    $response['message'] = 'Login exitoso';
                    $response['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                } else {
                    $response['message'] = 'Credenciales incorrectas';
                }
                break;
                
            case 'register':
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    $response['message'] = 'Todos los campos son requeridos';
                    break;
                }
                
                // Verificar si el usuario ya existe
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetch()) {
                    $response['message'] = 'El usuario o email ya existe';
                    break;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                
                if ($stmt->execute([$username, $email, $hashedPassword])) {
                    $userId = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_username'] = $username;
                    
                    $response['success'] = true;
                    $response['message'] = 'Registro exitoso';
                    $response['user'] = [
                        'id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user'
                    ];
                } else {
                    $response['message'] = 'Error en el registro';
                }
                break;
                
            case 'logout':
                // LIMPIAR COMPLETAMENTE LA SESIÓN
                $_SESSION = array(); // Vaciar el array de sesión
                
                // Si se desea destruir la sesión completamente, borra también la cookie de sesión.
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                
                // Finalmente, destruir la sesión.
                session_destroy();
                
                $response['success'] = true;
                $response['message'] = 'Logout exitoso';
                break;
                
            default:
                $response['message'] = 'Acción no válida';
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log("Auth error: " . $e->getMessage());
}

echo json_encode($response);
?>