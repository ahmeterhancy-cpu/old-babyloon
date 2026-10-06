<?php
declare(strict_types=1);

/* ==========================================================================
 *  Eksik eklenti yedekleri
 *
 *  Paylaşımlı barındırmada mbstring kapalı olabiliyor. Kapalıysa
 *  config.php'nin ilk satırlarındaki mb_internal_encoding() çağrısı
 *  "undefined function" ölümcül hatası veriyor ve site BOŞ bir 500
 *  döndürüyor — hiçbir ipucu bırakmadan.
 *
 *  Buradaki yedekler o durumda devreye girer. Doğru çözüm eklentiyi
 *  açtırmaktır; bunlar sitenin en azından ayakta kalmasını sağlar.
 *  Eklenti varsa bu dosya hiçbir şey yapmaz.
 * ======================================================================= */

if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding(?string $encoding = null): string|bool
    {
        return $encoding === null ? 'UTF-8' : true;
    }
}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        if (function_exists('iconv_strlen')) {
            $n = @iconv_strlen($string, 'UTF-8');
            if ($n !== false) { return $n; }
        }
        // Çok baytlı UTF-8 dizisinin devam baytlarını (10xxxxxx) saymaz
        return (int) preg_match_all('/[^\x80-\xBF]/', $string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        if (function_exists('iconv_substr')) {
            $r = @iconv_substr($string, $start, $length ?? PHP_INT_MAX, 'UTF-8');
            if ($r !== false) { return $r; }
        }
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $part  = $length === null ? array_slice($chars, $start) : array_slice($chars, $start, $length);
        return implode('', $part);
    }
}

if (!function_exists('mb_strtolower')) {
    /**
     * Türkçe harfler ASCII strtolower'da bozulur (İ, I, Ç, Ğ, Ö, Ş, Ü).
     * Önce onlar elle çevrilir, kalanı ASCII kuralına bırakılır.
     */
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        $map = ['İ' => 'i', 'I' => 'ı', 'Ç' => 'ç', 'Ğ' => 'ğ', 'Ö' => 'ö',
                'Ş' => 'ş', 'Ü' => 'ü', 'Â' => 'â', 'Î' => 'î', 'Û' => 'û'];
        return strtolower(strtr($string, $map));
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        $map = ['i' => 'İ', 'ı' => 'I', 'ç' => 'Ç', 'ğ' => 'Ğ', 'ö' => 'Ö',
                'ş' => 'Ş', 'ü' => 'Ü', 'â' => 'Â', 'î' => 'Î', 'û' => 'Û'];
        return strtoupper(strtr($string, $map));
    }
}

if (!function_exists('mb_str_split')) {
    function mb_str_split(string $string, int $length = 1, ?string $encoding = null): array
    {
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return $length === 1 ? $chars : array_map('implode', array_chunk($chars, $length));
    }
}
