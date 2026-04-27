<?php

class DateHelper {
    
    public static function diffLargestThai(string $datetime, ?DateTimeZone $tz = null): string {
        $tz  = $tz ?: new DateTimeZone('Asia/Bangkok');
        $dt  = new DateTime($datetime, $tz);
        $now = new DateTime('now', $tz);

        $diffSec = max(0, $now->getTimestamp() - $dt->getTimestamp());

        if ($diffSec < 60) {
            return 'เมื่อสักครู่';
        }

        $units = [
            'ปี'     => 12 * 30 * 24 * 60 * 60,
            'เดือน'  => 30 * 24 * 60 * 60,
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

    public static function formatDateThaiBuddhist(string $datetime, ?DateTimeZone $tz = null): string {
        $tz = $tz ?: new DateTimeZone('Asia/Bangkok');
        $dt = new DateTime($datetime, $tz);
        $year_th = (int)$dt->format('Y') + 543;
        return $dt->format('d/m/') . $year_th . $dt->format(' H:i');
    }

    public static function formatDateThaiBuddhistWithOutTime(string $datetime, ?DateTimeZone $tz = null): string {
        $tz = $tz ?: new DateTimeZone('Asia/Bangkok');
        $dt = new DateTime($datetime, $tz);
        $year_th = (int)$dt->format('Y') + 543;
        return $dt->format('d/m/') . $year_th;
    }

    public static function formatDateThaiWithMonth($date) {
        if (empty($date)) return "-";
        
        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        // กันเหนียวกรณีส่ง datetime เข้ามา (มีเวลาต่อท้าย) ให้ตัดเอาแค่วันที่
        $dateOnly = explode(' ', $date)[0]; 
        
        [$y, $m, $d] = explode('-', $dateOnly);
        $y += 543;
        return "{$d} {$months[(int)$m]} {$y}";
    }
}