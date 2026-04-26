<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db/conn.php';

// 1. รับค่าและตรวจสอบ request_type_id จาก GET request
$requestTypeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestTypeId <= 0) {
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

    // 2. ดึงข้อมูลหมวดหมู่ย่อย (Categories) ที่เชื่อมอยู่กับ Request Type นี้
    $sql = "
        SELECT id, request_type_id, code, name_th 
        FROM issue_categories 
        WHERE request_type_id = :id 
        ORDER BY name_th ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $requestTypeId]);
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