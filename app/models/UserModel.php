<?php
// ============================================================
//  app/models/UserModel.php
// ============================================================

class UserModel extends BaseModel
{
    public function getAll(string $search = '', string $plan = '', string $status = ''): array
    {
        $sql    = "SELECT u.id, u.name, u.email, p.name AS plan, u.status, u.joined_at, u.last_seen
                   FROM users u JOIN plans p ON p.id = u.plan_id WHERE 1=1";
        $params = [];
        if ($search) { $sql .= " AND (u.name LIKE :s OR u.email LIKE :s OR p.name LIKE :s OR u.status LIKE :s)"; $params[':s'] = "%$search%"; }
        if ($plan)   { $sql .= " AND p.name = :plan";   $params[':plan']   = $plan; }
        if ($status) { $sql .= " AND u.status = :stat"; $params[':stat']   = $status; }
        $sql .= " ORDER BY u.joined_at DESC";
        return $this->query($sql, $params);
    }

    public function getPlans(): array
    {
        return $this->query("SELECT id, name, price, currency, duration_days FROM plans WHERE status='active' ORDER BY FIELD(name, 'Premium', 'Basic', 'Free'), name");
    }

    public function getActivePlanById(int $id): ?array
    {
        return $this->queryOne(
            "SELECT id, name, price, currency, duration_days FROM plans WHERE id=:id AND status='active'",
            [':id' => $id]
        );
    }

    public function getSubscriptionRequestContext(int $userId, int $planId): array
    {
        return [
            'user' => $this->getById($userId),
            'plan' => $this->getActivePlanById($planId),
        ];
    }

    public function getById(int $id): ?array
    {
        // Also fetch active subscription to verify plan is still valid
        return $this->queryOne(
            "SELECT u.*, p.name AS plan,
                     s.status  AS sub_status,
                     s.expires_at AS sub_expires_at
              FROM users u
              JOIN plans p ON p.id = u.plan_id
              LEFT JOIN subscriptions s
                ON s.user_id = u.id
               AND s.status  = 'active'
               AND (s.expires_at IS NULL OR s.expires_at >= CURDATE())
              WHERE u.id = :id
              ORDER BY s.created_at DESC
              LIMIT 1",
            [':id' => $id]
        );
    }

    public function getDetails(int $id): array
    {
        $user = $this->getById($id);
        if (!$user) {
            return [];
        }

        return [
            'profile' => $user,
            'subscriptions' => $this->safeQuery(
                "SELECT s.status, s.starts_at, s.expires_at, s.created_at, p.name AS plan_name, p.price, p.currency
                 FROM subscriptions s
                 JOIN plans p ON p.id = s.plan_id
                 WHERE s.user_id = :id
                 ORDER BY s.created_at DESC",
                [':id' => $id]
            ),
            'payments' => $this->safeQuery(
                "SELECT pay.txn_id, pay.amount, p.currency, pay.method, pay.status, pay.paid_at, pay.created_at
                 FROM payments pay
                 JOIN plans p ON p.id = pay.plan_id
                 WHERE pay.user_id = :id
                 ORDER BY pay.created_at DESC
                 LIMIT 5",
                [':id' => $id]
            ),
            'wishlist' => $this->safeQuery(
                "SELECT w.created_at, v.title, c.name AS category
                 FROM wishlists w
                 JOIN videos v ON v.id = w.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE w.user_id = :id
                 ORDER BY w.created_at DESC
                 LIMIT 8",
                [':id' => $id]
            ),
            'history' => $this->safeQuery(
                "SELECT h.watched_at, h.progress_percent, v.title, c.name AS category
                 FROM watch_history h
                 JOIN videos v ON v.id = h.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE h.user_id = :id
                 ORDER BY h.watched_at DESC
                 LIMIT 8",
                [':id' => $id]
            ),
            'reviews' => $this->safeQuery(
                "SELECT r.rating, r.comment, r.status, r.created_at, v.title AS video
                 FROM reviews r
                 JOIN videos v ON v.id = r.video_id
                 WHERE r.user_id = :id
                 ORDER BY r.created_at DESC
                 LIMIT 5",
                [':id' => $id]
            ),
        ];
    }

    public function getUserHomeData(int $id): array
    {
        return [
            'wishlist_count' => (int)($this->safeQueryOne("SELECT COUNT(*) AS total FROM wishlists WHERE user_id=:id", [':id' => $id])['total'] ?? 0),
            'history_count' => (int)($this->safeQueryOne("SELECT COUNT(*) AS total FROM watch_history WHERE user_id=:id", [':id' => $id])['total'] ?? 0),
            'continue_watching' => $this->safeQuery(
                "SELECT h.video_id, h.progress_percent, h.watched_at, v.title, v.description, v.thumbnail, v.file_path, v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
                 FROM watch_history h
                 JOIN videos v ON v.id = h.video_id
                 JOIN categories c ON c.id = v.category_id
                 WHERE h.user_id=:id AND v.status = 'published' AND c.status = 'active'
                 ORDER BY h.watched_at DESC
                 LIMIT 4",
                [':id' => $id]
            ),
        ];
    }

    public function getActiveSubscription(int $id): ?array
    {
        return $this->safeQueryOne(
            "SELECT s.status AS sub_status, s.starts_at, s.expires_at,
                    p.name AS plan_name, p.price, p.currency,
                    DATEDIFF(s.expires_at, CURDATE()) AS days_remaining
             FROM subscriptions s JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = :id
               AND s.status = 'active'
               AND (s.expires_at IS NULL OR s.expires_at >= CURDATE())
             ORDER BY s.created_at DESC LIMIT 1",
            [':id' => $id]
        );
    }

    public function getWishlistItems(int $id): array
    {
        return $this->safeQuery(
            "SELECT w.video_id, w.created_at, v.title, v.description, v.thumbnail, v.file_path, v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
             FROM wishlists w
             JOIN videos v ON v.id = w.video_id
             JOIN categories c ON c.id = v.category_id
             WHERE w.user_id = :id AND v.status = 'published' AND c.status = 'active' ORDER BY w.created_at DESC",
            [':id' => $id]
        );
    }

    public function getHistoryItems(int $id): array
    {
        return $this->safeQuery(
            "SELECT h.video_id, h.progress_percent, h.watched_at, v.title, v.description, v.thumbnail, v.file_path, v.duration_sec, COALESCE(v.access_level, 'free') AS access_level, c.name AS category
             FROM watch_history h
             JOIN videos v ON v.id = h.video_id
             JOIN categories c ON c.id = v.category_id
             WHERE h.user_id = :id AND v.status = 'published' AND c.status = 'active' ORDER BY h.watched_at DESC",
            [':id' => $id]
        );
    }

    public function toggleWishlist(int $userId, int $videoId): array
    {
        $message = 'Watchlist updated';
        $count = 0;

        try {
            $chk = $this->db->prepare("SELECT id FROM wishlists WHERE user_id=:u AND video_id=:v");
            $chk->execute([':u' => $userId, ':v' => $videoId]);

            if ($chk->fetch()) {
                $this->execute(
                    "DELETE FROM wishlists WHERE user_id=:u AND video_id=:v",
                    [':u' => $userId, ':v' => $videoId]
                );
                $message = 'Removed from watchlist';
            } else {
                $this->execute(
                    "INSERT IGNORE INTO wishlists (user_id, video_id) VALUES (:u,:v)",
                    [':u' => $userId, ':v' => $videoId]
                );
                $message = 'Added to watchlist âœ“';
            }

            $count = (int)($this->safeQueryOne(
                "SELECT COUNT(*) AS total FROM wishlists WHERE user_id=:u",
                [':u' => $userId]
            )['total'] ?? 0);
        } catch (Throwable) {}

        return ['message' => $message, 'count' => $count];
    }

    public function removeWishlistItem(int $userId, int $videoId): void
    {
        try {
            $this->execute(
                "DELETE FROM wishlists WHERE user_id=:u AND video_id=:v",
                [':u' => $userId, ':v' => $videoId]
            );
        } catch (Throwable) {}
    }

    public function saveWatchProgress(int $userId, int $videoId, int $progress): ?int
    {
        try {
            $this->execute(
                "INSERT INTO watch_history (user_id, video_id, progress_percent, watched_at)
                 VALUES (:u, :v, :p, NOW())
                 ON DUPLICATE KEY UPDATE progress_percent=:p2, watched_at=NOW()",
                [':u' => $userId, ':v' => $videoId, ':p' => $progress, ':p2' => $progress]
            );

            return (int)($this->safeQueryOne(
                "SELECT COUNT(*) AS total FROM watch_history WHERE user_id=:u",
                [':u' => $userId]
            )['total'] ?? 0);
        } catch (Throwable) {
            return null;
        }
    }

    public function clearWatchHistory(int $userId): void
    {
        try {
            $this->execute("DELETE FROM watch_history WHERE user_id=:u", [':u' => $userId]);
        } catch (Throwable) {}
    }

    public function getPasswordHash(int $id): ?string
    {
        $user = $this->safeQueryOne("SELECT password FROM users WHERE id=:id", [':id' => $id]);
        return isset($user['password']) ? (string)$user['password'] : null;
    }

    public function updateProfileName(int $id, string $name): void
    {
        $this->execute(
            "UPDATE users SET name=:name WHERE id=:id",
            [':name' => $name, ':id' => $id]
        );
    }

    public function updateProfileNameAndPassword(int $id, string $name, string $passwordHash): void
    {
        $this->execute(
            "UPDATE users SET name=:name, password=:pw WHERE id=:id",
            [':name' => $name, ':pw' => $passwordHash, ':id' => $id]
        );
    }

    public function updateStatus(int $id, string $status): int
    {
        return $this->execute(
            "UPDATE users SET status=:status WHERE id=:id",
            [':status' => $status, ':id' => $id]
        );
    }

    public function createManual(array $data): string
    {
        $this->execute(
            "INSERT INTO users (name, email, password, plan_id, status, joined_at, last_seen)
             VALUES (:name, :email, :password, :plan_id, :status, NOW(), NULL)",
            [
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':plan_id' => $data['plan_id'],
                ':status' => $data['status'],
            ]
        );

        $userId = $this->lastInsertId();
        if (!empty($data['create_subscription'])) {
            $plan = $this->queryOne("SELECT duration_days FROM plans WHERE id=:id", [':id' => $data['plan_id']]);
            $durationDays = max(1, (int)($plan['duration_days'] ?? 30));
            $this->execute(
                "INSERT INTO subscriptions (user_id, plan_id, starts_at, expires_at, status)
                 VALUES (:user_id, :plan_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL {$durationDays} DAY), 'active')",
                [':user_id' => $userId, ':plan_id' => $data['plan_id']]
            );
        }

        return $userId;
    }

    public function delete(int $id): int
    {
        if ($id <= 0) {
            return 0;
        }

        $this->db->beginTransaction();

        try {
            $params = [':id' => $id];

            $this->execute("DELETE FROM wishlists WHERE user_id=:id", $params);
            $this->execute("DELETE FROM watch_history WHERE user_id=:id", $params);
            $this->execute("DELETE FROM reviews WHERE user_id=:id", $params);
            $this->execute("DELETE FROM notifications WHERE target_user_id=:id", $params);
            $this->execute("DELETE FROM admin_messages WHERE user_id=:id", $params);
            $this->execute("DELETE FROM reports WHERE reporter_id=:id", $params);
            $this->execute("UPDATE reports SET ref_user_id=NULL WHERE ref_user_id=:id", $params);
            $this->execute("DELETE FROM subscriptions WHERE user_id=:id", $params);
            $this->execute("DELETE FROM payments WHERE user_id=:id", $params);

            $deleted = $this->execute("DELETE FROM users WHERE id=:id", $params);
            $this->db->commit();

            return $deleted;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private function safeQuery(string $sql, array $params = []): array
    {
        try {
            return $this->query($sql, $params);
        } catch (Throwable) {
            return [];
        }
    }

    private function safeQueryOne(string $sql, array $params = []): ?array
    {
        try {
            return $this->queryOne($sql, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
