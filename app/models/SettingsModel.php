<?php
// ============================================================
//  app/models/SettingsModel.php
// ============================================================

class SettingsModel extends BaseModel
{
    public function getAll(): array
    {
        $rows   = $this->query("SELECT setting_key, val FROM settings");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['val'];
        }
        return $result;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->queryOne(
            "SELECT val FROM settings WHERE setting_key=:key",
            [':key' => $key]
        );
        return $row ? $row['val'] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->execute(
            "INSERT INTO settings (setting_key, val) VALUES (:key, :val)
             ON DUPLICATE KEY UPDATE val = :val_update",
            [':key' => $key, ':val' => $value, ':val_update' => $value]
        );
    }

    public function saveMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }
}
