<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found | <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="<?= BASE_URL ?>assets/css/user.css?v=<?= @filemtime(ROOT_PATH . '/public/assets/css/user.css') ?: time() ?>" rel="stylesheet">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:#050505;color:#fff;font-family:Arial,sans-serif;">
    <main style="text-align:center;padding:32px;">
        <div style="font-size:64px;font-weight:800;color:#e50914;line-height:1;">404</div>
        <h1 style="margin:12px 0 8px;font-size:28px;">Page not found</h1>
        <p style="margin:0 0 22px;color:#a3a3a3;">The page you requested does not exist.</p>
        <a href="<?= BASE_URL ?>" style="color:#fff;background:#e50914;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700;">Go Home</a>
    </main>
</body>
</html>
