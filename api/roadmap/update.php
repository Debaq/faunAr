<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$task_id = $input['task_id'] ?? null;

if (empty($task_id) || empty($input['title'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tarea y título son obligatorios.']);
    exit();
}

$roadmap_file = __DIR__ . '/../../data/roadmap.json';
$data = json_decode(file_get_contents($roadmap_file), true);

$task_found = false;
foreach ($data['tasks'] as &$task) {
    if ($task['id'] === $task_id) {
        $task['title'] = $input['title'];
        $task['description'] = $input['description'] ?? $task['description'];
        $task['status'] = $input['status'] ?? $task['status'];
        $task['due_date'] = $input['due_date'] ?? $task['due_date'];
        $task_found = true;
        break;
    }
}

if (!$task_found) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tarea no encontrada.']);
    exit();
}

if (file_put_contents($roadmap_file, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Tarea actualizada.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la tarea.']);
}
?>
