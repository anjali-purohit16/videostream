<?php
// ============================================================
//  app/models/ReviewModel.php
// ============================================================

class ReviewModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->query(
            "SELECT r.id, u.name AS user, v.title AS video,
                    r.rating, r.comment, r.status, r.created_at
             FROM reviews r
             JOIN users  u ON u.id = r.user_id
             JOIN videos v ON v.id = r.video_id
             ORDER BY r.created_at DESC"
        );
    }

    public function updateStatus(int $id, string $status): int
    {
        return $this->execute(
            "UPDATE reviews SET status=:status WHERE id=:id",
            [':status' => $status, ':id' => $id]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne("SELECT id, status FROM reviews WHERE id=:id", [':id' => $id]);
    }

    public function delete(int $id): int
    {
        return $this->execute("DELETE FROM reviews WHERE id=:id", [':id' => $id]);
    }

    public function saveUserReview(int $userId, int $videoId, int $rating, string $comment): string
    {
        $existing = $this->queryOne(
            "SELECT id FROM reviews WHERE user_id=:u AND video_id=:v",
            [':u' => $userId, ':v' => $videoId]
        );

        if ($existing) {
            $this->execute(
                "UPDATE reviews SET rating=:r, comment=:c, status='pending', created_at=NOW() WHERE user_id=:u AND video_id=:v",
                [':r' => $rating, ':c' => $comment, ':u' => $userId, ':v' => $videoId]
            );
            return 'updated';
        }

        $this->execute(
            "INSERT INTO reviews (user_id, video_id, rating, comment, status, created_at) VALUES (:u,:v,:r,:c,'pending',NOW())",
            [':u' => $userId, ':v' => $videoId, ':r' => $rating, ':c' => $comment]
        );

        return 'created';
    }

    public function getReviewNotificationContext(int $userId, int $videoId): array
    {
        return $this->queryOne(
            "SELECT u.name AS user_name, v.title AS video_title
             FROM users u
             JOIN videos v ON v.id = :video
             WHERE u.id = :user",
            [':video' => $videoId, ':user' => $userId]
        ) ?: [];
    }

    public function getAvgRating(): float
    {
        $row = $this->queryOne("SELECT ROUND(AVG(rating),1) AS avg_r FROM reviews WHERE status='approved'");
        return (float)($row['avg_r'] ?? 0);
    }
}
