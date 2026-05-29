<?php
// ============================================================
//  app/models/VideoModel.php
// ============================================================

class VideoModel extends BaseModel
{
    private ?bool $hasDescriptionColumn = null;
    private ?bool $hasAccessLevelColumn = null;

    public function getAll(string $search = '', string $status = '', string $category = ''): array
    {
        $this->ensureAccessColumns();
        $descriptionSelect = $this->descriptionSelect();
        $categorySelect = $this->categorySelect();
        $accessSelect = $this->accessSelect();
        $sql    = "SELECT v.id, v.title, {$descriptionSelect}, {$categorySelect}, {$this->categoryIdsSelect()}, {$accessSelect}, v.category_id, v.duration_sec, v.thumbnail, v.file_path, v.views, v.status, v.created_at
                   FROM videos v JOIN categories c ON c.id = v.category_id WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND v.title LIKE :search";
            $params[':search'] = "%$search%";
        }
        if ($status) {
            $sql .= " AND v.status = :status";
            $params[':status'] = $status;
        }
        if ($category) {
            $sql .= " AND (c.name = :cat OR EXISTS (
                        SELECT 1
                        FROM video_categories vc
                        JOIN categories cx ON cx.id = vc.category_id
                        WHERE vc.video_id = v.id AND cx.name = :cat
                    ))";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY v.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $this->ensureAccessColumns();
        return $this->queryOne(
            "SELECT v.*, c.name AS primary_category, {$this->categorySelect()}, {$this->categoryIdsSelect()}, {$this->accessSelect()} FROM videos v
             JOIN categories c ON c.id = v.category_id WHERE v.id = :id",
            [':id' => $id]
        );
    }

    public function create(array $data): string
    {
        $this->ensureAccessColumns();
        $categoryIds = $this->normaliseCategoryIds($data['category_ids'] ?? [$data['category_id'] ?? 0]);
        $data['category_id'] = $categoryIds[0] ?? (int)($data['category_id'] ?? 0);
        $accessLevel = $this->normaliseAccessLevel($data['access_level'] ?? 'free');

        if (!$this->hasDescriptionColumn()) {
            $this->execute(
                "INSERT INTO videos (title, category_id, access_level, duration_sec, file_path, thumbnail, status, uploaded_by)
                 VALUES (:title, :cat, :access, :dur, :file, :thumb, :status, :admin)",
                [
                    ':title'  => $data['title'],
                    ':cat'    => $data['category_id'],
                    ':access' => $accessLevel,
                    ':dur'    => $data['duration_sec'] ?? 0,
                    ':file'   => $data['file_path'] ?? null,
                    ':thumb'  => $data['thumbnail'] ?? null,
                    ':status' => $data['status'] ?? 'draft',
                    ':admin'  => $data['uploaded_by'] ?? null,
                ]
            );
            $videoId = $this->lastInsertId();
            $this->syncCategories((int)$videoId, $categoryIds);
            return $videoId;
        }

        $this->execute(
            "INSERT INTO videos (title, description, category_id, access_level, duration_sec, file_path, thumbnail, status, uploaded_by)
             VALUES (:title, :description, :cat, :access, :dur, :file, :thumb, :status, :admin)",
            [
                ':title'  => $data['title'],
                ':description' => $data['description'] ?? null,
                ':cat'    => $data['category_id'],
                ':access' => $accessLevel,
                ':dur'    => $data['duration_sec'] ?? 0,
                ':file'   => $data['file_path'] ?? null,
                ':thumb'  => $data['thumbnail'] ?? null,
                ':status' => $data['status'] ?? 'draft',
                ':admin'  => $data['uploaded_by'] ?? null,
            ]
        );
        $videoId = $this->lastInsertId();
        $this->syncCategories((int)$videoId, $categoryIds);
        return $videoId;
    }

    public function update(int $id, array $data): int
    {
        $this->ensureAccessColumns();
        $categoryIds = $this->normaliseCategoryIds($data['category_ids'] ?? [$data['category_id'] ?? 0]);
        $data['category_id'] = $categoryIds[0] ?? (int)($data['category_id'] ?? 0);
        $accessLevel = $this->normaliseAccessLevel($data['access_level'] ?? 'free');

        if (!$this->hasDescriptionColumn()) {
            $affected = $this->execute(
                "UPDATE videos
                 SET title=:title, category_id=:cat, access_level=:access, duration_sec=:dur, thumbnail=:thumb, file_path=:file, status=:status
                 WHERE id=:id",
                [':title' => $data['title'], ':cat' => $data['category_id'],
                 ':access' => $accessLevel,
                 ':dur' => $data['duration_sec'] ?? 0, ':thumb' => $data['thumbnail'] ?? null,
                 ':file' => $data['file_path'] ?? null, ':status' => $data['status'], ':id' => $id]
            );
            $this->syncCategories($id, $categoryIds);
            return $affected;
        }

        $affected = $this->execute(
            "UPDATE videos
             SET title=:title, description=:description, category_id=:cat, access_level=:access, duration_sec=:dur, thumbnail=:thumb, file_path=:file, status=:status
             WHERE id=:id",
            [':title' => $data['title'], ':description' => $data['description'] ?? null, ':cat' => $data['category_id'],
             ':access' => $accessLevel,
             ':dur' => $data['duration_sec'] ?? 0, ':thumb' => $data['thumbnail'] ?? null,
             ':file' => $data['file_path'] ?? null, ':status' => $data['status'], ':id' => $id]
        );
        $this->syncCategories($id, $categoryIds);
        return $affected;
    }

    public function getByCategoryId(int $categoryId): array
    {
        $this->ensureAccessColumns();
        $descriptionSelect = $this->descriptionSelect();
        $categorySelect = $this->categorySelect();
        $accessSelect = $this->accessSelect();
        return $this->query(
            "SELECT v.id, v.title, {$descriptionSelect}, {$categorySelect}, {$accessSelect}, v.duration_sec, v.thumbnail, v.file_path, v.views, v.status, v.created_at
             FROM videos v
             JOIN categories c ON c.id = v.category_id
             WHERE c.status = 'active'
               AND (v.category_id = :id OR EXISTS (
                   SELECT 1
                   FROM video_categories vc
                   JOIN categories cx ON cx.id = vc.category_id
                   WHERE vc.video_id = v.id AND vc.category_id = :id2 AND cx.status = 'active'
               ))
               AND v.status = 'published'
             ORDER BY v.created_at DESC",
            [':id' => $categoryId, ':id2' => $categoryId]
        );
    }

    public function getPublished(int $limit = 12): array
    {
        $this->ensureAccessColumns();
        $descriptionSelect = $this->descriptionSelect();
        $categorySelect = $this->categorySelect();
        $accessSelect = $this->accessSelect();
        $limitSql = $limit > 0 ? ' LIMIT ' . max(1, min(500, $limit)) : '';
        return $this->query(
            "SELECT v.id, v.title, {$descriptionSelect}, {$categorySelect}, {$accessSelect}, v.duration_sec, v.thumbnail, v.file_path, v.views, v.created_at
             FROM videos v
             JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published'
               AND c.status = 'active'
             ORDER BY v.created_at DESC{$limitSql}"
        );
    }

    public function getTrending(string $direction = 'desc', int $limit = 15): array
    {
        $this->ensureAccessColumns();
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $limit = max(1, min(15, $limit));
        $descriptionSelect = $this->descriptionSelect();
        $categorySelect = $this->categorySelect();
        $accessSelect = $this->accessSelect();
        return $this->query(
            "SELECT ranked.*
             FROM (
                 SELECT v.id, v.title, {$descriptionSelect}, {$categorySelect}, {$accessSelect}, v.duration_sec, v.thumbnail, v.file_path, v.views, v.created_at
                 FROM videos v
                 JOIN categories c ON c.id = v.category_id
                 WHERE v.status = 'published'
                   AND c.status = 'active'
                 ORDER BY v.views DESC, v.created_at DESC
                 LIMIT {$limit}
             ) ranked
             ORDER BY ranked.views {$direction}, ranked.created_at DESC"
        );
    }

    public function recordView(int $id): int
    {
        return $this->execute(
            "UPDATE videos SET views = views + 1 WHERE id = :id AND status = 'published'",
            [':id' => $id]
        );
    }

    public function countPublished(): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total
             FROM videos v
             JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published' AND c.status = 'active'"
        );
        return (int)($row['total'] ?? 0);
    }

    public function delete(int $id): int
    {
        return $this->execute("DELETE FROM videos WHERE id=:id", [':id' => $id]);
    }

    /** Format duration from seconds → "1h 45m" */
    public static function formatDuration(int $secs): string
    {
        $h = intdiv($secs, 3600);
        $m = intdiv($secs % 3600, 60);
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    private function descriptionSelect(): string
    {
        return $this->hasDescriptionColumn() ? 'v.description' : "'' AS description";
    }

    private function hasDescriptionColumn(): bool
    {
        if ($this->hasDescriptionColumn !== null) {
            return $this->hasDescriptionColumn;
        }

        try {
            $row = $this->queryOne("SHOW COLUMNS FROM videos LIKE 'description'");
            $this->hasDescriptionColumn = (bool)$row;
        } catch (Throwable) {
            $this->hasDescriptionColumn = false;
        }

        return $this->hasDescriptionColumn;
    }

    private function categorySelect(): string
    {
        return "COALESCE((SELECT GROUP_CONCAT(cx.name ORDER BY (cx.id = v.category_id) DESC, cx.name SEPARATOR ', ')
                 FROM categories cx
                 JOIN video_categories vc ON vc.category_id = cx.id
                 WHERE vc.video_id = v.id), c.name) AS category";
    }

    private function categoryIdsSelect(): string
    {
        return "COALESCE((SELECT GROUP_CONCAT(vc.category_id ORDER BY (vc.category_id = v.category_id) DESC, vc.category_id SEPARATOR ',')
                 FROM video_categories vc
                 WHERE vc.video_id = v.id), CAST(v.category_id AS CHAR)) AS category_ids";
    }

    private function accessSelect(): string
    {
        return $this->hasAccessLevelColumn() ? 'v.access_level' : "'free' AS access_level";
    }

    private function hasAccessLevelColumn(): bool
    {
        if ($this->hasAccessLevelColumn !== null) {
            return $this->hasAccessLevelColumn;
        }

        try {
            $this->hasAccessLevelColumn = (bool)$this->queryOne("SHOW COLUMNS FROM videos LIKE 'access_level'");
        } catch (Throwable) {
            $this->hasAccessLevelColumn = false;
        }

        return $this->hasAccessLevelColumn;
    }

    private function ensureAccessColumns(): void
    {
        if (!$this->hasAccessLevelColumn()) {
            try {
                $this->execute("ALTER TABLE videos ADD COLUMN access_level ENUM('free','basic','premium') NOT NULL DEFAULT 'free'");
                $this->hasAccessLevelColumn = true;
            } catch (Throwable) {}
        }
    }

    private function normaliseCategoryIds(array|string|int $ids): array
    {
        $raw = is_array($ids) ? $ids : explode(',', (string)$ids);
        $clean = [];
        foreach ($raw as $id) {
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $clean, true)) {
                $clean[] = $id;
            }
        }
        return $clean;
    }

    private function normaliseAccessLevel(string $level): string
    {
        $level = strtolower(trim($level));
        return in_array($level, ['free', 'basic', 'premium'], true) ? $level : 'free';
    }

    private function syncCategories(int $videoId, array $categoryIds): void
    {
        if ($videoId <= 0) {
            return;
        }

        try {
            $this->execute("DELETE FROM video_categories WHERE video_id = :video_id", [':video_id' => $videoId]);
            foreach ($categoryIds as $categoryId) {
                $this->execute(
                    "INSERT INTO video_categories (video_id, category_id) VALUES (:video_id, :category_id)",
                    [':video_id' => $videoId, ':category_id' => $categoryId]
                );
            }
        } catch (Throwable) {
        }
    }
}
