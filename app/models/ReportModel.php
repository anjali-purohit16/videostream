<?php
// ============================================================
//  app/models/ReportModel.php
// ============================================================

class ReportModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->query(
            "SELECT r.id, r.report_code, r.type, u.name AS reporter,
                    r.content_ref, r.reason, r.status, r.created_at
             FROM reports r
             JOIN users u ON u.id = r.reporter_id
             ORDER BY r.created_at DESC"
        );
    }

    public function updateStatus(int $id, string $status): int
    {
        return $this->execute(
            "UPDATE reports SET status=:status WHERE id=:id",
            [':status' => $status, ':id' => $id]
        );
    }

    public function getVideoTitle(int $videoId): ?string
    {
        $row = $this->queryOne("SELECT title FROM videos WHERE id=:id", [':id' => $videoId]);
        return isset($row['title']) ? (string)$row['title'] : null;
    }

    public function createVideoReport(int $userId, int $videoId, string $title, string $reason): void
    {
        $code = 'RPT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $this->execute(
            "INSERT INTO reports (report_code, type, reporter_id, ref_video_id, content_ref, reason, status, created_at)
             VALUES (:code, 'Video', :user, :video, :content, :reason, 'pending', NOW())",
            [
                ':code' => $code,
                ':user' => $userId,
                ':video' => $videoId,
                ':content' => $title,
                ':reason' => mb_substr($reason, 0, 200),
            ]
        );
    }
}
