<?php
require_once __DIR__ . '/../models/WorkCategoryModel.php';

class WorkCategoryController {
    private $model;

    public function __construct($pdo) {
        $this->model = new WorkCategoryModel($pdo);
    }

    // ฟังก์ชันที่คอยรับ POST Request (เพิ่ม/แก้ไข/ลบ)
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
        $action = $_POST['action'] ?? '';

        // จัดการ เพิ่ม/แก้ไข
        if ($action === 'add' || $action === 'edit') {
            $name = trim($_POST['name_th'] ?? '');
            $id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : null;
            
            if (empty($name)) {
                return ['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่'];
            }

            return ($action === 'edit') 
                ? ($this->model->update($id, $name) ? ['status' => 'success', 'message' => 'แก้ไขสำเร็จ'] : ['status' => 'error', 'message' => 'แก้ไขไม่สำเร็จ'])
                : ($this->model->create($name) ? ['status' => 'success', 'message' => 'เพิ่มสำเร็จ'] : ['status' => 'error', 'message' => 'เพิ่มไม่สำเร็จ']);
        }

        // จัดการ ลบ
        if ($action === 'delete') {
            $id = intval($_POST['cat_id']);
            
            // เช็คก่อนว่ามีงานผูกอยู่หรือไม่
            if ($this->model->isUsed($id)) {
                return ['status' => 'error', 'message' => 'ไม่สามารถลบได้เนื่องจากมีงานผูกอยู่'];
            }
            
            return $this->model->delete($id) ? ['status' => 'success', 'message' => 'ลบหมวดหมู่สำเร็จ'] : ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบ'];
        }
    }

    // ฟังก์ชันสำหรับดึงข้อมูลไปแสดงในตาราง (ที่ผมลืมให้ไปรอบที่แล้ว 🙏)
    public function getAllCategories($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // ดึงข้อมูลผ่าน Model
        $categories = $this->model->getPaginated($limit, $offset);
        $total = $this->model->countAll();
        $totalPages = ceil($total / $limit);

        return [
            'categories' => $categories,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $total
        ];
    }
}