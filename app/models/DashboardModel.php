<?php

class DashboardModel extends BaseModel
{
    public function getStats(): array
    {
        return $this->queryOne('SELECT * FROM v_dashboard_stats') ?? [];
    }

    public function getRevenueChart(): array
    {
        // Always show last 7 months including current month.
        // Months with no payments return revenue = 0 (no fallback template).
        return $this->query(
            "SELECT
                DATE_FORMAT(m.month_start, '%b')  AS month_label, -- %b = Jan, Feb, etc.
                MONTH(m.month_start)               AS month_num, -- 1-based month number for sorting
                YEAR(m.month_start)                AS yr, -- Year for sorting (handles year wrap-around)
                COALESCE(SUM(CASE WHEN p.status = 'success' THEN p.amount END), 0) AS revenue
             FROM (
                 SELECT DATE_FORMAT(DATE_SUB(LAST_DAY(NOW()), INTERVAL seq.n MONTH), '%Y-%m-01') AS month_start
                 FROM (
                     SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION
                     SELECT 3       UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                 ) seq
             ) m
             LEFT JOIN payments p
                 ON  YEAR(p.paid_at)  = YEAR(m.month_start)
                 AND MONTH(p.paid_at) = MONTH(m.month_start)
             GROUP BY m.month_start
             ORDER BY m.month_start ASC"
        );
    }

    public function getSubscriptionBreakdown(): array
    {
        return $this->query(
            "SELECT
                p.name AS plan_name,
                COUNT(u.id) AS subscriber_count,
                ROUND(COUNT(u.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM users WHERE status = 'active'), 0), 1) AS pct
             FROM plans p
             LEFT JOIN users u ON u.plan_id = p.id AND u.status = 'active'
             WHERE p.status = 'active'
             GROUP BY p.id, p.name
             ORDER BY FIELD(p.name, 'Premium', 'Basic', 'Free')"
        );
    }

    public function getRecentVideos(): array
    {
        return $this->query(
            "SELECT v.title, c.name AS category, v.duration_sec, v.thumbnail, v.views, v.status, v.created_at
             FROM videos v
             JOIN categories c ON c.id = v.category_id
             ORDER BY v.created_at DESC
             LIMIT 20"
        );
    }

    public function getTopContent(): array
    {
        return $this->query(
            "SELECT v.title, c.name AS cat, v.views,
                    ROUND(v.views * 100.0 / NULLIF((SELECT MAX(views) FROM videos), 0), 0) AS pct
             FROM videos v
             JOIN categories c ON c.id = v.category_id
             ORDER BY v.views DESC
             LIMIT 30"
        );
    }

    public function getActivityFeed(): array
    {
        return $this->query(
            "SELECT actor, action, module, details, icon_color, logged_at
             FROM activity_logs
             ORDER BY logged_at DESC
             LIMIT 4"
        );
    }

    public function getNavCounts(): array
    {
        return $this->queryOne(
            "SELECT
                (SELECT COUNT(*) FROM videos) AS videos,
                (SELECT COUNT(*) FROM users WHERE status = 'active') AS users,
                (SELECT COUNT(*) FROM reviews WHERE status = 'pending') AS reviews,
                (SELECT COUNT(*) FROM reports WHERE status = 'pending') AS reports,
                (SELECT COUNT(*) FROM admin_messages WHERE is_read = 0) AS messages"
        ) ?? [];
    }
}