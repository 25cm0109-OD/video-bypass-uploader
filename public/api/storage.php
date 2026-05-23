<?php
declare(strict_types=1);

function resolveUploadStorage(): array
{
    $uploadsDir = dirname(__DIR__) . '/uploads';
    $apiDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(dirname($apiDir), '/');
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    }

    return [
        'uploadsDir' => $uploadsDir,
        'publicBase' => $basePath . '/uploads'
    ];
}
