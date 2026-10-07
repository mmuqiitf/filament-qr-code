<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * Typed builders for common QR payload formats (WiFi, vCard, geo, …) so
 * apps stop hand-concatenating escaping-sensitive strings.
 */
class QrPayload
{
    public static function url(string $url): string
    {
        return trim($url);
    }

    public static function wifi(string $ssid, ?string $password = null, string $encryption = 'WPA', bool $hidden = false): string
    {
        $type = strtoupper($encryption) === 'NOPASS' ? 'nopass' : strtoupper($encryption);

        $payload = 'WIFI:T:'.$type.';S:'.self::escapeWifi($ssid).';';

        if ($password !== null && $password !== '' && $type !== 'nopass') {
            $payload .= 'P:'.self::escapeWifi($password).';';
        }

        if ($hidden) {
            $payload .= 'H:true;';
        }

        return $payload.';';
    }

    public static function mailto(string $to, ?string $subject = null, ?string $body = null): string
    {
        $query = [];

        if ($subject !== null && $subject !== '') {
            $query['subject'] = $subject;
        }

        if ($body !== null && $body !== '') {
            $query['body'] = $body;
        }

        $payload = 'mailto:'.trim($to);

        if ($query !== []) {
            $payload .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $payload;
    }

    public static function sms(string $number, ?string $body = null): string
    {
        $payload = 'SMSTO:'.trim($number);

        if ($body !== null && $body !== '') {
            $payload .= ':'.$body;
        }

        return $payload;
    }

    public static function geo(float $latitude, float $longitude, ?string $query = null): string
    {
        $payload = "geo:{$latitude},{$longitude}";

        if ($query !== null && $query !== '') {
            $payload .= '?q='.rawurlencode($query);
        }

        return $payload;
    }

    /**
     * @param  array{firstName?: string, lastName?: string, organization?: string, title?: string, phone?: string, email?: string, url?: string}  $contact
     */
    public static function vcard(array $contact): string
    {
        $first = self::escapeVcard($contact['firstName'] ?? '');
        $last = self::escapeVcard($contact['lastName'] ?? '');

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "N:{$last};{$first};;;",
            'FN:'.trim("{$first} {$last}"),
        ];

        foreach (['ORG' => $contact['organization'] ?? null, 'TITLE' => $contact['title'] ?? null] as $field => $value) {
            if (is_string($value) && $value !== '') {
                $lines[] = $field.':'.self::escapeVcard($value);
            }
        }

        if (! empty($contact['phone'])) {
            $lines[] = 'TEL;TYPE=CELL:'.$contact['phone'];
        }

        if (! empty($contact['email'])) {
            $lines[] = 'EMAIL:'.$contact['email'];
        }

        if (! empty($contact['url'])) {
            $lines[] = 'URL:'.$contact['url'];
        }

        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    private static function escapeWifi(string $value): string
    {
        return addcslashes($value, '\\;,:"');
    }

    private static function escapeVcard(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(["\r\n", "\n"], '\\n', $value);

        return str_replace([',', ';'], ['\\,', '\\;'], $value);
    }
}
