<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/WorkCategoryModel.php';

class WorkCategoryController
{
    private WorkCategoryModel $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new WorkCategoryModel($pdo);
    }

    public function handleRequest(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $name = trim($_POST['name_th'] ?? '');
            $id   = (int)($_POST['cat_id'] ?? 0);

            if ($name === '') {
                return ['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่'];
            }

            if ($action === 'edit') {
                if ($id <= 0) {
                    return ['status' => 'error', 'message' => 'ไม่พบหมวดหมู่ที่ต้องการแก้ไข'];
                }
                return $this->model->update($id, $name)
                    ? ['status' => 'success', 'message' => 'แก้ไขสำเร็จ']
                    : ['status' => 'error',   'message' => 'แก้ไขไม่สำเร็จ'];
            }

            // add
            $this->model->create($name);
            return ['status' => 'success', 'message' => 'เพิ่มสำเร็จ'];
        }

        if ($action === 'delete') {
            $id = (int)($_POST['cat_id'] ?? 0);
            if ($id <= 0) {
                return ['status' => 'error', 'message' => 'ไม่พบหมวดหมู่ที่ต้องการลบ'];
            }
            if ($this->model->isUsed($id)) {
                return ['status' => 'error', 'message' => 'ไม่สามารถลบได้เนื่องจากมีงานผูกอยู่'];
            }
            return $this->model->delete($id)
                ? ['status' => 'success', 'message' => 'ลบหมวดหมู่สำเร็จ']
                : ['status' => 'error',   'message' => 'เกิดข้อผิดพลาดในการลบ'];
        }

        return null;
    }

    public function getAllCategories(int $page = 1, int $limit = 10): array
    {
        $offset     = ($page - 1) * $limit;
        $categories = $this->model->getPaginated($limit, $offset);
        $total      = $this->model->countAll();
        $totalPages = (int)ceil($total / $limit);

        return [
            'categories'    => $categories,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'total_records' => $total,
        ];
    }
}