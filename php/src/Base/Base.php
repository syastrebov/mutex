<?php

/**
 * Class Base
 */
class Base
{
    /**
     * @param string $str
     */
    public static function msg($str)
    {
        $time = new DateTime;
        echo sprintf("%s on %s\r\n", $str, $time->format('H:i:s'));
        flush();
    }
} 