<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conn.php';

try {
    if (method_exists($pdo, 'setAttribute')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $request_type_id = isset($_GET['request_type_id']) ? intval($_GET['request_type_id']) : 0;

    if ($request_type_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing or invalid request_type_id']);
        exit;
    }

    $sql = "SELECT id, code, name_th 
            FROM issue_categories 
            WHERE request_type_id = :request_type_id
            ORDER BY name_th ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':request_type_id' => $request_type_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error.'], JSON_UNESCAPED_UNICODE);
}
