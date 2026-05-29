<?php

class HomeController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | User Home Page
    |--------------------------------------------------------------------------
    | Renders the user panel shell. Specific user actions are handled by
    | smaller controllers; this controller owns home/feed data only.
    */
    public function index(): void
    {
        $userId = $this->requireActiveUser();

        $this->view('user/home', $this->buildHomeViewData($userId), 'main');
    }

    /*
    |--------------------------------------------------------------------------
    | View Data
    |--------------------------------------------------------------------------
    | Build the PHP view payload for app/views/user/home.php.
    */
    private function buildHomeViewData(int $userId): array
    {
        $videoModel = new VideoModel();
        $userModel  = new UserModel();
        $catModel   = new CategoryModel();
        $sort       = strtolower($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        try { $videoModel->getPublished(1); } catch (Throwable) {}
        $dashboard = $this->userDashboardData($userId, $userModel);
        $userData = $dashboard['userData'];
        $profile  = $userModel->getById($userId) ?? [];

        $subscription = $dashboard['subscription'];

        $categories    = $catModel->getAllWithCounts();
        $selectedCatId = (int)($_GET['cat'] ?? 0);
        $categoryVideos = $selectedCatId > 0 ? $videoModel->getByCategoryId($selectedCatId) : [];

        $wishlistItems = $dashboard['wishlistItems'];

        $historyItems = $dashboard['historyItems'];

        $videos = $videoModel->getPublished(0);
        $trending = $videoModel->getTrending($sort, 15);
        $notifications = $dashboard['notifications'];

        return [
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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Dashboard Data
    |--------------------------------------------------------------------------
    | Data used by both the initial PHP view and the live feed JSON endpoint.
    */
    private function userDashboardData(int $userId, UserModel $userModel): array
    {
        $notifications = [];
        try {
            $notifications = $this->visibleUserNotifications();
        } catch (Throwable) {}

        return [
            'userData' => $userModel->getUserHomeData($userId),
            'subscription' => $userModel->getActiveSubscription($userId),
            'wishlistItems' => $userModel->getWishlistItems($userId),
            'historyItems' => $userModel->getHistoryItems($userId),
            'notifications' => $notifications,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | Hide notifications that were cleared in the current user session.
    */
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

    /*
    |--------------------------------------------------------------------------
    | Feed Formatting
    |--------------------------------------------------------------------------
    | Convert database video rows into the shape expected by user.js.
    */
    private function mapVideoFeedItem(array $row): array
    {
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
    }

    /*
    |--------------------------------------------------------------------------
    | Live Feed Endpoint
    |--------------------------------------------------------------------------
    | Returns fresh home-panel data for websocket-triggered UI refreshes.
    */
    public function feed_json(): void
    {
        header('Content-Type: application/json');
        $userId = $this->requireActiveUser(true);

        require_once ROOT_PATH . '/app/views/admin/_helpers.php';

        try {
            $videoModel = new VideoModel();
            $userModel  = new UserModel();

            $dashboard = $this->userDashboardData($userId, $userModel);
            $userData = $dashboard['userData'];

            $subscription = $dashboard['subscription'];

            $wishlistItems = $dashboard['wishlistItems'];

            $historyItems = $dashboard['historyItems'];

            $featured = $videoModel->getPublished(0);
            $trending = $videoModel->getTrending('desc', 15);

            $notifications = $dashboard['notifications'];

            echo json_encode([
                'ok'              => true,
                'generated_at'    => date(DATE_ATOM),
                'publishedCount'  => (int)$videoModel->countPublished(),
                'wishlistCount'   => (int)($userData['wishlist_count'] ?? count($wishlistItems)),
                'historyCount'    => (int)($userData['history_count'] ?? count($historyItems)),
                'subscription'    => $subscription,
                'featured'        => array_map([$this, 'mapVideoFeedItem'], $featured),
                'trending'        => array_map([$this, 'mapVideoFeedItem'], $trending),
                'wishlistItems'   => array_map([$this, 'mapVideoFeedItem'], $wishlistItems),
                'historyItems'    => array_map([$this, 'mapVideoFeedItem'], $historyItems),
                'notifications'   => $notifications,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Feed unavailable: ' . $e->getMessage()]);
        }
        exit;
    }
}
