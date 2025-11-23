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

?>