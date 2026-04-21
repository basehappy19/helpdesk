<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../db/conn.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// ---------- ฟังก์ชันส่ง Telegram (ดึงค่าจาก ENV) ----------
function sendTelegramAlert(string $message): void
{
    $botToken = getenv('TELEGRAM_BOT_TOKEN') ?: ($_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
    $chatId   = getenv('TELEGRAM_CHAT_ID') ?: ($_ENV['TELEGRAM_CHAT_ID'] ?? '');

    if (!$botToken || !$chatId) {
        return;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// ---------- รับค่าจากฟอร์ม ----------
$request_type_code   = trim((string)($_POST['request_type'] ?? ''));
$category_code       = trim((string)($_POST['issue_category'] ?? ''));
$category_other_text = trim((string)($_POST['issue_category_other'] ?? ''));
$symptom_code        = trim((string)($_POST['issue_symptom'] ?? ''));
$symptom_other_text  = trim((string)($_POST['issue_symptom_other'] ?? ''));

$department    = trim((string)($_POST['department'] ?? ''));
$building      = trim((string)($_POST['building'] ?? ''));
$floor         = trim((string)($_POST['floor'] ?? ''));
$service_point = trim((string)($_POST['service_point'] ?? ''));
$phone         = trim((string)($_POST['phone'] ?? ''));
$reporter      = trim((string)($_POST['reporter'] ?? ''));

// ---- normalize ----
if ($category_code === '' && $category_other_text !== '') {
    $category_code = '__other__';
}
if ($symptom_code === '' && $symptom_other_text !== '') {
    $symptom_code = '__other__';
}

// ---------- Validate ----------
$errors = [];
if ($request_type_code === '') $errors[] = 'request_type code is required';
if ($category_code === '')     $errors[] = 'issue_category code is required';
if ($symptom_code === '')      $errors[] = 'issue_symptom code is required';
if ($department === '')        $errors[] = 'department is required';
if ($building === '')          $errors[] = 'building is required';
if ($floor === '')             $errors[] = 'floor is required';
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

$pdo->beginTransaction();
try {
    $requestTypeId = getRequestTypeIdByCode($pdo, $request_type_code);
    if (!$requestTypeId) throw new RuntimeException('Invalid request_type code');

    // จัดการ Category
    if ($category_code === '__other__') {
        $cat = createCategoryWithCode($pdo, $requestTypeId, $category_other_text);
        $categoryId = $cat['id'];
        $categoryDisplayName = $category_other_text;
    } else {
        $categoryId = getCategoryIdByCode($pdo, $category_code);
        if (!$categoryId) throw new RuntimeException('Invalid issue_category code');
        $categoryDisplayName = $category_code;
    }

    // จัดการ Symptom
    if ($symptom_code === '__other__') {
        $sym = createSymptomWithCode($pdo, $categoryId, $symptom_other_text);
        $symptomId = $sym['id'];
        $symptomDisplayName = $symptom_other_text;
    } else {
        $symptomId = getSymptomIdByCode($pdo, $symptom_code);
        if (!$symptomId) throw new RuntimeException('Invalid issue_symptom code');
        $symptomDisplayName = $symptom_code;
    }

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
    if (!$result['ok']) throw new RuntimeException($result['error']);

    $logStmt = $pdo->prepare("
        INSERT INTO ticket_status_logs (ticket_id, from_status, to_status, changed_by)
        VALUES (:ticket_id, NULL, :to_status, :changed_by)
    ");
    $logStmt->execute([
        ':ticket_id' => $result['id'],
        ':to_status' => 1,
        ':changed_by' => null,
    ]);

    $pdo->commit();
    $baseUrl = getenv('APP_URL') ?: 'http://127.0.0.1:8080';

    // ---------- สร้างข้อความแจ้งเตือน Telegram ----------
    $ticketCode = $result['code'];
    $msg = "<b>🛠 มีรายการแจ้งซ่อมใหม่!</b>\n";
    $msg .= "----------------------------------\n";
    $msg .= "<b>Ticket ID:</b> <code>{$ticketCode}</code>\n";
    $msg .= "<b>ผู้แจ้ง:</b> {$reporter} (โทร: {$phone})\n";
    $msg .= "<b>หน่วยงาน:</b> {$department}\n";
    $msg .= "<b>สถานที่:</b> ตึก {$building} ชั้น {$floor} (" . ($service_point ?: '-') . ")\n";
    $msg .= "----------------------------------\n";
    $msg .= "<b>ปัญหาการใช้งาน:</b> {$request_type_code}\n";
    $msg .= "<b>รายละเอียด:</b> {$categoryDisplayName} / {$symptomDisplayName}\n";
    $msg .= "----------------------------------\n";
    $msg .= "🔗 <a href='{$baseUrl}/?page=report-detail&code={$ticketCode}'>จัดการรายการนี้</a>";

    sendTelegramAlert($msg);

    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'message' => 'Ticket created',
        'ticket_code' => $ticketCode,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Internal Error', 'error' => $e->getMessage()]);
}

// ---------- Helper Functions ----------

function insert_ticket_with_code(PDO $pdo, array $payload, int $maxRetry = 5): array
{
    $sql = "INSERT INTO tickets (code, request_type_id, category_id, symptom_id, department, building, floor, service_point, phone_ext, reporter_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                $payload['reporter']
            ]);
            return ['ok' => true, 'code' => $code, 'id' => (int)$pdo->lastInsertId()];
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) continue;
            throw $e;
        }
    }
    return ['ok' => false, 'error' => 'Could not generate unique ticket code'];
}

function getRequestTypeIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM request_types WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function getCategoryIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM issue_categories WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function getSymptomIdByCode(PDO $pdo, string $code): ?int
{
    $stmt = $pdo->prepare("SELECT id FROM issue_symptoms WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

function new_ticket_code(): string
{
    $time = base_convert((string)time(), 10, 36);
    $rand = base_convert((string)random_int(100, 1295), 10, 36);
    return strtoupper("H{$time}-{$rand}");
}

function gen_auto_code(string $prefix): string
{
    return sprintf('%s-%s-%s', $prefix, date('Ymd'), strtoupper(bin2hex(random_bytes(3))));
}

function ensure_unique_code(PDO $pdo, string $table, string $codeBase): string
{
    $code = $codeBase;
    $i = 1;
    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE code = ? LIMIT 1");
    while (true) {
        $stmt->execute([$code]);
        if (!$stmt->fetch()) return $code;
        $code = $codeBase . '-' . $i++;
    }
}

function createCategoryWithCode(PDO $pdo, int $requestTypeId, string $nameTh): array
{
    $code = ensure_unique_code($pdo, 'issue_categories', gen_auto_code('CAT'));
    $stmt = $pdo->prepare("INSERT INTO issue_categories (request_type_id, code, name_th) VALUES (?, ?, ?)");
    $stmt->execute([$requestTypeId, $code, $nameTh]);
    return ['id' => (int)$pdo->lastInsertId(), 'code' => $code];
}

function createSymptomWithCode(PDO $pdo, int $categoryId, string $nameTh): array
{
    $code = ensure_unique_code($pdo, 'issue_symptoms', gen_auto_code('SYM'));
    $stmt = $pdo->prepare("INSERT INTO issue_symptoms (category_id, code, name_th) VALUES (?, ?, ?)");
    $stmt->execute([$categoryId, $code, $nameTh]);
    return ['id' => (int)$pdo->lastInsertId(), 'code' => $code];
}
