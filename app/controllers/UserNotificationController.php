<?php

class UserNotificationController extends BaseController
{
    public function clear_notifications(): void
    {
        $userId = $this->requireActiveUser();
        $_SESSION['user_notifications_cleared_at'] = time();
        WsPublisher::push('notifications', ['audience' => 'user', 'user_id' => $userId]);
        $this->redirect(BASE_URL);
    }
}
