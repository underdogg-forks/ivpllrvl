<?php

if (! function_exists('mb_rtrim')) {
    function mb_rtrim(string $string, string $characters = " \n\r\t\v\0"): string
    {
        return rtrim($string, $characters);
    }
}
