<?php
/**
 * QR code utilities.
 *
 * @package chassesautresor.com
 */

defined('ABSPATH') || exit;

/**
 * Builds a QR code URL for a target URL.
 *
 * @param string $target URL to encode.
 * @param string $format Output format (png|svg|eps).
 * @param string $size   Size in WIDTHxHEIGHT format.
 *
 * @return string
 */
function cat_get_qr_code_url(string $target, string $format = 'png', string $size = '400x400'): string
{
    $allowed = ['png', 'svg', 'eps'];
    if (!in_array($format, $allowed, true)) {
        $format = 'png';
    }

    $base   = 'https://api.qrserver.com/v1/create-qr-code/';
    $params = [
        'size'   => $size,
        'data'   => $target,
        'format' => $format,
    ];

    return $base . '?' . http_build_query($params);
}
