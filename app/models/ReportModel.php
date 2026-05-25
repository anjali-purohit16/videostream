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
}
