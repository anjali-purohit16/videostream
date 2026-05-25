<?php
// ============================================================
//  app/models/PaymentModel.php
// ============================================================

class PaymentModel extends BaseModel
{
    public function getAll(string $status = '', string $method = ''): array
    {
        $currencyExpr = $this->currencyExpression();
        $sql    = "SELECT p.txn_id, u.name AS user, pl.name AS plan,
                          p.amount, {$currencyExpr} AS currency, p.method, p.status, p.paid_at
                   FROM payments p
                   JOIN users u  ON u.id  = p.user_id
                   JOIN plans pl ON pl.id = p.plan_id
                   WHERE 1=1";
        $params = [];
        if ($status) { $sql .= " AND p.status=:stat";   $params[':stat']   = $status; }
        if ($method) { $sql .= " AND p.method=:method"; $params[':method'] = $method; }
        $sql .= " ORDER BY p.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getRevenueSummary(): array
    {
        return $this->queryOne(
            "SELECT
               COALESCE(SUM(CASE WHEN status='success' THEN amount END), 0) AS total_revenue,
               COALESCE(SUM(CASE WHEN status='success' AND YEAR(paid_at)=YEAR(NOW()) AND MONTH(paid_at)=MONTH(NOW()) THEN amount END), 0) AS monthly_revenue,
               COALESCE(SUM(CASE WHEN status='success' AND YEAR(paid_at)=YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(paid_at)=MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN amount END), 0) AS previous_month_revenue,
               COUNT(CASE WHEN status='success' THEN 1 END) AS success_count,
               COUNT(CASE WHEN status='pending' THEN 1 END) AS pending_count
             FROM payments"
        ) ?? [];
    }

    private function currencyExpression(): string
    {
        if ($this->hasColumn('plans', 'currency')) {
            return 'pl.currency';
        }
        if ($this->hasColumn('payments', 'currency')) {
            return 'p.currency';
        }

        return "'USD'";
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return (bool)$this->queryOne("SHOW COLUMNS FROM {$table} LIKE :column", [':column' => $column]);
        } catch (Throwable) {
            return false;
        }
    }
}
