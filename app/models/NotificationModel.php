<?php

class NotificationModel extends BaseModel
{
    public function recent(string $role = 'admin'): array
    {
        return $this->query(
            "SELECT * FROM notifications
             WHERE target_role = :role
             ORDER BY created_at DESC
             LIMIT 8",
            [':role' => $role]
        );
    }

    public function unreadCount(string $role = 'admin'): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM notifications WHERE target_role = :role AND is_read = 0",
            [':role' => $role]
        );
        return (int)($row['total'] ?? 0);
    }

    public function markAllRead(string $role = 'admin'): void
    {
        $this->execute('UPDATE notifications SET is_read = 1 WHERE target_role = :role', [':role' => $role]);
    }

    public function clearAll(string $role = 'admin'): void
    {
        $this->execute('DELETE FROM notifications WHERE target_role = :role', [':role' => $role]);
    }

    public function create(string $title, string $body, string $link = '', string $role = 'admin'): void
    {
        $this->execute(
            "INSERT INTO notifications (target_role, title, body, link_url)
             VALUES (:role, :title, :body, :link)",
            [':role' => $role, ':title' => $title, ':body' => $body, ':link' => $link]
        );
    }

    public function userHighlights(): array
    {
        $items = [];

        $newVideo = $this->queryOne(
            "SELECT title, created_at
             FROM videos
             WHERE status = 'published'
             ORDER BY created_at DESC
             LIMIT 1"
        );
        if ($newVideo) {
            $items[] = [
                'title' => 'New movie added',
                'body' => $newVideo['title'],
                'link_url' => BASE_URL . '?module=user&page=home&upage=movies',
                'created_at' => $newVideo['created_at'],
                'icon' => 'bi-plus-circle',
            ];
        }

        $mostViewed = $this->queryOne(
            "SELECT title, views, created_at
             FROM videos
             WHERE status = 'published'
             ORDER BY views DESC, created_at DESC
             LIMIT 1"
        );
        if ($mostViewed) {
            $items[] = [
                'title' => 'Most viewed movie',
                'body' => $mostViewed['title'] . ' has ' . number_format((int)$mostViewed['views']) . ' views',
                'link_url' => BASE_URL . '?module=user&page=home&upage=trending',
                'created_at' => $mostViewed['created_at'],
                'icon' => 'bi-eye',
            ];
        }

        $mostLiked = $this->queryOne(
            "SELECT v.title, COUNT(r.id) AS likes, MAX(r.created_at) AS created_at
             FROM videos v
             JOIN reviews r ON r.video_id = v.id
             WHERE v.status = 'published' AND r.status = 'approved' AND r.rating >= 4
             GROUP BY v.id, v.title
             ORDER BY likes DESC, MAX(r.rating) DESC, MAX(r.created_at) DESC
             LIMIT 1"
        );
        if ($mostLiked) {
            $items[] = [
                'title' => 'Most liked movie',
                'body' => $mostLiked['title'] . ' leads with ' . number_format((int)$mostLiked['likes']) . ' likes',
                'link_url' => BASE_URL . '?module=user&page=home&upage=movies',
                'created_at' => $mostLiked['created_at'],
                'icon' => 'bi-hand-thumbs-up',
            ];
        }

        return $items;
    }
}
