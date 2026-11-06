<?php

namespace App\Support;

class EgyptianPhoneNumber
{
    public static function normalize(string $phone): string
    {
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $phone = preg_replace('/[\s().-]+/u', '', $phone) ?? '';

        if (str_starts_with($phone, '+20')) {
            return '0'.substr($phone, 3);
        }

        if (str_starts_with($phone, '0020')) {
            return '0'.substr($phone, 4);
        }

        if (str_starts_with($phone, '20') && strlen($phone) === 12) {
            return '0'.substr($phone, 2);
        }

        return $phone;
    }
}
