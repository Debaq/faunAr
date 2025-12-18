<?php
session_start();

// 1. Check for admin authentication
if (!isset($_SESSION['admin_user'])) {
    http_response_code(401);
    die('Acceso no autorizado. Por favor, inicie sesión.');
}

// Increase execution time limit, as zipping can be slow
set_time_limit(300); // 5 minutes

// 2. Define paths
$root_path = __DIR__ . '/../../';
$models_path = $root_path . 'models';

// Check if the models directory exists
if (!is_dir($models_path)) {
    http_response_code(404);
    die('Error: El directorio de modelos no existe.');
}

// 3. Use ZipArchive to create a zip file in a temporary location
$zip = new ZipArchive();
$zip_filename = tempnam(sys_get_temp_dir(), 'faunar_backup_') . '.zip';

if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    http_response_code(500);
    die('Error: No se pudo crear el archivo ZIP.');
}

// 4. Recursively add files from the models directory to the zip archive
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($models_path),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    // Skip directories (they will be added automatically)
    if (!$file->isDir()) {
        $file_path = $file->getRealPath();
        // Get relative path for insertion into zip
        $relative_path = substr($file_path, strlen($models_path) + 1);
        
        $zip->addFile($file_path, 'models/' . $relative_path);
    }
}

// Close the zip file
$zip->close();

// 5. Set the appropriate HTTP headers for download
$download_filename = 'faunar_backup_' . date('Y-m-d_His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $download_filename . '"');
header('Content-Length: ' . filesize($zip_filename));
header('Connection: close');

// Clear output buffer
ob_clean();
flush();

// 6. Send the file to the browser
readfile($zip_filename);

// 7. Delete the temporary zip file
unlink($zip_filename);

exit();
?>
