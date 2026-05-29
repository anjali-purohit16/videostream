<?php


function u_normalize_plan(string $name): string {
    $n = strtolower(trim($name));
    if (str_contains($n, 'premium')) return 'premium';
    if (str_contains($n, 'basic'))   return 'basic';
    return 'free';
}

function u_page_url(string $page, array $params = []): string {
    $paths = [
        'home'         => '',
        'movies'       => 'movies',
        'trending'     => 'trending',
        'categories'   => 'categories',
        'watchlist'    => 'watchlist',
        'history'      => 'history',
        'profile'      => 'profile',
        'subscription' => 'subscription',
    ];
    $url = BASE_URL . ($paths[$page] ?? ltrim($page, '/'));
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function u_icon(string $name): string
{
    return '<i class="bi ' . h($name) . '" aria-hidden="true"></i>';
}

function u_media_url(?string $path): string
{
    return app_media_url($path);
}

function u_video_payload(array $item, string $idKey = 'id'): string
{
    global $userPlanLevel;
    $rank = ['free' => 0, 'basic' => 1, 'premium' => 2];
    $accessLevel = strtolower($item['access_level'] ?? 'free');
    $canWatch = ($rank[$userPlanLevel] ?? 0) >= ($rank[$accessLevel] ?? 0);
    $filePath = u_media_url($item['file_path'] ?? '');
    $thumbUrl = u_media_url($item['thumbnail'] ?? '');
    return h(json_encode([
        'id' => (int)($item[$idKey] ?? $item['id'] ?? 0),
        'title' => $item['title'] ?? '',
        'filePath' => $filePath,
        'thumbUrl' => $thumbUrl,
        'desc' => $item['description'] ?? '',
        'category' => $item['category'] ?? '',
        'accessLevel' => $accessLevel,
        'canWatch' => $canWatch,
        'durationSec' => (int)($item['duration_sec'] ?? 0),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function u_access_label(array $item): string
{
    return ucfirst(strtolower($item['access_level'] ?? 'free'));
}

function u_access_class(array $item): string
{
    return 'badge-access-' . strtolower($item['access_level'] ?? 'free');
}

function u_hd_badge(array $item): string
{
    $q = strtolower($item['quality'] ?? $item['resolution'] ?? '');
    $isHd = ($q && (str_contains($q, 'hd') || str_contains($q, '1080') || str_contains($q, '4k') || str_contains($q, '720')));
    if (!$isHd) return '';
    return '<div class="u-card-badge u-badge-hd">HD</div>';
}

function u_plan_badge(array $item): string
{
    $level = strtolower($item['access_level'] ?? 'free');
    $label = ucfirst($level);
    return '<div class="u-card-badge u-card-badge-right u-badge-plan badge-plan-' . h($level) . '">' . h($label) . '</div>';
}

function u_plan_badge_left(array $item): string
{
    $level = strtolower($item['access_level'] ?? 'free');
    $label = $level === 'free' ? 'Free' : ucfirst($level);
    $cls   = 'badge-plan-' . h($level);
    return '<div class="u-card-badge u-badge-plan u-badge-plan-left ' . $cls . '">' . h($label) . '</div>';
}

$uPlanRank = ['free' => 0, 'basic' => 1, 'premium' => 2];
$userPlanLevel = u_normalize_plan($userProfile['plan'] ?? 'free');
if (!empty($subscription) && strtolower($subscription['sub_status'] ?? '') === 'active') {
    $subPlanLevel = u_normalize_plan($subscription['plan_name'] ?? 'free');
    if (($uPlanRank[$subPlanLevel] ?? 0) > ($uPlanRank[$userPlanLevel] ?? 0)) {
        $userPlanLevel = $subPlanLevel;
    }
}
