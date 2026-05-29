<?php

class MessageModel extends BaseModel
{
    public function recent(): array
    {
        $this->ensureRequestColumns();
        return $this->query(
            "SELECT * FROM admin_messages
             ORDER BY created_at DESC
             LIMIT 8"
        );
    }

    public function getAll(): array
    {
        $this->ensureRequestColumns();
        $rows = $this->query(
            "SELECT m.*,
                    u.name AS user_name,
                    u.email AS user_email,
                    p.name AS plan_name,
                    p.price,
                    p.currency,
                    pay.txn_id AS payment_txn_id,
                    pay.status AS payment_status
             FROM admin_messages m
             LEFT JOIN users u ON u.id = m.user_id
             LEFT JOIN plans p ON p.id = m.plan_id
             LEFT JOIN payments pay ON pay.id = m.payment_id
             ORDER BY m.is_read ASC, m.created_at DESC"
        );
        return array_map(fn(array $row): array => $this->hydratePlanRequest($row), $rows);
    }

    public function find(int $id): ?array
    {
        $this->ensureRequestColumns();
        $row = $this->queryOne(
            "SELECT m.*,
                    u.name AS user_name,
                    u.email AS user_email,
                    p.name AS plan_name,
                    p.price,
                    p.currency,
                    p.duration_days,
                    pay.txn_id AS payment_txn_id,
                    pay.status AS payment_status
             FROM admin_messages m
             LEFT JOIN users u ON u.id = m.user_id
             LEFT JOIN plans p ON p.id = m.plan_id
             LEFT JOIN payments pay ON pay.id = m.payment_id
             WHERE m.id = :id",
            [':id' => $id]
        );
        return $row ? $this->hydratePlanRequest($row) : null;
    }

    public function unreadCount(): int
    {
        $row = $this->queryOne('SELECT COUNT(*) AS total FROM admin_messages WHERE is_read = 0');
        return (int)($row['total'] ?? 0);
    }

    public function markAllRead(): void
    {
        $this->execute('UPDATE admin_messages SET is_read = 1');
    }

    public function markRead(int $id): void
    {
        $this->execute('UPDATE admin_messages SET is_read = 1 WHERE id = :id', [':id' => $id]);
    }

    public function clearAll(): void
    {
        $this->execute('DELETE FROM admin_messages');
    }

    public function createPlanRequest(int $userId, int $planId, string $userName, string $userEmail, string $planName, string $paymentMethod, string $note): void
    {
        $this->ensureRequestColumns();
        $paymentMethod = $this->normalisePaymentMethod($paymentMethod);
        $body = trim("User {$userName} ({$userEmail}) requested the {$planName} plan.\nPayment method: {$paymentMethod}\n" . ($note ? "Note: {$note}" : ''));
        $paymentId = null;

        try {
            $this->db->beginTransaction();
            $plan = $this->queryOne("SELECT price FROM plans WHERE id = :id", [':id' => $planId]) ?? [];
            $this->execute(
                "INSERT INTO payments (txn_id, user_id, plan_id, amount, method, status, paid_at, created_at)
                 VALUES (:txn, :user, :plan, :amount, :method, 'pending', NULL, NOW())",
                [
                    ':txn' => $this->transactionId($userId),
                    ':user' => $userId,
                    ':plan' => $planId,
                    ':amount' => (float)($plan['price'] ?? 0),
                    ':method' => $paymentMethod,
                ]
            );
            $paymentId = (int)$this->lastInsertId();

            $this->execute(
                "INSERT INTO admin_messages (sender_name, sender_email, subject, body, is_read, user_id, plan_id, payment_id, request_status, request_type, payment_method, created_at)
                 VALUES (:sender, :email, :subject, :body, 0, :user_id, :plan_id, :payment_id, 'pending', 'plan_request', :method, NOW())",
                [
                    ':sender' => $userName,
                    ':email' => $userEmail,
                    ':subject' => 'Plan request: ' . $planName,
                    ':body' => $body,
                    ':user_id' => $userId,
                    ':plan_id' => $planId,
                    ':payment_id' => $paymentId,
                    ':method' => $paymentMethod,
                ]
            );
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function updateRequestStatus(int $id, string $status): int
    {
        $this->ensureRequestColumns();
        return $this->execute(
            "UPDATE admin_messages SET request_status = :status, is_read = 1 WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }

    private function ensureRequestColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $columns = [
            'user_id' => "ALTER TABLE admin_messages ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER created_at",
            'plan_id' => "ALTER TABLE admin_messages ADD COLUMN plan_id INT UNSIGNED DEFAULT NULL AFTER user_id",
            'request_status' => "ALTER TABLE admin_messages ADD COLUMN request_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none' AFTER plan_id",
            'request_type' => "ALTER TABLE admin_messages ADD COLUMN request_type VARCHAR(40) NOT NULL DEFAULT 'general' AFTER request_status",
            'payment_method' => "ALTER TABLE admin_messages ADD COLUMN payment_method ENUM('UPI','Card','Wallet','NetBanking','Paypal') NOT NULL DEFAULT 'Card' AFTER request_type",
            'payment_id' => "ALTER TABLE admin_messages ADD COLUMN payment_id INT UNSIGNED DEFAULT NULL AFTER plan_id",
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->queryOne("SHOW COLUMNS FROM admin_messages LIKE '{$column}'")) {
                $this->execute($sql);
            }
        }

        $done = true;
    }

    private function normalisePaymentMethod(string $method): string
    {
        $method = trim($method);
        return in_array($method, ['UPI', 'Card', 'Wallet', 'NetBanking', 'Paypal'], true) ? $method : 'Card';
    }

    private function hydratePlanRequest(array $row): array
    {
        $subject = (string)($row['subject'] ?? '');
        if (($row['request_type'] ?? 'general') === 'plan_request' || stripos($subject, 'Plan request:') === 0) {
            $row['request_type'] = 'plan_request';
            if (($row['request_status'] ?? 'none') === 'none' || ($row['request_status'] ?? '') === '') {
                $row['request_status'] = 'pending';
            }

            if (empty($row['plan_name']) && preg_match('/^Plan request:\s*(.+)$/i', $subject, $match)) {
                $row['plan_name'] = trim($match[1]);
            }

            if (empty($row['plan_id']) && !empty($row['plan_name'])) {
                $plan = $this->queryOne(
                    "SELECT id, name, price, currency, duration_days FROM plans WHERE LOWER(name) = LOWER(:name) LIMIT 1",
                    [':name' => $row['plan_name']]
                );
                if ($plan) {
                    $row['plan_id'] = (int)$plan['id'];
                    $row['plan_name'] = $plan['name'];
                    $row['price'] = $plan['price'];
                    $row['currency'] = $plan['currency'] ?? ($row['currency'] ?? 'USD');
                    $row['duration_days'] = $plan['duration_days'] ?? ($row['duration_days'] ?? 30);
                }
            }

            if (empty($row['user_id']) && !empty($row['sender_email'])) {
                $user = $this->queryOne(
                    "SELECT id, name, email FROM users WHERE email = :email LIMIT 1",
                    [':email' => $row['sender_email']]
                );
                if ($user) {
                    $row['user_id'] = (int)$user['id'];
                    $row['user_name'] = $user['name'];
                    $row['user_email'] = $user['email'];
                }
            }
        }

        return $row;
    }

    private function transactionId(int $userId): string
    {
        return substr('TXN-' . date('YmdHis') . '-' . $userId . '-' . strtoupper(bin2hex(random_bytes(2))), 0, 40);
    }
}
