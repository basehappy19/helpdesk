<?php
// ==========================================
// Controller Class: ManageRequestTypesController
// ==========================================
class ManageRequestTypesController {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getAll($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM request_types ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->pdo->query("SELECT COUNT(*) FROM request_types")->fetchColumn();
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
            $stmt = $this->pdo->prepare("INSERT INTO request_types (code, name_th) VALUES (:code, :name_th)");
            $stmt->execute(['code' => $code, 'name_th' => trim($data['name_th'])]);
            return ['status' => 'success', 'message' => 'เพิ่มข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส Code นี้มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function edit($data) {
        try {
            $code = empty(trim($data['code'])) ? null : trim($data['code']);
            $stmt = $this->pdo->prepare("UPDATE request_types SET code = :code, name_th = :name_th WHERE id = :id");
            $stmt->execute(['code' => $code, 'name_th' => trim($data['name_th']), 'id' => $data['id']]);
            return ['status' => 'success', 'message' => 'แก้ไขข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'รหัส Code นี้มีในระบบแล้ว'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM request_types WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีหมวดหมู่ย่อยเชื่อมโยงอยู่'];
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>