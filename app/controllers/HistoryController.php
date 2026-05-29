<?php

class HistoryController extends BaseController
{
    public function clear_history(): void
    {
        $userId = $this->requireActiveUser();
        (new UserModel())->clearWatchHistory($userId);
        WsPublisher::push('history', ['audience' => 'user', 'user_id' => $userId]);
        $this->redirect(BASE_URL . 'history');
    }
}
