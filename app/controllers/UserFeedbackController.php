<?php

class UserFeedbackController extends BaseController
{
    public function save_review(): void
    {
        header('Content-Type: application/json');
        $userId  = $this->requireActiveUser(true);
        $videoId = (int)($_POST['video_id'] ?? 0);
        $rating  = min(5, max(1, (int)($_POST['rating'] ?? 0)));
        $comment = trim($_POST['comment'] ?? '');

        if ($videoId <= 0 || $rating < 1) { echo json_encode(['ok' => false, 'message' => 'Invalid data.']); exit; }

        try {
            $result = (new ReviewModel())->saveUserReview($userId, $videoId, $rating, $comment);
            $this->notifyReviewSubmitted($userId, $videoId, $rating);
            WsPublisher::push('reviews');
            $message = $result === 'updated'
                ? 'Review updated. It will be visible after approval.'
                : 'Review submitted! It will be visible after approval.';
            echo json_encode(['ok' => true, 'message' => $message]);
        } catch (Throwable) {
            echo json_encode(['ok' => false, 'message' => 'Could not save review. Please try again.']);
        }
        exit;
    }

    public function save_report(): void
    {
        header('Content-Type: application/json');
        $userId  = $this->requireActiveUser(true);
        $videoId = (int)($_POST['video_id'] ?? 0);
        $reason  = trim($_POST['reason'] ?? '');

        if ($videoId <= 0 || $reason === '') { echo json_encode(['ok' => false, 'message' => 'Please add a report reason.']); exit; }

        try {
            $reportModel = new ReportModel();
            $title = $reportModel->getVideoTitle($videoId) ?? ('Video #' . $videoId);
            $reportModel->createVideoReport($userId, $videoId, $title, $reason);
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

    private function notifyReviewSubmitted(int $userId, int $videoId, int $rating): void
    {
        try {
            $row = (new ReviewModel())->getReviewNotificationContext($userId, $videoId);
            $title = (string)($row['video_title'] ?? ('Video #' . $videoId));
            $user = (string)($row['user_name'] ?? ($_SESSION['user_name'] ?? 'User'));
            (new NotificationModel())->create('New review submitted', $user . ' rated ' . $title . ' ' . $rating . '/5.', BASE_URL . 'admin/reviews');
            (new ActivityLogModel())->log($user, 'New review', 'Reviews', $title, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        } catch (Throwable) {}
    }
}
