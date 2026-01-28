<?php

namespace mod_equella\utils;

defined('MOODLE_INTERNAL') || die();

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

    /**
     * Sanitize a string by decoding HTML entities and escaping special characters.
     *
     * @param string $input The input string to sanitize.
     * @return string The sanitized string.
     */
    public static function sanitize_text(string $input): string {
        $decoded = self::decode_html_entities($input);
        return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Decode HTML entities in a string.
     *
     * @param string $input The input string to decode.
     * @return string The decoded string.
     */
    public static function decode_html_entities(string $input): string {
        return html_entity_decode($input, ENT_QUOTES, 'UTF-8');
    }
}