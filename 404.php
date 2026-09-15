<?php
/**
 * Página 404 de FaunAR.
 *
 * Antes de mostrar el error, intenta resolver la ruta solicitada ignorando
 * mayúsculas/minúsculas (el sistema de archivos del servidor sí las distingue).
 * Si encuentra una coincidencia exacta salvo por el uso de mayúsculas,
 * redirige con 301 a la forma canónica. Si no, muestra el error en español.
 */

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = $requestPath !== null ? rawurldecode($requestPath) : '/';

$segments = array_values(array_filter(explode('/', $requestPath), fn($s) => $s !== ''));

if ($docRoot !== '' && is_dir($docRoot) && !empty($segments)) {
    $resolved = [];
    $currentDir = $docRoot;
    $allResolved = true;

    foreach ($segments as $seg) {
        if (!is_dir($currentDir)) {
            $allResolved = false;
            break;
        }
        $entries = @scandir($currentDir);
        if ($entries === false) {
            $allResolved = false;
            break;
        }
        $match = null;
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (strcasecmp($entry, $seg) === 0) {
                $match = $entry;
                break;
            }
        }
        if ($match === null) {
            $allResolved = false;
            break;
        }
        $resolved[] = $match;
        $currentDir = $currentDir . '/' . $match;
    }

    if ($allResolved) {
        $canonicalPath = '/' . implode('/', $resolved);
        if (is_dir($currentDir)) {
            $canonicalPath .= '/';
        }
        if ($canonicalPath !== $requestPath) {
            $query = $_SERVER['QUERY_STRING'] ?? '';
            $location = $canonicalPath . ($query !== '' ? '?' . $query : '');
            header('Location: ' . $location, true, 301);
            exit;
        }
    }
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Página no encontrada — FaunAR</title>
<meta name="robots" content="noindex, follow">
<style>
    :root { color-scheme: light dark; }
    body {
        font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        background: #16110C;
        color: #D9CFBE;
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        box-sizing: border-box;
    }
    .wrap { max-width: 480px; }
    h1 { font-size: 1.6rem; margin-bottom: .5rem; color: #D08303; }
    p { line-height: 1.5; }
    a.btn {
        display: inline-block;
        margin-top: 1.5rem;
        padding: 12px 24px;
        min-height: 44px;
        background: #D08303;
        color: #16110C;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
    }
    a.btn:focus-visible { outline: 3px solid #D9CFBE; outline-offset: 2px; }
</style>
</head>
<body>
    <div class="wrap">
        <h1>Página no encontrada</h1>
        <p>La dirección que buscás no existe o fue movida.</p>
        <a class="btn" href="/faunAr/">Volver al portal de FaunAR</a>
    </div>
</body>
</html>
