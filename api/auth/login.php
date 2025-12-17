<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Leer datos del request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Usuario y contraseña requeridos']);
    exit();
}

// Leer archivo de usuarios
$usersFile = __DIR__ . '/../../data/users.json';
if (!file_exists($usersFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
    exit();
}

$usersData = json_decode(file_get_contents($usersFile), true);
$users = $usersData['users'] ?? [];

// Buscar usuario
$user = null;
foreach ($users as &$u) {
    if ($u['username'] === $username) {
        $user = &$u;
        break;
    }
}

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
    exit();
}

// Verificar contraseña
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
    exit();
}

// Actualizar último login
$user['last_login'] = date('Y-m-d\TH:i:s\Z');
file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Crear sesión
session_regenerate_id(true);
$_SESSION['admin_user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role']
];
$_SESSION['LAST_ACTIVITY'] = time();

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'user' => $_SESSION['admin_user'],
    'session_id' => session_id()
]);
?>
