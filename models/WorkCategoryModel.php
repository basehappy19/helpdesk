<?php
class WorkCategoryModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM work_log_categories ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginated($limit, $offset) {
        $sql = "SELECT c.*, COUNT(w.id) as task_count 
                FROM work_log_categories c 
                LEFT JOIN daily_work_logs w ON c.id = w.category_id 
                GROUP BY c.id ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll() {
        return (int)$this->pdo->query("SELECT COUNT(id) FROM work_log_categories")->fetchColumn();
    }

    public function create($name) {
        return $this->pdo->prepare("INSERT INTO work_log_categories (name_th) VALUES (?)")->execute([$name]);
    }

    public function update($id, $name) {
        return $this->pdo->prepare("UPDATE work_log_categories SET name_th = ? WHERE id = ?")->execute([$name, $id]);
    }

    public function delete($id) {
        return $this->pdo->prepare("DELETE FROM work_log_categories WHERE id = ?")->execute([$id]);
    }

    public function isUsed($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(id) FROM daily_work_logs WHERE category_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}