<?php
// ==========================================
// Controller Class: ManageIssueSymptomsController
// ==========================================
class ManageIssueSymptomsController {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getCategories() {
        // ดึงหมวดหมู่และต่อชื่อประเภทหลักมาโชว์ให้เลือกง่ายขึ้น
        $sql = "SELECT c.id, c.name_th, rt.name_th as rt_name 
                FROM issue_categories c 
                LEFT JOIN request_types rt ON c.request_type_id = rt.id 
                ORDER BY rt.name_th ASC, c.name_th ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT s.*, c.name_th as cat_name, rt.name_th as rt_name 
                FROM issue_symptoms s 
                LEFT JOIN issue_categories c ON s.category_id = c.id 
                LEFT JOIN request_types rt ON c.request_type_id = rt.id 
                ORDER BY s.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->pdo->query("SELECT COUNT(*) FROM issue_symptoms")->fetchColumn();
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
            $stmt = $this->pdo->prepare("INSERT INTO issue_symptoms (category_id, code, name_th, sla_minutes) VALUES (:cat_id, :code, :name_th, :sla)");
            $stmt->execute([
                'cat_id' => $data['category_id'], 
                'code' => $code, 
                'name_th' => trim($data['name_th']),
                'sla' => (int)$data['sla_minutes']
            ]);
            return ['status' => 'success', 'message' => 'เพิ่มข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส หรือ ชื่ออาการในหมวดหมู่นี้ มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function edit($data) {
        try {
            $code = empty(trim($data['code'])) ? null : trim($data['code']);
            $stmt = $this->pdo->prepare("UPDATE issue_symptoms SET category_id = :cat_id, code = :code, name_th = :name_th, sla_minutes = :sla WHERE id = :id");
            $stmt->execute([
                'cat_id' => $data['category_id'], 
                'code' => $code, 
                'name_th' => trim($data['name_th']),
                'sla' => (int)$data['sla_minutes'],
                'id' => $data['id']
            ]);
            return ['status' => 'success', 'message' => 'แก้ไขข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส หรือ ชื่ออาการในหมวดหมู่นี้ มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function delete($id) {
        try {
            // ON DELETE CASCADE ถูกเซ็ตไว้ใน Database แล้ว ถ้าลบได้จะลบทันที
            $stmt = $this->pdo->prepare("DELETE FROM issue_symptoms WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>