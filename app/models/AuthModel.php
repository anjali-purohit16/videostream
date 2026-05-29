<?php

 class AuthModel extends BaseModel
{
    public function findAdminByEmail(string $email): ?array
    {
        return $this->queryOne('SELECT * FROM admins WHERE email = :email', [':email' => $email]);
    }

    public function findUserByEmail(string $email): ?array
    {
        return $this->queryOne(
            "SELECT u.*, p.name AS plan FROM users u JOIN plans p ON p.id = u.plan_id WHERE u.email = :email",
            [':email' => $email]
        );
    }

    public function createUser(string $name, string $email, string $password): string
    {
        $this->execute(
            "INSERT INTO users (name, email, password, plan_id, status, joined_at, last_seen)
             VALUES (:name, :email, :password, 1, 'active', NOW(), NOW())",
            [
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        return $this->lastInsertId();
    }

    public function createUserWithPasswordHash(string $name, string $email, string $passwordHash): string
    {
        $this->execute(
            "INSERT INTO users (name, email, password, plan_id, status, joined_at, last_seen)
             VALUES (:name, :email, :password, 1, 'active', NOW(), NOW())",
            [
                ':name' => $name,
                ':email' => $email,
                ':password' => $passwordHash,
            ]
        );

        return $this->lastInsertId();
    }

    public function touchUser(int $id): void
    {
        $this->execute('UPDATE users SET last_seen = NOW() WHERE id = :id', [':id' => $id]);
    }
}
