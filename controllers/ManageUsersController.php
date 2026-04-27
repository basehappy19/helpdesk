<?php
require_once __DIR__ . '/../models/UserModel.php';

class ManageUsersController
{
    private $userModel;

    public function __construct($pdo)
    {
        $this->userModel = new UserModel($pdo);
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            return $this->addUser($_POST);
        }

        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            return $this->editUser($_POST);
        }

        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            return $this->deleteUser($_POST['user_id']);
        }

        return null;
    }

    public function getAllUsers($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $users = $this->userModel->getAllUsers($limit, $offset);
        $total = $this->userModel->getTotalCount();
        $totalPages = ceil($total / $limit);

        return [
            'users' => $users,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $total
        ];
    }

    public function login($username, $password)
    {
        $user = $this->userModel->getByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            return ['id' => (int)$user['id'], 'role' => $user['role']];
        }
        return null;
    }

    private function addUser($data)
    {
        try {
            if ($this->userModel->getByUsername($data['username'])) {
                return ['status' => 'error', 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'];
            }

            $insertData = [
                'username'   => $data['username'],
                'password'   => password_hash($data['password'], PASSWORD_DEFAULT),
                'display_th' => $data['display_th'],
                'phone_ext'  => $data['phone_ext'],
                'role'       => $data['role'],
                'solver'     => isset($data['solver']) ? 1 : 0
            ];

            $this->userModel->create($insertData);
            return ['status' => 'success', 'message' => 'เพิ่มผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    private function editUser($data)
    {
        try {
            // 1. เตรียมข้อมูลที่จะส่งให้ Model (Logic อยู่ที่ Controller)
            $updateData = [
                'display_th' => $data['display_th'],
                'phone_ext'  => $data['phone_ext'],
                'role'       => $data['role'],
                'solver'     => isset($data['solver']) ? 1 : 0
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $this->userModel->update($data['user_id'], $updateData);

            return ['status' => 'success', 'message' => 'อัปเดตข้อมูลผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    private function deleteUser($id)
    {
        try {
            if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
                return ['status' => 'error', 'message' => 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'];
            }

            $this->userModel->delete($id);

            return ['status' => 'success', 'message' => 'ลบผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}
