<?php

function getStatues()
{
    global $pdo;
    try {
        $sql = 'SELECT * FROM ticket_statuses ORDER BY sort_order DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $statues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $statues;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}
