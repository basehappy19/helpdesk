<?php 

function formatDateThaiBuddhist(string $datetime, ?DateTimeZone $tz = null): string
{
    $tz = $tz ?: new DateTimeZone('Asia/Bangkok');
    $dt = new DateTime($datetime, $tz);
    $year_th = (int)$dt->format('Y') + 543;
    return $dt->format('d/m/') . $year_th . $dt->format(' H:i');
}

function formatDateThaiBuddhistWithOutTime(string $datetime, ?DateTimeZone $tz = null): string
{
    $tz = $tz ?: new DateTimeZone('Asia/Bangkok');
    $dt = new DateTime($datetime, $tz);
    $year_th = (int)$dt->format('Y') + 543;
    return $dt->format('d/m/') . $year_th;
}

function formatDateThaiWithMonth($date)
{
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    [$y, $m, $d] = explode('-', $date);
    $y += 543;
    return "{$d} {$months[(int)$m]} {$y}";
}

?>