<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conn.php';

try {
    if (method_exists($pdo, 'setAttribute')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $issue_category_id = isset($_GET['issue_category_id']) ? intval($_GET['issue_category_id']) : 0;
    if ($issue_category_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing or invalid issue_category_id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT id, category_id, code, name_th
            FROM issue_symptoms
            WHERE category_id = :issue_category_id
            ORDER BY name_th ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':issue_category_id' => $issue_category_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
