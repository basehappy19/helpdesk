<?php

class ProfileController {
    private $pdo;
    private $userId;

    // รับค่าการเชื่อมต่อฐานข้อมูลและ ID ผู้ใช้เมื่อเรียกใช้ Class
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    // ดึงข้อมูลผู้ใช้ปัจจุบัน
    public function getUserData() {
        $stmt = $this->pdo->prepare("SELECT username, display_th, phone_ext, role FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        return $stmt->fetch();
    }

    // ฟังก์ชันหลักสำหรับจัดการ Request ที่ส่งเข้ามา
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null; // ถ้าไม่ได้ส่งฟอร์มมา ไม่ต้องทำอะไร
        }

        if (isset($_POST['update_profile'])) {
            return $this->updateProfile($_POST['display_th'], $_POST['phone_ext']);
        }

        if (isset($_POST['update_password'])) {
            return $this->updatePassword($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password']);
        }

        return null;
    }

    // จัดการอัปเดตโปรไฟล์
    private function updateProfile($display_th, $phone_ext) {
        $stmt = $this->pdo->prepare("UPDATE users SET display_th = :display_th, phone_ext = :phone_ext WHERE id = :id");
        $success = $stmt->execute([
            'display_th' => trim($display_th),
            'phone_ext' => trim($phone_ext),
            'id' => $this->userId
        ]);

        if ($success) {
            return ['status' => 'success', 'message' => 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว'];
        }
        return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
    }

    // จัดการเปลี่ยนรหัสผ่าน
    private function updatePassword($old_password, $new_password, $confirm_password) {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->userId]);
        $user_data = $stmt->fetch();

        // ตรวจสอบรหัสเดิม
        if (!password_verify($old_password, $user_data['password'])) {
            return ['status' => 'error', 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'];
        }

        // ตรวจสอบรหัสใหม่ว่าตรงกันไหม
        if ($new_password !== $confirm_password) {
            return ['status' => 'error', 'message' => 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน'];
        }

        // อัปเดตรหัสผ่านใหม่
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        
        if ($stmt->execute(['password' => $hashed_password, 'id' => $this->userId])) {
            return ['status' => 'success', 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'];
        }
        
        return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'];
    }
}
?>