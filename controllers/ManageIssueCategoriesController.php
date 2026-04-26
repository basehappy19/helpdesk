<?php
// ==========================================
// Controller Class: ManageIssueCategoriesController
// ==========================================
class ManageIssueCategoriesController {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getRequestTypes() {
        return $this->pdo->query("SELECT id, name_th FROM request_types ORDER BY name_th ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT c.*, rt.name_th as rt_name 
                FROM issue_categories c 
                LEFT JOIN request_types rt ON c.request_type_id = rt.id 
                ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->pdo->query("SELECT COUNT(*) FROM issue_categories")->fetchColumn();
        return ['data' => $data, 'total_pages' => ceil($total / $limit)];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
        if ($_POST['action'] === 'add') return $this->add($_POST);
        if ($_POST['action'] === 'edit') return $this->edit($_POST);
        if ($_POST['action'] === 'delete') return $this->delete($_POST['id']);
        return null;
    }

    private function add($data) {
        try {
            $code = empty(trim($data['code'])) ? null : trim($data['code']);
            $stmt = $this->pdo->prepare("INSERT INTO issue_categories (request_type_id, code, name_th) VALUES (:rt_id, :code, :name_th)");
            $stmt->execute(['rt_id' => $data['request_type_id'], 'code' => $code, 'name_th' => trim($data['name_th'])]);
            return ['status' => 'success', 'message' => 'เพิ่มข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส หรือ ชื่อ นี้มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function edit($data) {
        try {
            $code = empty(trim($data['code'])) ? null : trim($data['code']);
            $stmt = $this->pdo->prepare("UPDATE issue_categories SET request_type_id = :rt_id, code = :code, name_th = :name_th WHERE id = :id");
            $stmt->execute(['rt_id' => $data['request_type_id'], 'code' => $code, 'name_th' => trim($data['name_th']), 'id' => $data['id']]);
            return ['status' => 'success', 'message' => 'แก้ไขข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส หรือ ชื่อ นี้มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM issue_categories WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีอาการ(Symptoms)เชื่อมโยงอยู่'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>