<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/TicketStatusModel.php';

class TicketDetailController
{
    private TicketModel       $ticketModel;
    private TicketStatusModel $statusModel;
    private ?array            $user;
    private string            $ticketCode;

    public ?array $ticketDetails = null;
    public array  $statuses      = [];
    public bool   $canEditStatus = false;
    public ?string $error        = null;

    public function __construct(PDO $pdo, ?array $user, string $ticketCode)
    {
        $this->ticketModel  = new TicketModel($pdo);
        $this->statusModel  = new TicketStatusModel($pdo);
        $this->user         = $user;
        $this->ticketCode   = trim($ticketCode);

        $this->loadData();
        $this->checkPermissions();
    }

    private function loadData(): void
    {
        if ($this->ticketCode === '') {
            $this->error = 'INVALID_CODE';
            return;
        }

        $this->ticketDetails = $this->ticketModel->getTicketDetails($this->ticketCode);

        if ($this->ticketDetails) {
            $this->statuses = $this->statusModel->getStatusesForDropdown();
        }
    }

    private function checkPermissions(): void
    {
        $role = $this->user['role'] ?? '';
        $this->canEditStatus = in_array($role, ['SYSTEM', 'ADMIN', 'SERVICE'], true);
    }
}