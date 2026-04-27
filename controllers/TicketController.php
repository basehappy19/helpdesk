<?php

require_once __DIR__ . "/../models/TicketModel.php"; 

class TicketDetailController {
    private $pdo;
    private $user;
    private $ticketCode;
    private $ticketModel; // เปลี่ยนชื่อตัวแปรให้ตรงกับ Model

    // เปลี่ยนชื่อตัวแปรที่ส่งไป View
    public $ticketDetails = null; 
    public $statuses = [];
    public $canEditStatus = false;
    public $error = null;

    public function __construct($pdo, $user, $ticketCode) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->ticketCode = (string)$ticketCode;
        
        // เรียกใช้ TicketModel
        $this->ticketModel = new TicketModel($this->pdo);
        
        $this->loadData();
        $this->checkPermissions();
    }

    private function loadData() {
        if ($this->ticketCode === '') {
            $this->error = "INVALID_CODE";
            return;
        }

        // เรียกใช้เมธอด getTicketDetails จาก Model
        $this->ticketDetails = $this->ticketModel->getTicketDetails($this->ticketCode);

        // ถ้าพบข้อมูลตั๋วค่อยดึง Status มาแสดงใน Dropdown
        if ($this->ticketDetails) {
            $this->loadStatuses();
        }
    }

    private function loadStatuses() {
        try {
            $stmt = $this->pdo->query("SELECT id, name_th FROM ticket_statuses ORDER BY sort_order ASC, id ASC");
            $this->statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching statuses: " . $e->getMessage());
        }
    }

    private function checkPermissions() {
        if (isset($this->user['role']) && in_array($this->user['role'], ['SYSTEM', 'ADMIN', 'SERVICE'])) {
            $this->canEditStatus = true;
        }
    }
}
?>