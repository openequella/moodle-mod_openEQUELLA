<?php

namespace mod_equella\utils;

class utility
{
    /**
     * Converts all newline characters to CR LF pairs.
     *
     * Based on the W3C standard, multi-line text field values use CR LF.
     * This function converts all "\n" to "\r\n".
     *
     * @param string $content The text to convert.
     * @return string The converted text.
     */
    public static function convert_newline_characters($content) {
        return preg_replace("/\r?\n/", "\r\n", $content);
    }
}