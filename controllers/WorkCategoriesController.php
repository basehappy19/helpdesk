<?php
class WorkCategoriesController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'add' || $action === 'edit') {
                return $this->saveCategory();
            } elseif ($action === 'delete') {
                return $this->deleteCategory();
            }
        }
        return null;
    }

    public function getAllCategories() {
        $sql = "
            SELECT 
                c.id, 
                c.name_th, 
                c.created_at,
                COUNT(w.id) as task_count
            FROM work_log_categories c
            LEFT JOIN daily_work_logs w ON c.id = w.category_id
            GROUP BY c.id
            ORDER BY c.id DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveCategory() {
        $id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : null;
        $name_th = trim($_POST['name_th'] ?? '');

        if (empty($name_th)) {
            return ['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่'];
        }

        try {
            if ($_POST['action'] === 'edit' && $id) {
                // แก้ไข
                $stmt = $this->pdo->prepare("UPDATE work_log_categories SET name_th = ? WHERE id = ?");
                $stmt->execute([$name_th, $id]);
                return ['status' => 'success', 'message' => 'แก้ไขหมวดหมู่สำเร็จ'];
            } else {
                // เพิ่มใหม่
                $stmt = $this->pdo->prepare("INSERT INTO work_log_categories (name_th) VALUES (?)");
                $stmt->execute([$name_th]);
                return ['status' => 'success', 'message' => 'เพิ่มหมวดหมู่ใหม่สำเร็จ'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    private function deleteCategory() {
        $id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : null;

        if (!$id) {
            return ['status' => 'error', 'message' => 'ไม่พบข้อมูลที่ต้องการลบ'];
        }

        try {
            // เช็คก่อนว่ามีงานผูกอยู่ไหม (ป้องกันการลบถ้ามีการฝืนยิง Request)
            $stmt = $this->pdo->prepare("SELECT COUNT(id) FROM daily_work_logs WHERE category_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                return ['status' => 'error', 'message' => 'ไม่สามารถลบได้เนื่องจากมีงานผูกอยู่'];
            }

            $stmt = $this->pdo->prepare("DELETE FROM work_log_categories WHERE id = ?");
            $stmt->execute([$id]);
            return ['status' => 'success', 'message' => 'ลบหมวดหมู่สำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}