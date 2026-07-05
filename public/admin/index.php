<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/storage.php';

$storage = resolveUploadStorage();
$uploadsDir = $storage['uploadsDir'];
$publicBase = $storage['publicBase'];
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . rtrim($publicBase, '/');

$files = [];
if (is_dir($uploadsDir)) {
    $entries = scandir($uploadsDir) ?: [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || strpos($entry, '.') === 0) {
            continue;
        }
        $path = $uploadsDir . '/' . $entry;
        if (!is_file($path)) {
            continue;
        }
        $files[] = [
            'name' => $entry,
            'size' => filesize($path) ?: 0,
            'mtime' => filemtime($path) ?: 0,
            'url' => $baseUrl . '/' . rawurlencode($entry)
        ];
    }
}

usort(
    $files,
    static function (array $a, array $b): int {
        return $b['mtime'] <=> $a['mtime'];
    }
);

$status = $_GET['status'] ?? '';
$error = $_GET['error'] ?? '';

$formatBytes = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float) $bytes;
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return sprintf('%.1f %s', $size, $units[$unitIndex]);
};
?>
<!doctype html>
<html lang="ja">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>アップロード管理</title>
    <link rel="stylesheet" href="../assets/admin.css" />
  </head>
  <body>
    <main class="admin-card">
      <div class="header">
        <h1>アップロード管理</h1>
        <p class="meta">保存先: <?= htmlspecialchars($publicBase, ENT_QUOTES, 'UTF-8') ?></p>
      </div>

      <?php if ($status === 'deleted'): ?>
        <p class="notice success">削除しました。</p>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <p class="notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ファイル名</th>
              <th>サイズ</th>
              <th>更新日時</th>
              <th>URL</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($files) === 0): ?>
              <tr>
                <td colspan="5" class="empty">ファイルがありません。</td>
              </tr>
            <?php else: ?>
              <?php foreach ($files as $file): ?>
                <tr>
                  <td class="name"><?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($formatBytes($file['size']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d H:i', $file['mtime']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="url">
                    <a href="<?= htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                      Open
                    </a>
                  </td>
                  <td>
                    <form method="post" action="delete.php" onsubmit="return confirm('削除しますか？');">
                      <input type="hidden" name="file" value="<?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?>" />
                      <button type="submit" class="danger">削除</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </body>
</html>
