<?php

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_url(string $page, array $params = []): string
{
    $url = BASE_URL . 'admin/' . $page;

    $action = $params['action'] ?? null;
    $id = $params['id'] ?? null;
    unset($params['action'], $params['id']);

    if ($action !== null && $action !== '') {
        $url .= '/' . rawurlencode((string)$action);
    }
    if ($id !== null && $id !== '') {
        $url .= '/' . (int)$id;
    }
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

function num_short(float|int|null $value): string
{
    $value = (float)($value ?? 0);
    if ($value >= 1000000) {
        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
    }
    if ($value >= 1000) {
        return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
    }
    return number_format($value);
}

function money(float|int|null $value, string $currency = '$'): string
{
    return $currency . number_format((float)($value ?? 0));
}

function app_media_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^[A-Za-z]:/#', '/', $path);
    $publicPos = stripos($path, '/public/');
    if ($publicPos !== false) {
        $path = substr($path, $publicPos + 8);
    }
    $path = preg_replace('#^/?public/#i', '', $path);
    $path = ltrim($path, '/');

    if (!str_contains($path, '/')) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'mov', 'm4v', 'webm', 'ogg', 'ogv', 'avi', 'mkv'], true)) {
            $path = 'uploads/videos/' . $path;
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
            $path = 'uploads/thumbnails/' . $path;
        }
    }

    if ($path === '') {
        return '';
    }

    return BASE_URL . implode('/', array_map('rawurlencode', explode('/', $path)));
}

function app_video_mime(?string $path): string
{
    $ext = strtolower(pathinfo(parse_url((string)$path, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    return match ($ext) {
        'webm' => 'video/webm',
        'ogg', 'ogv' => 'video/ogg',
        'mov' => 'video/quicktime',
        'm4v' => 'video/x-m4v',
        default => 'video/mp4',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'active', 'published', 'success', 'approved', 'resolved' => 'pill-green',
        'pending', 'processing', 'suspended' => 'pill-amber',
        'draft', 'free', 'basic' => 'pill-blue',
        default => 'pill-red',
    };
}

function ago(?string $date): string
{
    if (!$date) {
        return 'Never';
    }
    $seconds = max(1, time() - strtotime($date));
    if ($seconds < 3600) {
        return floor($seconds / 60) . ' minutes ago';
    }
    if ($seconds < 86400) {
        return floor($seconds / 3600) . ' hours ago';
    }
    if ($seconds < 604800) {
        return floor($seconds / 86400) . ' days ago';
    }
    if ($seconds < 2592000) {
        return floor($seconds / 604800) . ' weeks ago';
    }
    return floor($seconds / 2592000) . ' month ago';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
