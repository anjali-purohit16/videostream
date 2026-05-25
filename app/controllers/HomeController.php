<?php

class HomeController extends BaseController
{
    public function index(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
            $this->redirect(BASE_URL . 'login');
        }

        $userId     = (int)$_SESSION['user_id'];
        $videoModel = new VideoModel();
        $userModel  = new UserModel();
        $catModel   = new CategoryModel();
        $sort       = strtolower($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        try { $videoModel->getPublished(1); } catch (Throwable) {}
        $userData = $userModel->getUserHomeData($userId);
        $profile  = $userModel->getById($userId) ?? [];

        // Subscription
        $subscription = null;
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                "SELECT s.status AS sub_status, s.starts_at, s.expires_at,
                        p.name AS plan_name, p.price, p.currency,
                        DATEDIFF(s.expires_at, CURDATE()) AS days_remaining
                 FROM subscriptions s JOIN plans p ON p.id = s.plan_id
                 WHERE s.user_id = :id
                   AND s.status = 'active'
                   AND (s.expires_at IS NULL OR s.expires_at >= CURDATE())
                 ORDER BY s.created_at DESC LIMIT 1"
            );
            $stmt->execute([':id' => $userId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable) {}

        $categories    = $catModel->getAllWithCounts();
        $selectedCatId = (int)($_GET['cat'] ?? 0);
        $categoryVideos = $selectedCatId > 0 ? $videoModel->getByCategoryId($selectedCatId) : [];

        // Wishlist
        $wishlistItems = [];
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                "SELECT w.video_id, w.created_at, v.title, v.description, v.thumbnail, v.file_path, v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
                 FROM wishlists w
                 JOIN videos v ON v.id = w.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE w.user_id = :id AND v.status = 'published' AND c.status = 'active' ORDER BY w.created_at DESC"
            );
            $stmt->execute([':id' => $userId]);
            $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {}

        // History
        $historyItems = [];
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                "SELECT h.video_id, h.progress_percent, h.watched_at, v.title, v.description, v.thumbnail, v.file_path, v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
                 FROM watch_history h
                 JOIN videos v ON v.id = h.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE h.user_id = :id AND v.status = 'published' AND c.status = 'active' ORDER BY h.watched_at DESC"
            );
            $stmt->execute([':id' => $userId]);
            $historyItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {}

        $videos = $videoModel->getPublished(0);
        $trending = $videoModel->getTrending($sort, 15);
        $notifications = [];
        try {
            $notifications = $this->visibleUserNotifications();
        } catch (Throwable) {}

        $this->view('user/home', [
            'title'            => 'Home',
            'featured'         => $videos,
            'trending'         => $trending,
            'trendingSort'     => $sort,
            'continueWatching' => $userData['continue_watching'] ?? [],
            'categories'       => $categories,
            'categoryVideos'   => $categoryVideos,
            'wishlistItems'    => $wishlistItems,
            'historyItems'     => $historyItems,
            'userProfile'      => $profile,
            'subscription'     => $subscription,
            'publishedCount'   => $videoModel->countPublished(),
            'wishlistCount'    => $userData['wishlist_count'] ?? count($wishlistItems),
            'historyCount'     => $userData['history_count'] ?? count($historyItems),
            'notifications'    => $notifications,
            'plans'            => $userModel->getPlans(),
        ], 'main');
    }

    public function wishlist_toggle(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Not logged in']);
            exit;
        }
        $userId  = (int)$_SESSION['user_id'];
        $videoId = (int)($_GET['id'] ?? 0);
        $msg = 'Watchlist updated';
        $count = 0;
        try {
            $pdo = Database::getInstance();
            $chk = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=:u AND video_id=:v");
            $chk->execute([':u' => $userId, ':v' => $videoId]);
            if ($chk->fetch()) {
                $pdo->prepare("DELETE FROM wishlists WHERE user_id=:u AND video_id=:v")->execute([':u' => $userId, ':v' => $videoId]);
                $msg = 'Removed from watchlist';
            } else {
                $pdo->prepare("INSERT IGNORE INTO wishlists (user_id, video_id) VALUES (:u,:v)")->execute([':u' => $userId, ':v' => $videoId]);
                $msg = 'Added to watchlist ✓';
            }
            $cnt = $pdo->prepare("SELECT COUNT(*) AS total FROM wishlists WHERE user_id=:u");
            $cnt->execute([':u' => $userId]);
            $count = (int)($cnt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (Throwable) {}
        WsPublisher::push('wishlist', ['audience' => 'user', 'user_id' => $userId]);
        header('Content-Type: application/json');
        echo json_encode(['message' => $msg, 'count' => $count]);
        exit;
    }

    public function remove_wishlist(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $userId  = (int)$_SESSION['user_id'];
            $videoId = (int)($_POST['video_id'] ?? 0);
            try {
                $pdo = Database::getInstance();
                $pdo->prepare("DELETE FROM wishlists WHERE user_id=:u AND video_id=:v")->execute([':u' => $userId, ':v' => $videoId]);
            } catch (Throwable) {}
            WsPublisher::push('wishlist', ['audience' => 'user', 'user_id' => $userId]);
        }
        $this->redirect(BASE_URL . '?upage=watchlist');
    }

    /* ── PROFILE UPDATE ── */
    public function update_profile(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false, 'message' => 'Not logged in']); exit; }

        $userId  = (int)$_SESSION['user_id'];
        $name    = trim($_POST['name'] ?? '');
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password'] ?? '';

        if ($name === '') { echo json_encode(['ok' => false, 'message' => 'Name cannot be empty.']); exit; }

        try {
            $pdo = Database::getInstance();

            // Fetch current hash
            $row = $pdo->prepare("SELECT password FROM users WHERE id=:id");
            $row->execute([':id' => $userId]);
            $user = $row->fetch(PDO::FETCH_ASSOC);

            if ($newPw !== '') {
                if (!password_verify($current, $user['password'] ?? '')) {
                    echo json_encode(['ok' => false, 'message' => 'Current password is incorrect.']); exit;
                }
                if (strlen($newPw) < 6) {
                    echo json_encode(['ok' => false, 'message' => 'New password must be at least 6 characters.']); exit;
                }
                $hash = password_hash($newPw, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET name=:name, password=:pw WHERE id=:id")
                    ->execute([':name' => $name, ':pw' => $hash, ':id' => $userId]);
            } else {
                $pdo->prepare("UPDATE users SET name=:name WHERE id=:id")
                    ->execute([':name' => $name, ':id' => $userId]);
            }

            $_SESSION['user_name'] = $name;
            echo json_encode(['ok' => true, 'message' => 'Profile updated successfully.', 'name' => $name]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'message' => 'Update failed. Please try again.']);
        }
        exit;
    }

    /* ── SAVE WATCH PROGRESS ── */
    public function delete_account(): void
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
            $this->redirect(BASE_URL . 'login');
        }

        $userId = (int)$_SESSION['user_id'];

        try {
            (new UserModel())->delete($userId);
        } catch (Throwable) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unable to delete your account. Please try again.'];
            $this->redirect(BASE_URL . '?upage=profile');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }

        session_destroy();
        $this->redirect(BASE_URL . 'login');
    }

    public function save_progress(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false]); exit; }

        $userId   = (int)$_SESSION['user_id'];
        $videoId  = (int)($_POST['video_id'] ?? 0);
        $progress = min(100, max(0, (int)($_POST['progress'] ?? 0)));

        if ($videoId <= 0) { echo json_encode(['ok' => false]); exit; }

        try {
            $pdo = Database::getInstance();
            $pdo->prepare(
                "INSERT INTO watch_history (user_id, video_id, progress_percent, watched_at)
                 VALUES (:u, :v, :p, NOW())
                 ON DUPLICATE KEY UPDATE progress_percent=:p2, watched_at=NOW()"
            )->execute([':u' => $userId, ':v' => $videoId, ':p' => $progress, ':p2' => $progress]);
            $cnt = $pdo->prepare("SELECT COUNT(*) AS total FROM watch_history WHERE user_id=:u");
            $cnt->execute([':u' => $userId]);
            $total = (int)($cnt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            WsPublisher::push('history', ['audience' => 'user', 'user_id' => $userId]);
            echo json_encode(['ok' => true, 'count' => $total]);
        } catch (Throwable) {
            echo json_encode(['ok' => false]);
        }
        exit;
    }

    public function record_view(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false]); exit; }

        $videoId = (int)($_POST['video_id'] ?? 0);
        if ($videoId <= 0) { echo json_encode(['ok' => false]); exit; }

        try {
            (new VideoModel())->recordView($videoId);
            echo json_encode(['ok' => true]);
        } catch (Throwable) {
            echo json_encode(['ok' => false]);
        }
        exit;
    }

    public function subscription_request(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['ok' => false, 'message' => 'Please log in first.']);
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $planId = (int)($_POST['plan_id'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Manual payment');
        $note = trim($_POST['payment_note'] ?? '');

        try {
            $userModel = new UserModel();
            $user = $userModel->getById($userId);
            $plan = null;
            foreach ($userModel->getPlans() as $item) {
                if ((int)$item['id'] === $planId) {
                    $plan = $item;
                    break;
                }
            }

            if (!$user || !$plan) {
                echo json_encode(['ok' => false, 'message' => 'Invalid plan request.']);
                exit;
            }

            (new MessageModel())->createPlanRequest(
                $userId,
                $planId,
                $user['name'],
                $user['email'],
                $plan['name'],
                $method,
                $note
            );

            WsPublisher::push('messages');
            echo json_encode(['ok' => true, 'message' => 'Plan request sent to admin for approval.']);
        } catch (Throwable) {
            echo json_encode(['ok' => false, 'message' => 'Could not send plan request.']);
        }
        exit;
    }

    /* ── SUBMIT REVIEW ── */
    public function save_review(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false, 'message' => 'Not logged in']); exit; }

        $userId  = (int)$_SESSION['user_id'];
        $videoId = (int)($_POST['video_id'] ?? 0);
        $rating  = min(5, max(1, (int)($_POST['rating'] ?? 0)));
        $comment = trim($_POST['comment'] ?? '');

        if ($videoId <= 0 || $rating < 1) { echo json_encode(['ok' => false, 'message' => 'Invalid data.']); exit; }

        try {
            $pdo = Database::getInstance();
            // Check for existing review
            $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=:u AND video_id=:v");
            $chk->execute([':u' => $userId, ':v' => $videoId]);
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE reviews SET rating=:r, comment=:c, status='pending', created_at=NOW() WHERE user_id=:u AND video_id=:v")
                    ->execute([':r' => $rating, ':c' => $comment, ':u' => $userId, ':v' => $videoId]);
                $this->notifyReviewSubmitted($userId, $videoId, $rating);
                WsPublisher::push('reviews');
                echo json_encode(['ok' => true, 'message' => 'Review updated. It will be visible after approval.']);
            } else {
                $pdo->prepare("INSERT INTO reviews (user_id, video_id, rating, comment, status, created_at) VALUES (:u,:v,:r,:c,'pending',NOW())")
                    ->execute([':u' => $userId, ':v' => $videoId, ':r' => $rating, ':c' => $comment]);
                $this->notifyReviewSubmitted($userId, $videoId, $rating);
                WsPublisher::push('reviews');
                echo json_encode(['ok' => true, 'message' => 'Review submitted! It will be visible after approval.']);
            }
        } catch (Throwable) {
            echo json_encode(['ok' => false, 'message' => 'Could not save review. Please try again.']);
        }
        exit;
    }

    /* ── CLEAR WATCH HISTORY ── */
    private function notifyReviewSubmitted(int $userId, int $videoId, int $rating): void
    {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare(
                "SELECT u.name AS user_name, v.title AS video_title
                 FROM users u
                 JOIN videos v ON v.id = :video
                 WHERE u.id = :user"
            );
            $stmt->execute([':video' => $videoId, ':user' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $title = (string)($row['video_title'] ?? ('Video #' . $videoId));
            $user = (string)($row['user_name'] ?? ($_SESSION['user_name'] ?? 'User'));
            (new NotificationModel())->create('New review submitted', $user . ' rated ' . $title . ' ' . $rating . '/5.', BASE_URL . 'admin/reviews');
            (new ActivityLogModel())->log($user, 'New review', 'Reviews', $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        } catch (Throwable) {}
    }

    public function save_report(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { echo json_encode(['ok' => false, 'message' => 'Not logged in']); exit; }

        $userId  = (int)$_SESSION['user_id'];
        $videoId = (int)($_POST['video_id'] ?? 0);
        $reason  = trim($_POST['reason'] ?? '');

        if ($videoId <= 0 || $reason === '') { echo json_encode(['ok' => false, 'message' => 'Please add a report reason.']); exit; }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("SELECT title FROM videos WHERE id=:id");
            $stmt->execute([':id' => $videoId]);
            $title = (string)($stmt->fetch(PDO::FETCH_ASSOC)['title'] ?? ('Video #' . $videoId));
            $code = 'RPT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $pdo->prepare(
                "INSERT INTO reports (report_code, type, reporter_id, ref_video_id, content_ref, reason, status, created_at)
                 VALUES (:code, 'Video', :user, :video, :content, :reason, 'pending', NOW())"
            )->execute([
                ':code' => $code,
                ':user' => $userId,
                ':video' => $videoId,
                ':content' => $title,
                ':reason' => mb_substr($reason, 0, 200),
            ]);
            try {
                (new NotificationModel())->create('New video report', $title . ' was reported by a user.', BASE_URL . 'admin/reports');
                (new ActivityLogModel())->log($_SESSION['user_name'] ?? 'User', 'New report', 'Reports', $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            } catch (Throwable) {}
            WsPublisher::push('reports');
            echo json_encode(['ok' => true, 'message' => 'Report sent to admin for review.']);
        } catch (Throwable) {
            echo json_encode(['ok' => false, 'message' => 'Could not submit report. Please try again.']);
        }
        exit;
    }

    public function clear_history(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            try {
                $pdo = Database::getInstance();
                $pdo->prepare("DELETE FROM watch_history WHERE user_id=:u")->execute([':u' => $userId]);
            } catch (Throwable) {}
            WsPublisher::push('history', ['audience' => 'user', 'user_id' => $userId]);
        }
        $this->redirect(BASE_URL . '?upage=history');
    }

    public function clear_notifications(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $_SESSION['user_notifications_cleared_at'] = time();
            WsPublisher::push('notifications', ['audience' => 'user', 'user_id' => (int)$_SESSION['user_id']]);
        }
        $this->redirect(BASE_URL . '?upage=home');
    }

    private function visibleUserNotifications(): array
    {
        $items = (new NotificationModel())->userHighlights();
        $clearedAt = (int)($_SESSION['user_notifications_cleared_at'] ?? 0);
        if ($clearedAt <= 0) {
            return $items;
        }
        return array_values(array_filter($items, static function (array $item) use ($clearedAt): bool {
            $createdAt = strtotime((string)($item['created_at'] ?? '')) ?: 0;
            return $createdAt > $clearedAt;
        }));
    }

    public function feed_json(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Not logged in']);
            exit;
        }

        require_once ROOT_PATH . '/app/views/admin/_helpers.php';

        $userId = (int)$_SESSION['user_id'];

        try {
            $videoModel = new VideoModel();
            $userModel  = new UserModel();
            $pdo        = Database::getInstance();

            $userData = $userModel->getUserHomeData($userId);

            $subStmt = $pdo->prepare(
                "SELECT s.status AS sub_status, s.starts_at, s.expires_at,
                        p.name AS plan_name, p.price, p.currency,
                        DATEDIFF(s.expires_at, CURDATE()) AS days_remaining
                 FROM subscriptions s JOIN plans p ON p.id = s.plan_id
                 WHERE s.user_id = :id
                   AND s.status = 'active'
                   AND (s.expires_at IS NULL OR s.expires_at >= CURDATE())
                 ORDER BY s.created_at DESC LIMIT 1"
            );
            $subStmt->execute([':id' => $userId]);
            $subscription = $subStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $wishStmt = $pdo->prepare(
                "SELECT w.video_id, w.created_at, v.title, v.description, v.thumbnail, v.file_path,
                        v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
                 FROM wishlists w
                 JOIN videos v ON v.id = w.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE w.user_id = :id AND v.status = 'published' AND c.status = 'active'
                 ORDER BY w.created_at DESC"
            );
            $wishStmt->execute([':id' => $userId]);
            $wishlistItems = $wishStmt->fetchAll(PDO::FETCH_ASSOC);

            $histStmt = $pdo->prepare(
                "SELECT h.video_id, h.progress_percent, h.watched_at, v.title, v.description, v.thumbnail, v.file_path,
                        v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
                 FROM watch_history h
                 JOIN videos v ON v.id = h.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE h.user_id = :id AND v.status = 'published' AND c.status = 'active'
                 ORDER BY h.watched_at DESC"
            );
            $histStmt->execute([':id' => $userId]);
            $historyItems = $histStmt->fetchAll(PDO::FETCH_ASSOC);

            $featured = $videoModel->getPublished(0);
            $trending = $videoModel->getTrending('desc', 15);

            $notifications = [];
            try {
                $notifications = $this->visibleUserNotifications();
            } catch (Throwable) {}

            $mapVideo = function (array $row) {
                return [
                    'id'           => (int)($row['id'] ?? $row['video_id'] ?? 0),
                    'title'        => (string)($row['title'] ?? ''),
                    'description'  => (string)($row['description'] ?? ''),
                    'category'     => (string)($row['category'] ?? ''),
                    'access_level' => strtolower((string)($row['access_level'] ?? 'free')),
                    'duration_sec' => (int)($row['duration_sec'] ?? 0),
                    'views'        => (int)($row['views'] ?? 0),
                    'thumbnail'    => app_media_url($row['thumbnail'] ?? ''),
                    'file_path'    => app_media_url($row['file_path'] ?? ''),
                    'created_at'   => (string)($row['created_at'] ?? ''),
                    'progress'     => isset($row['progress_percent']) ? (int)$row['progress_percent'] : null,
                ];
            };

            echo json_encode([
                'ok'              => true,
                'generated_at'    => date(DATE_ATOM),
                'publishedCount'  => (int)$videoModel->countPublished(),
                'wishlistCount'   => (int)($userData['wishlist_count'] ?? count($wishlistItems)),
                'historyCount'    => (int)($userData['history_count'] ?? count($historyItems)),
                'subscription'    => $subscription,
                'featured'        => array_map($mapVideo, $featured),
                'trending'        => array_map($mapVideo, $trending),
                'wishlistItems'   => array_map($mapVideo, $wishlistItems),
                'historyItems'    => array_map($mapVideo, $historyItems),
                'notifications'   => $notifications,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Feed unavailable: ' . $e->getMessage()]);
        }
        exit;
    }
}
