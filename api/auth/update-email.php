<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_user'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'El correo y la contraseña son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit;
}

$usersFilePath = __DIR__ . '/../../data/users.json';
if (!file_exists($usersFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: no se encontró el archivo de usuarios.']);
    exit;
}

$usersData = json_decode(file_get_contents($usersFilePath), true);
if ($usersData === null) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: no se pudo leer el archivo de usuarios.']);
    exit;
}

$currentUser = null;
$userIndex = -1;
foreach ($usersData['users'] as $index => $user) {
    if ($user['id'] === $_SESSION['admin_user']['id']) {
        $currentUser = $user;
        $userIndex = $index;
        break;
    }
}

if ($currentUser === null) {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: no se encontró al usuario actual.']);
    exit;
}

if (!password_verify($password, $currentUser['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'La contraseña es incorrecta.']);
    exit;
}

// Update user's email
$usersData['users'][$userIndex]['email'] = $email;

// Update session
$_SESSION['admin_user']['email'] = $email;

if (file_put_contents($usersFilePath, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Correo electrónico actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: no se pudo guardar el nuevo correo.']);
}
?>
