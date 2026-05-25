<?php
// ============================================================
//  app/models/CategoryModel.php
// ============================================================

class CategoryModel extends BaseModel
{
    public function getAll(): array
    {
        $this->ensureStatusColumn();
        return $this->query(
            "SELECT c.id, c.name, c.icon, c.status,
                    COALESCE(vc_stats.video_count, 0) AS video_count,
                    COALESCE(vc_stats.total_views, 0) AS total_views,
                    vc_stats.last_upload
             FROM categories c
             LEFT JOIN (
                 SELECT linked.category_id,
                        COUNT(*) AS video_count,
                        SUM(linked.views) AS total_views,
                        MAX(linked.created_at) AS last_upload
                 FROM (
                     SELECT DISTINCT c2.id AS category_id, v.id AS video_id, v.views, v.created_at
                     FROM categories c2
                     JOIN videos v ON v.category_id = c2.id
                     UNION
                     SELECT vc.category_id, v.id AS video_id, v.views, v.created_at
                     FROM video_categories vc
                     JOIN videos v ON v.id = vc.video_id
                 ) linked
                 GROUP BY linked.category_id
             ) vc_stats ON vc_stats.category_id = c.id
             ORDER BY video_count DESC"
        );
    }

    public function getAllWithCounts(): array
    {
        try {
            $this->ensureStatusColumn();
            return $this->query(
                "SELECT c.id, c.name, c.icon, c.status,
                        COUNT(DISTINCT v.id) AS video_count
                 FROM categories c
                 LEFT JOIN video_categories vc ON vc.category_id = c.id
                 LEFT JOIN videos v ON (v.id = vc.video_id OR v.category_id = c.id) AND v.status = 'published'
                 WHERE c.status = 'active'
                 GROUP BY c.id
                 ORDER BY video_count DESC"
            );
        } catch (Throwable) {
            return [];
        }
    }

    public function create(string $name, string $icon): string
    {
        $this->ensureStatusColumn();
        $this->execute(
            "INSERT INTO categories (name, icon) VALUES (:name, :icon)",
            [':name' => $name, ':icon' => $icon]
        );
        return $this->lastInsertId();
    }

    public function update(int $id, string $name, string $icon, string $status): int
    {
        $this->ensureStatusColumn();
        $status = $this->normaliseStatus($status);
        return $this->execute(
            "UPDATE categories SET name=:name, icon=:icon, status=:status WHERE id=:id",
            [':name' => $name, ':icon' => $icon, ':status' => $status, ':id' => $id]
        );
    }

    public function toggleStatus(int $id): ?string
    {
        $this->ensureStatusColumn();
        $category = $this->queryOne("SELECT status FROM categories WHERE id=:id", [':id' => $id]);
        if (!$category) {
            return null;
        }

        $newStatus = ($category['status'] ?? '') === 'active' ? 'suspended' : 'active';
        $this->execute(
            "UPDATE categories SET status=:status WHERE id=:id",
            [':status' => $newStatus, ':id' => $id]
        );

        return $newStatus;
    }

    public function delete(int $id): int
    {
        return $this->execute("DELETE FROM categories WHERE id=:id", [':id' => $id]);
    }

    public function getAllForSelect(): array
    {
        $this->ensureStatusColumn();
        return $this->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name");
    }

    private function ensureStatusColumn(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        try {
            $this->execute("ALTER TABLE categories MODIFY status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'");
        } catch (Throwable) {
        }

        $done = true;
    }

    private function normaliseStatus(string $status): string
    {
        return $status === 'active' ? 'active' : 'suspended';
    }
}
