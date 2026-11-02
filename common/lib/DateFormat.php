<?php
// Locale names come from the same LC_TIME data as libc strftime(), without
// calling that deprecated function. Numeric fields use PHP's current timezone.
function a2b_strftime($format, $timestamp=null)
{
    if ($timestamp === null) $timestamp = time();
    $timestamp = (int)$timestamp;
    $result = preg_replace_callback('/%([-_0^#]*)([0-9]*)([EO]?)([%a-zA-Z])/', function ($match) use ($timestamp) {
        $code = $match[4];
        $weekday = (int)date('w', $timestamp);
        $month = (int)date('n', $timestamp);
        $numeric = true;
        switch ($code) {
            case '%': $value = '%'; $numeric = false; break;
            case 'a': $value = nl_langinfo(constant('ABDAY_' . ($weekday + 1))); $numeric = false; break;
            case 'A': $value = nl_langinfo(constant('DAY_' . ($weekday + 1))); $numeric = false; break;
            case 'b':
            case 'h': $value = nl_langinfo(constant('ABMON_' . $month)); $numeric = false; break;
            case 'B': $value = nl_langinfo(constant('MON_' . $month)); $numeric = false; break;
            case 'c':
            case 'x':
            case 'X':
            case 'r':
                $item = array('c' => D_T_FMT, 'x' => D_FMT, 'X' => T_FMT, 'r' => T_FMT_AMPM);
                $localeFormat = nl_langinfo($item[$code]);
                if ($code === 'r' && $localeFormat === '') $localeFormat = '%I:%M:%S %p';
                $value = a2b_strftime($localeFormat, $timestamp);
                $numeric = false;
                break;
            case 'C': $value = sprintf('%02d', (int)floor((int)date('Y', $timestamp) / 100)); break;
            case 'd': $value = date('d', $timestamp); break;
            case 'D': $value = date('m/d/y', $timestamp); break;
            case 'e': $value = sprintf('%2d', (int)date('j', $timestamp)); break;
            case 'F': $value = date('Y-m-d', $timestamp); break;
            case 'g': $value = sprintf('%02d', (int)date('o', $timestamp) % 100); break;
            case 'G': $value = date('o', $timestamp); break;
            case 'H': $value = date('H', $timestamp); break;
            case 'I': $value = date('h', $timestamp); break;
            case 'j': $value = sprintf('%03d', (int)date('z', $timestamp) + 1); break;
            case 'k': $value = sprintf('%2d', (int)date('G', $timestamp)); break;
            case 'l': $value = sprintf('%2d', (int)date('g', $timestamp)); break;
            case 'm': $value = date('m', $timestamp); break;
            case 'M': $value = date('i', $timestamp); break;
            case 'n': $value = "\n"; $numeric = false; break;
            case 'p':
            case 'P':
                $value = nl_langinfo((int)date('G', $timestamp) < 12 ? AM_STR : PM_STR);
                if ($code === 'P') $value = strtolower($value);
                $numeric = false;
                break;
            case 'R': $value = date('H:i', $timestamp); break;
            case 's': $value = (string)$timestamp; break;
            case 'S': $value = date('s', $timestamp); break;
            case 't': $value = "\t"; $numeric = false; break;
            case 'T': $value = date('H:i:s', $timestamp); break;
            case 'u': $value = date('N', $timestamp); break;
            case 'U': $value = sprintf('%02d', (int)floor(((int)date('z', $timestamp) + 7 - $weekday) / 7)); break;
            case 'V': $value = date('W', $timestamp); break;
            case 'w': $value = (string)$weekday; break;
            case 'W': $value = sprintf('%02d', (int)floor(((int)date('z', $timestamp) + 8 - (int)date('N', $timestamp)) / 7)); break;
            case 'y': $value = date('y', $timestamp); break;
            case 'Y': $value = date('Y', $timestamp); break;
            case 'z': $value = date('O', $timestamp); break;
            case 'Z': $value = date('T', $timestamp); $numeric = false; break;
            default: return $match[0];
        }
        if ($match[3] === 'O' && $numeric && preg_match('/^\s*\d+\s*$/', $value)) {
            $alternateDigits = explode(';', nl_langinfo(ALT_DIGITS));
            $index = (int)$value;
            if (isset($alternateDigits[$index]) && $alternateDigits[$index] !== '') {
                $value = $alternateDigits[$index];
                $numeric = false;
            } elseif (count($alternateDigits) === 1 && preg_match('/^(\p{Nd})\1*$/u', $alternateDigits[0], $zero)) {
                // glibc separates ALT_DIGITS with NULs; PHP exposes only the
                // first number. Decimal digit alphabets have consecutive codepoints.
                $point = unpack('Nzero', iconv('UTF-8', 'UCS-4BE', $zero[1]));
                $digits = array();
                for ($digit = 0; $digit <= 9; $digit++) {
                    $digits[(string)$digit] = iconv('UCS-4BE', 'UTF-8', pack('N', $point['zero'] + $digit));
                }
                $width = strlen($alternateDigits[0]) / strlen($zero[1]);
                $value = strtr(str_pad(trim($value), (int)$width, '0', STR_PAD_LEFT), $digits);
                $numeric = false;
            }
        }
        $flags = $match[1];
        if ($numeric && strpos($flags, '-') !== false) {
            $value = ltrim($value, ' 0');
            if ($value === '') $value = '0';
        } elseif ($numeric && (strpos($flags, '_') !== false || strpos($flags, '0') !== false)) {
            $padding = strpos($flags, '_') !== false ? ' ' : '0';
            $value = str_pad(ltrim($value, ' 0'), strlen($value), $padding, STR_PAD_LEFT);
        }
        if ($match[2] !== '') $value = str_pad($value, (int)$match[2], $numeric ? '0' : ' ', STR_PAD_LEFT);
        if (strpos($flags, '^') !== false || strpos($flags, '#') !== false) $value = strtoupper($value);
        return $value;
    }, $format);
    return $result === '' ? false : $result;
}
