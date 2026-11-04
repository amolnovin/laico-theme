<?php

declare(strict_types=1);

namespace App\Support;

final class Jalali
{
    private const FA_MONTHS = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    private const EN_MONTHS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];

    public static function toPersianDigits(string|int|float $input): string
    {
        return strtr((string) $input, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }

    public static function toEnglishDigits(string|int|float $input): string
    {
        return strtr((string) $input, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /**
     * Convert Gregorian (Y, m, d) to Jalali (jy, jm, jd).
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666
            + (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            + $gd
            + $gDaysInMonth[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * Format a Unix timestamp into Jalali Persian string (e.g. ۲۱ مهر ۱۴۰۵).
     */
    public static function formatTimestamp(int $timestamp, bool $withTime = false): string
    {
        $gy = (int) wp_date('Y', $timestamp);
        $gm = (int) wp_date('n', $timestamp);
        $gd = (int) wp_date('j', $timestamp);
        [$jy, $jm, $jd] = self::gregorianToJalali($gy, $gm, $gd);

        $date = sprintf('%s %s %s', self::toPersianDigits((string) $jd), self::FA_MONTHS[$jm] ?? '', self::toPersianDigits((string) $jy));
        if ($withTime) {
            $time = self::toPersianDigits((string) wp_date('H:i', $timestamp));
            return $date . ' — ' . $time;
        }

        return $date;
    }

    /**
     * Return [Persian Jalali Date, English Gregorian Date] for a post.
     *
     * @return array{0: string, 1: string}
     */
    public static function postDatePair(int|\WP_Post|null $post = null): array
    {
        $timestamp = get_post_timestamp($post) ?: time();
        $gy = (int) gmdate('Y', $timestamp);
        $gm = (int) gmdate('n', $timestamp);
        $gd = (int) gmdate('j', $timestamp);
        [$jy, $jm, $jd] = self::gregorianToJalali($gy, $gm, $gd);

        $fa = sprintf('%s %s %s', self::toPersianDigits((string) $jd), self::FA_MONTHS[$jm] ?? '', self::toPersianDigits((string) $jy));
        $en = sprintf('%d %s %d', $gd, self::EN_MONTHS[$gm] ?? '', $gy);

        return [$fa, $en];
    }

    /**
     * Convert a Jalali year like "1404" to approximate Gregorian year "2025".
     */
    public static function jalaliYearToGregorian(string|int $jalaliYear): string
    {
        $clean = (int) self::toEnglishDigits((string) $jalaliYear);
        if ($clean >= 1300 && $clean <= 1500) {
            return (string) ($clean + 621);
        }

        return (string) $jalaliYear;
    }
    public static function fromTimestamp(int $timestamp, bool $withTime = false): string
    {
        return self::formatTimestamp($timestamp, $withTime);
    }

    public static function approximateGregorianYear(string|int $jalaliYear): string
    {
        return self::jalaliYearToGregorian($jalaliYear);
    }
}
