<?php

class SubscriptionModel extends BaseModel
{
    public function getDistribution(): array
    {
        return $this->query(
            "SELECT
                DATE_FORMAT(m.month_start, '%b') AS month_label,
                MONTH(m.month_start) AS month_num,
                YEAR(m.month_start) AS yr,
                COALESCE(SUM(CASE WHEN p.name = 'Premium' THEN 1 ELSE 0 END), 0) AS premium_count,
                COALESCE(SUM(CASE WHEN p.name = 'Basic' THEN 1 ELSE 0 END), 0) AS basic_count,
                COALESCE(SUM(CASE WHEN p.name = 'Premium' THEN p.price ELSE 0 END), 0) AS premium_cost,
                COALESCE(SUM(CASE WHEN p.name = 'Basic'   THEN p.price ELSE 0 END), 0) AS basic_cost
             FROM (
                 SELECT DATE_FORMAT(DATE_SUB(LAST_DAY(NOW()), INTERVAL seq.n MONTH), '%Y-%m-01') AS month_start
                 FROM (
                     SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION
                     SELECT 3       UNION SELECT 4 UNION SELECT 5
                 ) seq
             ) m
             LEFT JOIN subscriptions s
                ON YEAR(s.starts_at) = YEAR(m.month_start)
               AND MONTH(s.starts_at) = MONTH(m.month_start)
             LEFT JOIN plans p
                ON p.id = s.plan_id
               AND p.name IN ('Premium', 'Basic')
             GROUP BY m.month_start
             ORDER BY m.month_start"
        );
    }

    public function getRenewals(): array
    {
        return $this->query(
            "SELECT u.name, p.name AS plan, p.price, p.currency, s.expires_at, s.status,
                    DATEDIFF(s.expires_at, CURDATE()) AS days_left
             FROM subscriptions s
             JOIN users u ON u.id = s.user_id
             JOIN plans p ON p.id = s.plan_id
             WHERE s.status IN ('active','expired')
             ORDER BY ABS(DATEDIFF(s.expires_at, CURDATE())) ASC
             LIMIT 8"
        );
    }
}
