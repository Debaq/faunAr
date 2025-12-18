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

if (empty($task_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tarea es obligatorio.']);
    exit();
}

$roadmap_file = __DIR__ . '/../../data/roadmap.json';
$data = json_decode(file_get_contents($roadmap_file), true);

$original_count = count($data['tasks']);
$data['tasks'] = array_filter($data['tasks'], function($task) use ($task_id) {
    return $task['id'] !== $task_id;
});

// Re-index array
$data['tasks'] = array_values($data['tasks']);

if (count($data['tasks']) === $original_count) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tarea no encontrada.']);
    exit();
}

if (file_put_contents($roadmap_file, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Tarea eliminada.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la tarea.']);
}
?>
