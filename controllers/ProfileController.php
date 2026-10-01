<?php

declare(strict_types=1);

class ProfileController
{
    private PDO $pdo;
    private int $userId;

    public function __construct(PDO $pdo, int $userId)
    {
        $this->pdo    = $pdo;
        $this->userId = $userId;
    }

    public function getUserData(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT username, display_th, phone_ext, role FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $this->userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function handleRequest(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        if (isset($_POST['update_profile'])) {
            return $this->updateProfile(
                trim($_POST['display_th'] ?? ''),
                trim($_POST['phone_ext']  ?? '')
            );
        }

        if (isset($_POST['update_password'])) {
            return $this->updatePassword(
                $_POST['old_password']     ?? '',
                $_POST['new_password']     ?? '',
                $_POST['confirm_password'] ?? ''
            );
        }

        return null;
    }

    private function updateProfile(string $displayTh, string $phoneExt): array
    {
        if ($displayTh === '') {
            return ['status' => 'error', 'message' => 'กรุณากรอกชื่อที่แสดง'];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users SET display_th = :display_th, phone_ext = :phone_ext WHERE id = :id'
        );
        $ok = $stmt->execute([
            'display_th' => $displayTh,
            'phone_ext'  => $phoneExt,
            'id'         => $this->userId,
        ]);

        return $ok
            ? ['status' => 'success', 'message' => 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว']
            : ['status' => 'error',   'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
    }

    private function updatePassword(string $oldPassword, string $newPassword, string $confirmPassword): array
    {
        if ($oldPassword === '' || $newPassword === '') {
            return ['status' => 'error', 'message' => 'กรุณากรอกรหัสผ่านให้ครบ'];
        }

        if (strlen($newPassword) < 6) {
            return ['status' => 'error', 'message' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['status' => 'error', 'message' => 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน'];
        }

        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $this->userId]);
        $userData = $stmt->fetch();

        if (!$userData || !password_verify($oldPassword, $userData['password'])) {
            return ['status' => 'error', 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'];
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');

        return $stmt->execute(['password' => $hashed, 'id' => $this->userId])
            ? ['status' => 'success', 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ']
            : ['status' => 'error',   'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'];
    }
}