<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$file = $_POST['file'] ?? '';
$basename = basename($file);
if ($basename === '' || $basename !== $file) {
    redirectWithError('ファイル名が不正です。');
}

$storage = resolveUploadStorage();
$uploadsDir = $storage['uploadsDir'];
$uploadsReal = realpath($uploadsDir);
if ($uploadsReal === false) {
    redirectWithError('保存先が見つかりません。');
}

$target = $uploadsDir . '/' . $basename;
$targetReal = realpath($target);
if ($targetReal === false || !str_starts_with($targetReal, $uploadsReal . DIRECTORY_SEPARATOR)) {
    redirectWithError('削除対象が見つかりません。');
}

if (!is_file($targetReal)) {
    redirectWithError('削除対象が見つかりません。');
}

if (!unlink($targetReal)) {
    redirectWithError('削除に失敗しました。');
}

redirectWithStatus('deleted');

function redirectWithStatus(string $status): void
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    header('Location: ' . $base . '/?status=' . urlencode($status));
    exit;
}

function redirectWithError(string $message): void
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    header('Location: ' . $base . '/?error=' . urlencode($message));
    exit;
}
