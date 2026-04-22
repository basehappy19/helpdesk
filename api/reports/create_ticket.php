<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../db/conn.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (Exception $e) {
    error_log("Dotenv Error: " . $e->getMessage());
}

// ตรวจสอบ Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// --- 1. รับค่าและทำความสะอาดข้อมูล (Input Handling) ---
$params = [
    'request_type'    => trim((string)($_POST['request_type'] ?? '')),
    'category'        => trim((string)($_POST['issue_category'] ?? '')),
    'category_other'  => trim((string)($_POST['issue_category_other'] ?? '')),
    'symptom'         => trim((string)($_POST['issue_symptom'] ?? '')),
    'symptom_other'   => trim((string)($_POST['issue_symptom_other'] ?? '')),
    'department'      => trim((string)($_POST['department'] ?? '')),
    'building'        => trim((string)($_POST['building'] ?? '')),
    'floor'           => trim((string)($_POST['floor'] ?? '')),
    'service_point'   => trim((string)($_POST['service_point'] ?? '')),
    'phone'           => trim((string)($_POST['phone'] ?? '')),
    'reporter'        => trim((string)($_POST['reporter'] ?? '')),
];

// Normalize 'other' values
if ($params['category'] === '' && $params['category_other'] !== '') $params['category'] = '__other__';
if ($params['symptom'] === '' && $params['symptom_other'] !== '')   $params['symptom']  = '__other__';

// --- 2. Validation ---
$errors = validateInput($params);
if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]);
    exit;
}

// --- 3. Main Logic (Database Transaction) ---
$pdo->beginTransaction();

try {
    // ดึงข้อมูลประเภทงาน (Request Type)
    $reqType = getInfoByCode($pdo, 'request_types', $params['request_type']);
    if (!$reqType) throw new RuntimeException('ไม่พบประเภทการแจ้งในระบบ');

    // จัดการหมวดหมู่ (Category) แบบ Hybrid
    $categoryId = null;
    $categoryRemark = null;
    $categoryName = '-';

    if ($params['category'] === '__other__') {
        // กรณีไม่มี Code ส่งมา แต่พิมพ์ค่าอื่นๆ มา
        $categoryRemark = $params['category_other'];
        $categoryName = $params['category_other'];
    } else {
        // กรณีส่ง Code มา (อาจเป็น Code หมวดปกติ หรือ Code ของหมวด "อื่นๆ")
        $cat = getInfoByCode($pdo, 'issue_categories', $params['category']);
        if (!$cat) throw new RuntimeException('ไม่พบหมวดหมู่ปัญหาในระบบ');
        $categoryId = (int)$cat['id'];
        $categoryName = $cat['name_th'];
        
        if ($params['category_other'] !== '') {
            $categoryRemark = $params['category_other'];
            $categoryName .= " (" . $params['category_other'] . ")";
        }
    }

    // จัดการอาการ (Symptom) แบบ Hybrid
    $symptomId = null;
    $symptomRemark = null;
    $symptomName = '-';

    if ($params['symptom'] === '__other__') {
        $symptomRemark = $params['symptom_other'];
        $symptomName = $params['symptom_other'];
    } else {
        $sym = getInfoByCode($pdo, 'issue_symptoms', $params['symptom']);
        if (!$sym) throw new RuntimeException('ไม่พบอาการที่ระบุในระบบ');
        $symptomId = (int)$sym['id'];
        $symptomName = $sym['name_th'];

        if ($params['symptom_other'] !== '') {
            $symptomRemark = $params['symptom_other'];
            $symptomName .= " (" . $params['symptom_other'] . ")";
        }
    }

    // บันทึก Ticket
    $ticketPayload = [
        'request_type_id'       => $reqType['id'],
        'issue_category_id'     => $categoryId,
        'category_other_remark' => $categoryRemark,
        'issue_symptom_id'      => $symptomId,
        'symptom_other_remark'  => $symptomRemark,
        'department'            => $params['department'],
        'building'              => $params['building'],
        'floor'                 => $params['floor'],
        'service_point'         => $params['service_point'] ?: null,
        'phone'                 => $params['phone'],
        'reporter'              => $params['reporter'],
    ];

    $result = insertTicketWithCode($pdo, $ticketPayload);
    if (!$result['ok']) throw new RuntimeException($result['error']);

    // บันทึก Log สถานะ (รอดำเนินการ = 1)
    $logStmt = $pdo->prepare("INSERT INTO ticket_status_logs (ticket_id, from_status, to_status) VALUES (?, NULL, 1)");
    $logStmt->execute([$result['id']]);

    $pdo->commit();

    // --- 4. การแจ้งเตือน (Telegram Notification) ---
    $baseUrl = $_ENV['APP_URL'] ?? 'http://127.0.0.1:8080';
    $msg = formatTelegramMessage($result['code'], $params, $reqType['name_th'], $categoryName, $symptomName, $baseUrl);
    sendTelegramAlert($msg);

    echo json_encode([
        'ok' => true,
        'message' => 'Ticket created',
        'ticket_code' => $result['code']
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Ticket Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ', 'error' => $e->getMessage()]);
}

/** * --- Helper Functions ---
 */

function validateInput(array $p): array {
    $e = [];
    if (!$p['request_type']) $e[] = 'กรุณาระบุประเภทการแจ้ง';
    if (!$p['category'] && !$p['category_other']) $e[] = 'กรุณาระบุหมวดหมู่ปัญหา';
    if (!$p['symptom'] && !$p['symptom_other'])   $e[] = 'กรุณาระบุอาการ';
    if (!$p['department'])   $e[] = 'กรุณาระบุหน่วยงาน';
    if (!$p['reporter'])     $e[] = 'กรุณาระบุชื่อผู้แจ้ง';
    return $e;
}

function getInfoByCode(PDO $pdo, string $table, string $code): ?array {
    $stmt = $pdo->prepare("SELECT id, name_th FROM {$table} WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function insertTicketWithCode(PDO $pdo, array $data, int $maxRetry = 5): array {
    $sql = "INSERT INTO tickets (
                code, request_type_id, category_id, category_other_remark, 
                symptom_id, symptom_other_remark, department, building, 
                floor, service_point, phone_ext, reporter_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    for ($i = 0; $i < $maxRetry; $i++) {
        $code = generateTicketCode();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $code, 
                $data['request_type_id'], 
                $data['issue_category_id'], 
                $data['category_other_remark'],
                $data['issue_symptom_id'], 
                $data['symptom_other_remark'],
                $data['department'], 
                $data['building'], 
                $data['floor'], 
                $data['service_point'], 
                $data['phone'], 
                $data['reporter']
            ]);
            return ['ok' => true, 'code' => $code, 'id' => (int)$pdo->lastInsertId()];
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) continue;
            throw $e;
        }
    }
    return ['ok' => false, 'error' => 'ไม่สามารถสร้างรหัส Ticket ได้'];
}

function formatTelegramMessage($code, $p, $reqType, $cat, $sym, $url): string {
    $msg = "<b>🛠 มีรายการแจ้งปัญหาใหม่!</b>\n";
    $msg .= "----------------------------------\n";
    $msg .= "<b>รหัส:</b> <code>{$code}</code>\n";
    $msg .= "<b>ผู้แจ้ง:</b> " . htmlspecialchars($p['reporter']) . " (โทร: {$p['phone']})\n";
    $msg .= "<b>หน่วยงาน:</b> " . htmlspecialchars($p['department']) . "\n";
    $msg .= "<b>สถานที่:</b> อาคาร {$p['building']} ชั้น {$p['floor']} (" . ($p['service_point'] ?: '-') . ")\n";
    $msg .= "----------------------------------\n";
    $msg .= "<b>ประเภทงาน:</b> {$reqType}\n";
    $msg .= "<b>ปัญหา:</b> " . htmlspecialchars($cat) . "\n";
    $msg .= "<b>อาการ:</b> " . htmlspecialchars($sym) . "\n";
    $msg .= "----------------------------------\n";
    $msg .= "🔗 <a href='{$url}/?page=report-detail&code={$code}'>ดูรายละเอียดรายงาน</a>";
    return $msg;
}

function sendTelegramAlert(string $message): void {
    $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
    
    // ตรวจสอบว่าโหลดตัวแปร .env มาได้หรือไม่
    if (!$token || !$chatId) {
        error_log("Telegram Alert Skipped: Missing TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID in .env file");
        return;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = json_encode(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // ป้องกัน cURL ค้าง
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // เช็คผลลัพธ์และเขียนลง Log ถ้าเกิดข้อผิดพลาด
    if ($response === false) {
        error_log("Telegram cURL Error: " . $curlError);
    } elseif ($httpCode >= 400) {
        error_log("Telegram API Error (HTTP {$httpCode}): " . $response);
    }
}

function generateTicketCode(): string {
    return strtoupper("H" . base_convert((string)time(), 10, 36) . "-" . base_convert((string)random_int(100, 1295), 10, 36));
}