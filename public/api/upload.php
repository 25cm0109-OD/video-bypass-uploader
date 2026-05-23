<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$respondError = static function (string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    echo json_encode(
        ['success' => false, 'error' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respondError('POSTメソッドのみ対応しています。', 405);
}

if (!isset($_FILES['file'])) {
    $respondError('アップロードファイルが見つかりません。');
}

$file = $_FILES['file'];

if (!isset($file['error']) || is_array($file['error'])) {
    $respondError('アップロード形式が不正です。');
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMap = [
        UPLOAD_ERR_INI_SIZE => 'ファイルサイズが大きすぎます。',
        UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが大きすぎます。',
        UPLOAD_ERR_PARTIAL => 'ファイルのアップロードが途中で失敗しました。',
        UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません。',
        UPLOAD_ERR_NO_TMP_DIR => '一時保存ディレクトリが見つかりません。',
        UPLOAD_ERR_CANT_WRITE => 'ディスクへの書き込みに失敗しました。',
        UPLOAD_ERR_EXTENSION => '拡張機能によりアップロードが停止されました。'
    ];
    $respondError($errorMap[$file['error']] ?? 'アップロードに失敗しました。');
}

$maxBytes = 200 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    $respondError('ファイルサイズは200MB以下にしてください。');
}

$allowedExtensions = [
    'mp4' => [
        'video/mp4',
        'application/mp4',
        'video/quicktime',
        'video/x-m4v',
        'application/x-m4v',
        'video/mp4v-es'
    ],
    'webm' => ['video/webm'],
    'ogg' => ['video/ogg', 'application/ogg'],
    'mov' => ['video/quicktime']
];

$originalName = $file['name'] ?? '';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($extension === '' || !isset($allowedExtensions[$extension])) {
    $respondError('許可されていないファイル形式です。');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($file['tmp_name']) ?: '';
$allowedMimes = $allowedExtensions[$extension];
$clientMime = $file['type'] ?? '';

if (!in_array($detectedMime, $allowedMimes, true)) {
    $canFallback = $detectedMime === '' || $detectedMime === 'application/octet-stream';
    if (!$canFallback && !in_array($clientMime, $allowedMimes, true)) {
        $respondError('ファイル形式が正しくありません。');
    }
}

require_once __DIR__ . '/storage.php';
$storage = resolveUploadStorage();
$uploadsDir = $storage['uploadsDir'];
$publicBase = $storage['publicBase'];

if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
    $respondError('保存先ディレクトリを作成できませんでした。', 500);
}

$baseName = bin2hex(random_bytes(16));
$filename = $baseName . '.' . $extension;
$destination = $uploadsDir . '/' . $filename;

if ($extension === 'mov') {
    $ffmpegPath = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
    if ($ffmpegPath === '') {
        $respondError('サーバーに変換ツールがありません。', 500);
    }

    $sourcePath = $uploadsDir . '/' . $baseName . '_source.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $sourcePath)) {
        $respondError('ファイルの保存に失敗しました。', 500);
    }

    $filename = $baseName . '.mp4';
    $destination = $uploadsDir . '/' . $filename;
    $filter = 'scale=iw:ih,setsar=1';
    $command = sprintf(
        '%s -hide_banner -loglevel error -y -i %s -map 0:v:0 -map 0:a? -vf %s -metadata:s:v rotate=0 -c:v libx264 -preset veryfast -crf 23 -c:a aac -movflags +faststart %s',
        escapeshellcmd($ffmpegPath),
        escapeshellarg($sourcePath),
        escapeshellarg($filter),
        escapeshellarg($destination)
    );

    $exitCode = 0;
    exec($command, $outputLines, $exitCode);
    if (is_file($sourcePath)) {
        unlink($sourcePath);
    }
    if ($exitCode !== 0 || !is_file($destination)) {
        $respondError('動画の変換に失敗しました。', 500);
    }
} else {
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $respondError('ファイルの保存に失敗しました。', 500);
    }
}

chmod($destination, 0644);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$url = sprintf('%s://%s%s/%s', $scheme, $host, rtrim($publicBase, '/'), $filename);

echo json_encode(
    ['success' => true, 'url' => $url],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
