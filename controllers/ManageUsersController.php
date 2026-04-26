<?php
class ManageUsersController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. ดึงผู้ใช้ทั้งหมดมาแสดงในตาราง (เพิ่มดึง solver มาด้วย)
    public function getAllUsers($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->pdo->prepare("SELECT id, username, display_th, phone_ext, role, solver, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
        $totalPages = ceil($total / $limit);

        return [
            'users' => $users,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $total
        ];
    }

    // 2. จัดการข้อมูลจากฟอร์ม (POST Request)
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

        // --- เพิ่มผู้ใช้ใหม่ ---
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            return $this->addUser($_POST);
        }

        // --- แก้ไขผู้ใช้ ---
        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            return $this->editUser($_POST);
        }

        // --- ลบผู้ใช้ ---
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            return $this->deleteUser($_POST['user_id']);
        }

        return null;
    }

    private function addUser($data) {
        try {
            // เช็คว่า Username ซ้ำไหม
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => trim($data['username'])]);
            if ($stmt->fetch()) {
                return ['status' => 'error', 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'];
            }

            // ถ้ามี checkbox ติ๊กมาจะมีค่าใน $_POST['solver'] = 1 ถ้าไม่ติ๊กจะไม่มีคีย์นี้
            $solver = isset($data['solver']) ? 1 : 0;
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, display_th, phone_ext, role, solver) VALUES (:username, :password, :display_th, :phone_ext, :role, :solver)");
            $stmt->execute([
                'username' => trim($data['username']),
                'password' => $hashed_password,
                'display_th' => trim($data['display_th']),
                'phone_ext' => trim($data['phone_ext']),
                'role' => $data['role'],
                'solver' => $solver
            ]);
            return ['status' => 'success', 'message' => 'เพิ่มผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    private function editUser($data) {
        try {
            $solver = isset($data['solver']) ? 1 : 0;

            // ถ้ายกเลิกการเปลี่ยนรหัสผ่าน (ปล่อยว่าง)
            if (empty($data['password'])) {
                $stmt = $this->pdo->prepare("UPDATE users SET display_th = :display_th, phone_ext = :phone_ext, role = :role, solver = :solver WHERE id = :id");
                $params = [
                    'display_th' => trim($data['display_th']),
                    'phone_ext' => trim($data['phone_ext']),
                    'role' => $data['role'],
                    'solver' => $solver,
                    'id' => $data['user_id']
                ];
            } else {
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE users SET password = :password, display_th = :display_th, phone_ext = :phone_ext, role = :role, solver = :solver WHERE id = :id");
                $params = [
                    'password' => $hashed_password,
                    'display_th' => trim($data['display_th']),
                    'phone_ext' => trim($data['phone_ext']),
                    'role' => $data['role'],
                    'solver' => $solver,
                    'id' => $data['user_id']
                ];
            }
            $stmt->execute($params);
            return ['status' => 'success', 'message' => 'อัปเดตข้อมูลผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    private function deleteUser($id) {
        try {
            // ป้องกันการลบตัวเอง (เช็คที่ View ด้วย แต่กันเหนียวไว้)
            if ($id == $_SESSION['user_id']) {
                 return ['status' => 'error', 'message' => 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'];
            }

            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ['status' => 'success', 'message' => 'ลบผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}
?>