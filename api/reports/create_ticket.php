<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

/** สร้าง slug สั้น ๆ สำหรับ code และกันซ้ำด้วย suffix */
function make_code_slug(string $text): string
{
    $t = mb_strtolower($text, 'UTF-8');
    // replace non-word ด้วย dash
    $t = preg_replace('/[^\p{L}\p{N}]+/u', '-', $t);
    $t = trim($t, '-');
    if ($t === '') $t = 'x';
    // limit length หน่อย
    return mb_substr($t, 0, 48, 'UTF-8');
}

function ensure_unique_code(PDO $pdo, string $table, string $codeBase): string
{
    $code = $codeBase;
    $i = 1;
    $sql = "SELECT 1 FROM {$table} WHERE code = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    while (true) {
        $stmt->execute([$code]);
        if (!$stmt->fetch()) return $code;
        $code = $codeBase . '-' . $i;
        $i++;
    }
}

/** request_types: หา id ด้วย code */
function getRequestTypeIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM request_types WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

/** issue_categories: หา id ด้วย code */
function getCategoryIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM issue_categories WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

/** issue_symptoms: หา id ด้วย code */
function getSymptomIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM issue_symptoms WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

/**
 * สร้างรหัสสุ่มสำหรับ category หรือ symptom
 * เช่น CAT-20251012-4F3A9C  หรือ  SYM-20251012-81D2B7
 */
function gen_auto_code(string $prefix): string
{
    $date = date('Ymd');
    $rand = strtoupper(bin2hex(random_bytes(3))); // 6 ตัว
    return sprintf('%s-%s-%s', $prefix, $date, $rand);
}

/** สร้าง category ใหม่ (เมื่อ user เลือก other) */
function createCategoryWithCode(PDO $pdo, int $requestTypeId, string $nameTh): array
{
    $base = gen_auto_code('CAT');

    $code = ensure_unique_code($pdo, 'issue_categories', $base);

    $stmt = $pdo->prepare("
        INSERT INTO issue_categories (request_type_id, code, name_th)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$requestTypeId, $code, $nameTh]);

    return ['id' => (int)$pdo->lastInsertId(), 'code' => $code];
}

/** สร้าง symptom ใหม่ (เมื่อ user เลือก other) */
function createSymptomWithCode(PDO $pdo, int $categoryId, string $nameTh): array
{
    $base = gen_auto_code('SYM');
    $code = ensure_unique_code($pdo, 'issue_symptoms', $base);

    $stmt = $pdo->prepare("
        INSERT INTO issue_symptoms (category_id, code, name_th)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$categoryId, $code, $nameTh]);

    return ['id' => (int)$pdo->lastInsertId(), 'code' => $code];
}

/** สร้าง ticket code แบบ TYYYYMMDD-XXXXXX */
function new_ticket_code(): string
{
    $date = date('Ymd');
    $rand = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
    return "T{$date}-{$rand}";
}

/** ลอง insert ticket พร้อมสร้าง code และ retry เมื่อชน UNIQUE */
function insert_ticket_with_code(PDO $pdo, array $payload, int $maxRetry = 5): array
{
    // $payload ต้องมี: request_type_id, issue_category_id, issue_symptom_id,
    // department, building, floor, service_point (nullable), phone, reporter
    $sql = "
        INSERT INTO tickets
        (code, request_type_id, category_id, symptom_id, department, building, floor, service_point, phone_ext, reporter_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    for ($i = 0; $i < $maxRetry; $i++) {
        $code = new_ticket_code();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $code,
                $payload['request_type_id'],
                $payload['issue_category_id'],
                $payload['issue_symptom_id'],
                $payload['department'],
                $payload['building'],
                $payload['floor'],
                $payload['service_point'] ?? null,
                $payload['phone'],
                $payload['reporter'],
            ]);
            return ['ok' => true, 'code' => $code, 'id' => (int)$pdo->lastInsertId()];
        } catch (PDOException $e) {
            // 1062 = duplicate entry
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                // ชน code — loop ไปสุ่มใหม่
                continue;
            }
            throw $e; // error อื่น โยนต่อ
        }
    }
    return ['ok' => false, 'error' => 'Could not generate unique ticket code'];
}

// ---------- รับค่าจากฟอร์ม (เป็น code) ----------
$request_type_code   = trim((string)($_POST['request_type'] ?? ''));
$category_code       = trim((string)($_POST['issue_category'] ?? ''));
$category_other_text = trim((string)($_POST['issue_category_other'] ?? ''));
$symptom_code        = trim((string)($_POST['issue_symptom'] ?? ''));
$symptom_other_text  = trim((string)($_POST['issue_symptom_other'] ?? ''));

$department   = trim((string)($_POST['department'] ?? ''));
$building     = trim((string)($_POST['building'] ?? ''));
$floor        = trim((string)($_POST['floor'] ?? ''));
$service_point = trim((string)($_POST['service_point'] ?? ''));
$phone        = trim((string)($_POST['phone'] ?? ''));
$reporter     = trim((string)($_POST['reporter'] ?? ''));

// ---- normalize: ถ้า code ว่างแต่มี _other ให้ตีความเป็น 'other' ----
if ($category_code === '' && $category_other_text !== '') {
    $category_code = '__other__';
}
if ($symptom_code === '' && $symptom_other_text !== '') {
    $symptom_code = '__other__';
}

// ---------- Validate ขั้นต้น ----------
$errors = [];
if ($request_type_code === '') $errors[] = 'request_type code is required';
if ($category_code === '')     $errors[] = 'issue_category code is required';
if ($symptom_code === '')      $errors[] = 'issue_symptom code is required';
if ($department === '')        $errors[] = 'department is required';
if ($building === '')          $errors[] = 'building is required';
if ($floor === '')             $errors[] = 'floor is required';
if ($phone === '')             $errors[] = 'phone is required';
if ($reporter === '')          $errors[] = 'reporter is required';

if ($category_code === '__other__' && $category_other_text === '') {
    $errors[] = 'issue_category_other is required when category is other';
}
if ($symptom_code === '__other__' && $symptom_other_text === '') {
    $errors[] = 'issue_symptom_other is required when symptom is other';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// ---------- ทำงานใน Transaction ----------
$pdo->beginTransaction();
try {
    // 1) request_type_id จาก code
    $requestTypeId = getRequestTypeIdByCode($pdo, $request_type_code);
    if (!$requestTypeId) {
        throw new RuntimeException('Invalid request_type code');
    }

    if ($category_code === '__other__') {
        $cat = createCategoryWithCode($pdo, $requestTypeId, $category_other_text);
        $categoryId   = $cat['id'];
        $categoryCode = $cat['code'];
    } else {
        $categoryId = getCategoryIdByCode($pdo, $category_code);
        if (!$categoryId) throw new RuntimeException('Invalid issue_category code');
        $categoryCode = $category_code;
    }

    // 3) symptom_id (ต้องผูกกับ category ที่ได้)
    if ($symptom_code === '__other__') {
        $sym = createSymptomWithCode($pdo, $categoryId, $symptom_other_text);
        $symptomId   = $sym['id'];
        $symptomCode = $sym['code'];
    } else {
        $symptomId = getSymptomIdByCode($pdo, $symptom_code);
        if (!$symptomId) throw new RuntimeException('Invalid issue_symptom code');
        $symptomCode = $symptom_code;
    }

    // (ทางเลือก) ตรวจว่า symptom นี้อยู่ใน categoryId เดียวกันจริงไหม
    $chk = $pdo->prepare("SELECT category_id FROM issue_symptoms WHERE id = ?");
    $chk->execute([$symptomId]);
    $owner = $chk->fetchColumn();
    if ((int)$owner !== (int)$categoryId) {
        throw new RuntimeException('issue_symptom code does not belong to chosen issue_category');
    }

    $data = [
        'request_type_id'   => $requestTypeId,
        'issue_category_id' => $categoryId,
        'issue_symptom_id'  => $symptomId,
        'department'        => $department,
        'building'          => $building,
        'floor'             => $floor,
        'service_point'     => ($service_point !== '' ? $service_point : null),
        'phone'             => $phone,
        'reporter'          => $reporter,
    ];

    $result = insert_ticket_with_code($pdo, $data, 5);
    if (!$result['ok']) {
        throw new RuntimeException($result['error']);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'message' => 'Ticket created',
        'ticket_code' => $result['code'],
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
}
