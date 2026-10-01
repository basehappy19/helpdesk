<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/UserModel.php';

class ManageUsersController
{
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new UserModel($pdo);
    }

    public function handleRequest(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $action = $_POST['action'] ?? '';

        return match ($action) {
            'add'    => $this->addUser($_POST),
            'edit'   => $this->editUser($_POST),
            'delete' => $this->deleteUser((int)($_POST['user_id'] ?? 0)),
            default  => null,
        };
    }

    public function getAllUsers(int $page = 1, int $limit = 10): array
    {
        $offset     = ($page - 1) * $limit;
        $users      = $this->userModel->getAllUsers($limit, $offset);
        $total      = $this->userModel->getTotalCount();
        $totalPages = (int)ceil($total / $limit);

        return [
            'users'          => $users,
            'total_pages'    => $totalPages,
            'current_page'   => $page,
            'total_records'  => $total,
        ];
    }

    /**
     * ตรวจสอบ username/password แล้วคืน array ข้อมูล user (id, role) หรือ null
     */
    public function login(string $username, string $password): ?array
    {
        if ($username === '' || $password === '') {
            return null;
        }

        $user = $this->userModel->getByUsername($username);

        if (!$user) {
            // ใช้ dummy_verify เพื่อป้องกัน timing attack แม้ user ไม่มีในระบบ
            password_verify($password, '$2y$12$invalidhashplaceholder000000000000000000000000000000');
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return [
            'id'   => (int)$user['id'],
            'role' => $user['role'],
        ];
    }

    private function addUser(array $data): array
    {
        try {
            $username = trim($data['username'] ?? '');
            if ($username === '') {
                return ['status' => 'error', 'message' => 'กรุณากรอก username'];
            }

            if ($this->userModel->getByUsername($username)) {
                return ['status' => 'error', 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'];
            }

            $password = $data['password'] ?? '';
            if (strlen($password) < 6) {
                return ['status' => 'error', 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];
            }

            $this->userModel->create([
                'username'   => $username,
                'password'   => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'display_th' => trim($data['display_th'] ?? ''),
                'phone_ext'  => trim($data['phone_ext'] ?? ''),
                'role'       => $data['role'] ?? 'MEMBER',
                'solver'     => isset($data['solver']) ? 1 : 0,
            ]);

            return ['status' => 'success', 'message' => 'เพิ่มผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            error_log('addUser error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
        }
    }

    private function editUser(array $data): array
    {
        try {
            $userId = (int)($data['user_id'] ?? 0);
            if ($userId <= 0) {
                return ['status' => 'error', 'message' => 'ไม่พบผู้ใช้งาน'];
            }

            $updateData = [
                'display_th' => trim($data['display_th'] ?? ''),
                'phone_ext'  => trim($data['phone_ext'] ?? ''),
                'role'       => $data['role'] ?? 'MEMBER',
                'solver'     => isset($data['solver']) ? 1 : 0,
            ];

            $password = $data['password'] ?? '';
            if ($password !== '') {
                if (strlen($password) < 6) {
                    return ['status' => 'error', 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];
                }
                $updateData['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $this->userModel->update($userId, $updateData);

            return ['status' => 'success', 'message' => 'อัปเดตข้อมูลผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            error_log('editUser error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
        }
    }

    private function deleteUser(int $id): array
    {
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'ไม่พบผู้ใช้งาน'];
        }

        // เช็คว่าไม่ได้ลบตัวเอง — ใช้ $_SESSION['user']['id'] เพียงจุดเดียว
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        if ($id === $currentUserId) {
            return ['status' => 'error', 'message' => 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'];
        }

        try {
            $this->userModel->delete($id);
            return ['status' => 'success', 'message' => 'ลบผู้ใช้งานสำเร็จ'];
        } catch (PDOException $e) {
            error_log('deleteUser error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล'];
        }
    }
}
