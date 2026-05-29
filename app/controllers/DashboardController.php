<?php

class DashboardController extends AdminController
{
    public function index(): void
    {
        try {
            $model = new DashboardModel();
            $stats = $model->getStats();
            $revenueChart = $model->getRevenueChart();
            $subscriptions = $model->getSubscriptionBreakdown();
            $recentVideos = $model->getRecentVideos();
            $topContent = $model->getTopContent();
            $activity = $model->getActivityFeed();
            $navCounts = $model->getNavCounts();
            $dbError = null;
        } catch (Throwable $error) {
            $stats = [];
            $revenueChart = [];
            $subscriptions = [];
            $recentVideos = [];
            $topContent = [];
            $activity = [];
            $navCounts = [];
            $dbError = $error->getMessage();
        }

        $this->adminView('dashboard', [
            'title' => 'Dashboard',
            'section' => 'dashboard',
            'stats' => $stats,
            'revenueChart' => $revenueChart,
            'subscriptions' => $subscriptions,
            'recentVideos' => $recentVideos,
            'topContent' => $topContent,
            'activity' => $activity,
            'navCounts' => $navCounts,
            'dbError' => $dbError,
        ]);
    }

    public function feed_json(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Admin only']);
            exit;
        }

        try {
            $dashboard = new DashboardModel();
            $reviews   = new ReviewModel();
            $reports   = new ReportModel();
            $notifications = new NotificationModel();
            $messages = new MessageModel();

            echo json_encode([
                'ok'           => true,
                'generated_at' => date(DATE_ATOM),
                'dashboard' => [
                    'stats'         => $dashboard->getStats(),
                    'revenueChart'  => $dashboard->getRevenueChart(),
                    'subscriptions' => $dashboard->getSubscriptionBreakdown(),
                ],
                'reviews' => [
                    'avgRating' => $reviews->getAvgRating(),
                    'items'     => $reviews->getAll(),
                ],
                'reports' => [
                    'items' => $reports->getAll(),
                ],
                'topbar' => [
                    'notificationCount' => $notifications->unreadCount('admin'),
                    'notifications'     => $notifications->recent('admin'),
                    'messageCount'      => $messages->unreadCount(),
                    'messages'          => $messages->recent(),
                ],
                'navCounts' => $dashboard->getNavCounts(),
                'versions'  => $this->versions(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable $error) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
        }
        exit;
    }

    private function versions(): array
    {
        $pdo = Database::getInstance();
        $payment = $pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(MAX(UNIX_TIMESTAMP(created_at)), 0) AS max_created,
                    COALESCE(MAX(UNIX_TIMESTAMP(paid_at)), 0) AS max_paid,
                    COALESCE(SUM(CASE WHEN status='success' THEN amount ELSE 0 END), 0) AS success_sum,
                    COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END), 0) AS pending_sum,
                    COALESCE(SUM(CASE WHEN status='failed' THEN amount ELSE 0 END), 0) AS failed_sum,
                    COALESCE(SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END), 0) AS refunded_sum,
                    COALESCE(SUM(CASE WHEN status='success' THEN 1 ELSE 0 END), 0) AS success_count,
                    COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                    COALESCE(SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END), 0) AS failed_count,
                    COALESCE(SUM(CASE WHEN status='refunded' THEN 1 ELSE 0 END), 0) AS refunded_count
             FROM payments"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $subscription = $pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(MAX(UNIX_TIMESTAMP(created_at)), 0) AS max_created,
                    COALESCE(SUM(CASE WHEN status='active' THEN 1 ELSE 0 END), 0) AS active_count
             FROM subscriptions"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $hasRequestStatus = (bool)$pdo->query("SHOW COLUMNS FROM admin_messages LIKE 'request_status'")->fetch(PDO::FETCH_ASSOC);
        $messageSql = $hasRequestStatus
            ? "SELECT COUNT(*) AS total,
                      COALESCE(MAX(UNIX_TIMESTAMP(created_at)), 0) AS max_created,
                      COALESCE(SUM(CASE WHEN request_status='pending' THEN 1 ELSE 0 END), 0) AS pending_count
               FROM admin_messages"
            : "SELECT COUNT(*) AS total,
                      COALESCE(MAX(UNIX_TIMESTAMP(created_at)), 0) AS max_created,
                      0 AS pending_count
               FROM admin_messages";
        $message = $pdo->query($messageSql)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'payments'      => implode(':', $payment),
            'subscriptions' => implode(':', $subscription),
            'messages'      => implode(':', $message),
        ];
    }
}
