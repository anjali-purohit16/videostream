<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($title ?? 'Watch') . ' | ' . APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/user.css" rel="stylesheet">
</head>
<body>
    <?= $content ?>
    <script>
      window.VS_USER = {
        id: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
        baseUrl: '<?= BASE_URL ?>',
        planLevel: '<?= htmlspecialchars($userPlanLevel ?? 'free', ENT_QUOTES) ?>',
        upage: '<?= htmlspecialchars($_GET['upage'] ?? 'home', ENT_QUOTES) ?>'
      };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/user.js"></script>
</body>
</html>
