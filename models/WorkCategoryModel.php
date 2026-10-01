<?php

declare(strict_types=1);

class WorkCategoryModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        return $this->pdo
            ->query('SELECT id, name_th FROM work_log_categories ORDER BY id ASC')
            ->fetchAll();
    }

    public function getPaginated(int $limit, int $offset): array
    {
        $sql = "
            SELECT c.id, c.name_th, COUNT(w.id) AS task_count
            FROM work_log_categories c
            LEFT JOIN daily_work_logs w ON c.id = w.category_id
            GROUP BY c.id, c.name_th
            ORDER BY c.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(id) FROM work_log_categories')->fetchColumn();
    }

    /** @return int lastInsertId */
    public function create(string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO work_log_categories (name_th) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE work_log_categories SET name_th = :name WHERE id = :id');
        return $stmt->execute(['name' => $name, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM work_log_categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function isUsed(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(id) FROM daily_work_logs WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}