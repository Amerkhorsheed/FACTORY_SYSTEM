<?php

namespace App\Support;

/**
 * Converts integer money amounts to Arabic words for official PDFs.
 */
class ArabicAmount
{
    /** @var array<int, string> */
    private const ONES = [
        0 => '', 1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة',
        5 => 'خمسة', 6 => 'ستة', 7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة',
        10 => 'عشرة', 11 => 'أحد عشر', 12 => 'اثنا عشر', 13 => 'ثلاثة عشر',
        14 => 'أربعة عشر', 15 => 'خمسة عشر', 16 => 'ستة عشر', 17 => 'سبعة عشر',
        18 => 'ثمانية عشر', 19 => 'تسعة عشر',
    ];

    /** @var array<int, string> */
    private const TENS = [
        2 => 'عشرون', 3 => 'ثلاثون', 4 => 'أربعون', 5 => 'خمسون',
        6 => 'ستون', 7 => 'سبعون', 8 => 'ثمانون', 9 => 'تسعون',
    ];

    /** @var array<int, string> */
    private const HUNDREDS = [
        1 => 'مئة', 2 => 'مئتان', 3 => 'ثلاثمئة', 4 => 'أربعمئة', 5 => 'خمسمئة',
        6 => 'ستمئة', 7 => 'سبعمئة', 8 => 'ثمانمئة', 9 => 'تسعمئة',
    ];

    /** @var array<int, array{0: string, 1: string, 2: string, 3: string}> */
    private const SCALES = [
        1 => ['ألف', 'ألفان', 'آلاف', 'ألف'],
        2 => ['مليون', 'مليونان', 'ملايين', 'مليون'],
        3 => ['مليار', 'ملياران', 'مليارات', 'مليار'],
    ];

    public static function toSyp(int $amount): string
    {
        return self::toWords($amount).' '.__('pdf.common.syp_words');
    }

    public static function toWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        if ($number < 0) {
            return 'سالب '.self::toWords(abs($number));
        }

        $parts = [];
        $scale = 0;

        while ($number > 0) {
            $chunk = $number % 1000;

            if ($chunk > 0) {
                $parts[] = self::formatChunk($chunk, $scale);
            }

            $number = intdiv($number, 1000);
            $scale++;
        }

        return implode(' و', array_reverse($parts));
    }

    private static function formatChunk(int $number, int $scale): string
    {
        $words = self::belowThousand($number);

        if ($scale === 0) {
            return $words;
        }

        [$single, $dual, $plural, $many] = self::SCALES[$scale] ?? self::SCALES[3];

        return match (true) {
            $number === 1 => $single,
            $number === 2 => $dual,
            $number >= 3 && $number <= 10 => $words.' '.$plural,
            default => $words.' '.$many,
        };
    }

    private static function belowThousand(int $number): string
    {
        $parts = [];
        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;

        if ($hundreds > 0) {
            $parts[] = self::HUNDREDS[$hundreds];
        }

        if ($remainder >= 20) {
            $ones = $remainder % 10;
            $tens = intdiv($remainder, 10);

            if ($ones > 0) {
                $parts[] = self::ONES[$ones];
            }

            $parts[] = self::TENS[$tens];
        } elseif ($remainder > 0) {
            $parts[] = self::ONES[$remainder];
        }

        return implode(' و', $parts);
    }
}
