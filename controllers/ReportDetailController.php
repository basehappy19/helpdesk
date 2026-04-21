<?php

require_once __DIR__ . "/../functions/reports.php"; 

class ReportDetailController {
    private $pdo;
    private $user;
    private $ticketCode;

    // ตัวแปรสำหรับส่งไปให้ View ใช้งาน
    public $reportDetails = null;
    public $statuses = [];
    public $canEditStatus = false;
    public $error = null;

    public function __construct($pdo, $user, $ticketCode) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->ticketCode = (string)$ticketCode;
        
        $this->loadData();
        $this->checkPermissions();
    }

    // 1. โหลดข้อมูลรายละเอียดปัญหาและสถานะ
    private function loadData() {
        if ($this->ticketCode === '') {
            $this->error = "INVALID_CODE";
            return;
        }

        // ดึงข้อมูลรายละเอียดปัญหา (จาก functions/reports.php)
        $this->reportDetails = getReportDetails($this->ticketCode);

        // ถ้ามีข้อมูลปัญหา ให้ดึงรายการสถานะทั้งหมดมาเตรียมไว้สำหรับ Dropdown
        if ($this->reportDetails) {
            $this->loadStatuses();
        }
    }

    // 2. ดึงข้อมูลสถานะจากตาราง ticket_statuses
    private function loadStatuses() {
        try {
            $stmt = $this->pdo->query("SELECT id, name_th FROM ticket_statuses ORDER BY sort_order ASC, id ASC");
            $this->statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching statuses: " . $e->getMessage());
        }
    }

    // 3. ตรวจสอบสิทธิ์ (Role) ว่าสามารถจัดการสถานะได้หรือไม่
    private function checkPermissions() {
        if (isset($this->user['role']) && in_array($this->user['role'], ['SYSTEM', 'ADMIN', 'SERVICE'])) {
            $this->canEditStatus = true;
        }
    }
}
?>