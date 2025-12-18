<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_user'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$currentPassword = $input['currentPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

// --- Validation ---
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Las nuevas contraseñas no coinciden.']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
    exit;
}

// --- Logic ---
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

// Find the current user
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

// Verify current password
if (!password_verify($currentPassword, $currentUser['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
    exit;
}

// Hash the new password
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update user's password
$usersData['users'][$userIndex]['password_hash'] = $newPasswordHash;

// Save the updated data
if (file_put_contents($usersFilePath, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error del sistema: no se pudo guardar la nueva contraseña.']);
}
?>
