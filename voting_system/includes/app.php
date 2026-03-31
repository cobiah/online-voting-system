<?php

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        static $basePath = null;

        if ($basePath !== null) {
            return $basePath;
        }

        $configured = trim((string) getenv('APP_BASE_PATH'));
        if ($configured !== '') {
            $configured = '/' . trim($configured, '/');
            $basePath = $configured === '/' ? '' : $configured;
            return $basePath;
        }

        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $appRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);

        $normalize = static function (string $path): string {
            return rtrim(str_replace('\\', '/', $path), '/');
        };

        $normalizedDocumentRoot = $normalize($documentRoot);
        $normalizedAppRoot = $normalize($appRoot);

        if ($normalizedDocumentRoot !== '' && str_starts_with(strtolower($normalizedAppRoot), strtolower($normalizedDocumentRoot))) {
            $relativePath = trim(substr($normalizedAppRoot, strlen($normalizedDocumentRoot)), '/');
            $basePath = $relativePath === '' ? '' : '/' . $relativePath;
            return $basePath;
        }

        $basePath = '';
        return $basePath;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $basePath = app_base_path();

        if ($path === '') {
            return $basePath === '' ? '/' : $basePath;
        }

        return ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');
    }
}
