<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Parse waktu dari RouterOS (mis. ppp secret "last-logged-out") ke "Y-m-d H:i:s".
 * RouterOS 7: "2026-11-04 08:52:54"; RouterOS 6: "nov/04/2026 08:52:54".
 * Waktu router dianggap sudah dalam zona waktu aplikasi (router di-set Asia/Makassar).
 */
class RouterOsTime
{
    public static function parse(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || strtolower($value) === 'never') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'M/d/Y H:i:s', 'Y-m-d'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, ucfirst(strtolower($value)));
            } catch (\Throwable $e) {
                continue;
            }
            if ($dt === false || $dt->year < 2000) {
                return null; // "jan/01/1970 00:00:00" = belum pernah logout
            }

            return $dt->format('Y-m-d H:i:s');
        }

        return null;
    }
}
