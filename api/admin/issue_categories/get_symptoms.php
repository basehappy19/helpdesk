<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db/conn.php';

// 1. รับค่าและตรวจสอบ category_id จาก GET request
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(
        ['ok' => false, 'message' => 'Invalid ID'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

try {
    if (method_exists($pdo, 'setAttribute')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // 2. ดึงข้อมูลอาการ (Symptoms) ที่เชื่อมอยู่กับ Category นี้
    $sql = "
        SELECT id, category_id, code, name_th, sla_minutes 
        FROM issue_symptoms 
        WHERE category_id = :id 
        ORDER BY name_th ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $categoryId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(
        ['ok' => true, 'data' => $rows],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['ok' => false, 'error' => 'Server error.', 'message' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
?>