<?php

if (!function_exists('sanitizeHtml')) {
    /**
     * Sanitize HTML input to prevent XSS attacks.
     *
     * @param  string  $input  The input to sanitize
     * @return string The sanitized input
     */
    function sanitizeHtml(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
