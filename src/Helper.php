<?php

namespace TCGunel\ShprBot;

class Helper
{
    public static function d($w): ?string
    {
        $key    = sha1($w[1]);
        $strLen = strlen($w[0]);
        $keyLen = strlen($key);
        $j      = $hash = null;
        for ($i = 0; $i < $strLen; $i += 2) {
            $ordStr = hexdec(base_convert(strrev(substr($w[0], $i, 2)), 36, 16));
            if ($j == $keyLen) {
                $j = 0;
            }
            $ordKey = ord(substr($key, $j, 1));
            $j++;
            $hash .= chr($ordStr - $ordKey);
        }
        return $hash;
    }
}
