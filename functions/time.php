<?php

function diffLargestThai(string $datetime, ?DateTimeZone $tz = null): string
{
    $tz  = $tz ?: new DateTimeZone('Asia/Bangkok');
    $dt  = new DateTime($datetime, $tz);
    $now = new DateTime('now', $tz);

    $diffSec = max(0, $now->getTimestamp() - $dt->getTimestamp());

    if ($diffSec < 60) {
        return 'เมื่อสักครู่';
    }

    $units = [
        'ปี'     => 12 * 30 * 24 * 60 * 60,  // 12 เดือน
        'เดือน'  => 30 * 24 * 60 * 60,       // 30 วัน
        'วัน'    => 24 * 60 * 60,
        'ชั่วโมง' => 60 * 60,
        'นาที'   => 60,
    ];

    foreach ($units as $label => $secsPerUnit) {
        if ($diffSec >= $secsPerUnit) {
            $value = intdiv($diffSec, $secsPerUnit);
            return $value . ' ' . $label . 'ที่ผ่านมา';
        }
    }
    return 'เมื่อสักครู่';
}