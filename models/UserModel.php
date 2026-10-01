<?php

declare(strict_types=1);

class UserModel
{
    private PDO $pdo;

    /** คอลัมน์ที่ปลอดภัยสำหรับ SELECT (ไม่รวม password) */
    private const SAFE_COLUMNS = 'id, username, display_th, phone_ext, role, solver, created_at';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** ดึงพร้อม password_hash สำหรับ login เท่านั้น */
    public function getByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password, display_th, phone_ext, role, solver FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => trim($username)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAllUsers(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTotalCount(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(id) FROM users')->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password, display_th, phone_ext, role, solver)
             VALUES (:username, :password, :display_th, :phone_ext, :role, :solver)'
        );
        $stmt->execute([
            'username'   => trim($data['username']),
            'password'   => $data['password'],
            'display_th' => trim($data['display_th']),
            'phone_ext'  => trim($data['phone_ext']),
            'role'       => $data['role'] ?? 'MEMBER',
            'solver'     => (int)($data['solver'] ?? 0),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets   = ['display_th = :display_th', 'phone_ext = :phone_ext', 'role = :role', 'solver = :solver'];
        $params = [
            'display_th' => trim($data['display_th']),
            'phone_ext'  => trim($data['phone_ext']),
            'role'       => $data['role'],
            'solver'     => (int)$data['solver'],
            'id'         => $id,
        ];

        if (!empty($data['password'])) {
            $sets[]            = 'password = :password';
            $params['password'] = $data['password'];
        }

        $sql  = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** ดึงเฉพาะ users ที่เป็น solver สำหรับ dropdown */
    public function getSolvers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, display_th FROM users WHERE solver = 1 ORDER BY display_th ASC'
        );
        return $stmt->fetchAll();
    }
}