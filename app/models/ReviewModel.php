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

    public function getAvgRating(): float
    {
        $row = $this->queryOne("SELECT ROUND(AVG(rating),1) AS avg_r FROM reviews WHERE status='approved'");
        return (float)($row['avg_r'] ?? 0);
    }
}
