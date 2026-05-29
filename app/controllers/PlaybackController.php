<?php

class PlaybackController extends BaseController
{
    public function save_progress(): void
    {
        header('Content-Type: application/json');
        $userId   = $this->requireActiveUser(true);
        $videoId  = (int)($_POST['video_id'] ?? 0);
        $progress = min(100, max(0, (int)($_POST['progress'] ?? 0)));

        if ($videoId <= 0) { echo json_encode(['ok' => false]); exit; }

        $total = (new UserModel())->saveWatchProgress($userId, $videoId, $progress);
        if ($total !== null) {
            WsPublisher::push('history', ['audience' => 'user', 'user_id' => $userId]);
            echo json_encode(['ok' => true, 'count' => $total]);
            exit;
        }

        echo json_encode(['ok' => false]);
        exit;
    }

    public function record_view(): void
    {
        header('Content-Type: application/json');
        $this->requireActiveUser(true);

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
}
