<?php

abstract class AdminController extends BaseController
{
    protected function adminView(string $view, array $data = []): void
    {
        $this->requireAdmin();
        $data['navCounts'] = $data['navCounts'] ?? $this->navCounts();
        $data['flash'] = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $data['notifications'] = $this->notifications();
        $data['notificationCount'] = $this->notificationCount();
        $data['messages'] = $this->messages();
        $data['messageCount'] = $this->messageCount();
        $data['adminName'] = $_SESSION['admin_name'] ?? 'Admin';

        $this->view('admin/' . $view, $data, 'admin');
    }

    protected function readId(): int
    {
        return (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function back(string $fallback): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
    }

    protected function navCounts(): array
    {
        try {
            return (new DashboardModel())->getNavCounts();
        } catch (Throwable) {
            return [];
        }
    }

    protected function requireAdmin(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (empty($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect(BASE_URL . 'admin/login');
        }
    }

    protected function logAdminAction(string $action, string $module, string $details): void
    {
        try {
            (new ActivityLogModel())->log($_SESSION['admin_name'] ?? 'Admin', $action, $module, $details, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        } catch (Throwable) {
        }
    }

    private function notifications(): array
    {
        try {
            return (new NotificationModel())->recent('admin');
        } catch (Throwable) {
            return [];
        }
    }

    private function notificationCount(): int
    {
        try {
            return (new NotificationModel())->unreadCount('admin');
        } catch (Throwable) {
            return 0;
        }
    }

    private function messages(): array
    {
        try {
            return (new MessageModel())->recent();
        } catch (Throwable) {
            return [];
        }
    }

    private function messageCount(): int
    {
        try {
            return (new MessageModel())->unreadCount();
        } catch (Throwable) {
            return 0;
        }
    }
}


