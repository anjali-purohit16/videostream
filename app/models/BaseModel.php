<?php

//BaseModel.php  —  Shared query helpers


abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Execute a SELECT and return all rows */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Execute a SELECT and return one row */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Execute an INSERT/UPDATE/DELETE — returns affected rows */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Returns the last inserted auto-increment ID */
    protected function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }
}
