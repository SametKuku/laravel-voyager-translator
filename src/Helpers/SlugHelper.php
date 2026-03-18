<?php

namespace SametKuku\LaravelTranslator\Helpers;

class SlugHelper
{
    private static array $turkishMap = [
        'ğ' => 'g', 'Ğ' => 'G', 'ü' => 'u', 'Ü' => 'U',
        'ş' => 's', 'Ş' => 'S', 'ı' => 'i', 'İ' => 'I',
        'ö' => 'o', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C',
    ];

    private static array $arabicMap = [
        'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ب' => 'b',
        'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z', 'س' => 's',
        'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z',
        'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => 'h', 'و' => 'w',
        'ي' => 'y', 'ى' => 'a', 'ة' => 'h', 'ء' => '',
    ];

    private static array $cyrillicMap = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    public static function slugify(string $text, ?string $fallback = null): string
    {
        if (empty($text)) {
            return $fallback ? static::slugify($fallback) : '';
        }

        // Transliterate Turkish
        $text = strtr($text, static::$turkishMap);

        // Transliterate Arabic
        $text = strtr($text, static::$arabicMap);

        // Transliterate Cyrillic (lowercase first)
        $lower = mb_strtolower($text);
        $text = strtr($lower, static::$cyrillicMap);

        // Remove diacritics (Spanish, French, etc.)
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/[^\w\-]+/', '', $text);
        $text = preg_replace('/\-\-+/', '-', $text);
        $text = trim($text, '-');

        if (empty($text)) {
            if ($fallback) {
                return static::slugify($fallback);
            }
            // Last resort: numeric hash
            return 'item-' . abs(crc32($text));
        }

        return $text;
    }
}
