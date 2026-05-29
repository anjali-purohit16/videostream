<?php

class NotificationController extends AdminController
{
    public function read(): void
    {
        $this->requireAdmin();
        (new NotificationModel())->markAllRead('admin');
        $this->logAdminAction('Notifications Read', 'Notifications', 'Marked all notifications as read');
        WsPublisher::push('notifications');
        $this->back(BASE_URL . '?module=admin&page=dashboard');
    }

    public function clear(): void
    {
        $this->requireAdmin();
        (new NotificationModel())->clearAll('admin');
        $this->logAdminAction('Notifications Cleared', 'Notifications', 'Cleared all notifications');
        WsPublisher::push('notifications');
        $this->back(BASE_URL . '?module=admin&page=dashboard');
    }
}
