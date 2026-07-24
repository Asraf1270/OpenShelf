<?php

namespace App\Support;

class RelativeTime
{
    public static function format(?string $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        $timestamp = strtotime($date);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }

        if ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        }

        if ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        }

        if ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        }

        return date('M j, Y', $timestamp);
    }
}
