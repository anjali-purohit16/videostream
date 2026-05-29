<?php
// ============================================================
//  app/models/ActivityLogModel.php
// ============================================================

class ActivityLogModel extends BaseModel
{
    public function getAll(string $search = '', string $action = '', string $date = ''): array
    {
        $sql    = "SELECT id, actor, action, module, details, ip_address, logged_at
                   FROM activity_logs WHERE 1=1";
        $params = [];
        if ($search) { $sql .= " AND actor LIKE :s"; $params[':s'] = "%$search%"; }
        if ($action) { $sql .= " AND action = :action"; $params[':action'] = $action; }
        if ($date)   { $sql .= " AND DATE(logged_at) = :date"; $params[':date'] = $date; }
        $sql .= " ORDER BY logged_at DESC LIMIT 200";
        return $this->query($sql, $params);
    }

    public function log(string $actor, string $action, string $module, string $details, string $ip): void
    {
        $this->execute(
            "INSERT INTO activity_logs (actor, action, module, details, ip_address)
             VALUES (:actor, :action, :module, :details, :ip)",
            [':actor' => $actor, ':action' => $action, ':module' => $module,
             ':details' => $details, ':ip' => $ip]
        );
    }

    public function clearAll(): int
    {
        return $this->execute("DELETE FROM activity_logs");
    }
}
