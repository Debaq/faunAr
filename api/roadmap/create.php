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

if (empty($input['title'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El título es obligatorio.']);
    exit();
}

$roadmap_file = __DIR__ . '/../../data/roadmap.json';
$data = json_decode(file_get_contents($roadmap_file), true);

$new_task = [
    'id' => uniqid('task_'),
    'title' => $input['title'],
    'description' => $input['description'] ?? '',
    'status' => $input['status'] ?? 'pendiente',
    'due_date' => $input['due_date'] ?? null,
    'created_at' => date('Y-m-d H:i:s')
];

$data['tasks'][] = $new_task;

if (file_put_contents($roadmap_file, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'task' => $new_task]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la tarea.']);
}
?>
