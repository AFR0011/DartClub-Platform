<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

app_start_session();

if (!is_manager_or_admin()) {
    http_response_code(403);
    exit('Forbidden');
}

$applicationId = isset($_GET['application_id']) ? (int) $_GET['application_id'] : 0;
if ($applicationId <= 0) {
    http_response_code(400);
    exit('Invalid application.');
}

$stmt = $conn->prepare(
    'SELECT application_file_path, original_filename
     FROM membership_applications
     WHERE application_id = ?'
);
$stmt->bind_param('i', $applicationId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    http_response_code(404);
    exit('Application not found.');
}

$storedPath = str_replace('\\', '/', (string) $application['application_file_path']);
$expectedPrefix = '/files/applications/membership/';
if (!str_starts_with($storedPath, $expectedPrefix)) {
    http_response_code(404);
    exit('Application file is unavailable.');
}

$filename = basename($storedPath);
$absoluteDirectory = realpath(__DIR__ . '/../files/applications/membership');
$absolutePath = realpath(__DIR__ . '/../files/applications/membership/' . $filename);

if ($absoluteDirectory === false || $absolutePath === false || !is_file($absolutePath)) {
    http_response_code(404);
    exit('Application file is unavailable.');
}

$directoryPrefix = rtrim($absoluteDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (!str_starts_with($absolutePath, $directoryPrefix)) {
    http_response_code(404);
    exit('Application file is unavailable.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $absolutePath) : false;
if ($finfo) {
    finfo_close($finfo);
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowedMimes = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
    'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/octet-stream',
    ],
];

if (!isset($allowedMimes[$extension]) || !in_array((string) $mime, $allowedMimes[$extension], true)) {
    http_response_code(415);
    exit('Unsupported application file.');
}

$downloadName = trim((string) ($application['original_filename'] ?? ''));
if ($downloadName === '') {
    $downloadName = 'membership-application.' . $extension;
}
$downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($downloadName)) ?: ('membership-application.' . $extension);
$disposition = $extension === 'pdf' ? 'inline' : 'attachment';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($downloadName, '"\\') . '"');
readfile($absolutePath);
exit();
