<?php

namespace App\Support;

/**
 * Generic secret redactor. Centralizes the patterns that must never appear in
 * logs, exception messages, job error_log, retailer.last_error, or admin UI.
 */
class SecretRedactor
{
    protected const PATTERNS = [
        '/apikey\/[^\/\s&]+/i' => 'apikey/*****',
        '/api[_-]?key[=:]\s*[^\s&]+/i' => 'api_key=*****',
        '/access[_-]?key[=:]\s*[^\s&]+/i' => 'access_key=*****',
        '/secret[=:]\s*[^\s&]+/i' => 'secret=*****',
        '/token[=:]\s*[^\s&]+/i' => 'token=*****',
        '/password[=:]\s*[^\s&]+/i' => 'password=*****',
        '/authorization:\s*bearer\s+[^\s]+/i' => 'Authorization: Bearer *****',
        '/bearer\s+[^\s]+/i' => 'Bearer *****',
        '/awinaffid[=:]\s*[^\s&]+/i' => 'awinaffid=*****',
        '/awinmid[=:]\s*[^\s&]+/i' => 'awinmid=*****',
    ];

    public static function sanitize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $output = $input;
        foreach (self::PATTERNS as $pattern => $replacement) {
            $output = preg_replace($pattern, $replacement, $output);
        }

        return $output;
    }

    /**
     * Redact a full URL, preserving structure but hiding any embedded credentials/keys.
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return self::sanitize($url);
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            foreach ($query as $key => $value) {
                if (preg_match('/key|token|secret|affid|mid|password|api/i', (string) $key)) {
                    $query[$key] = '*****';
                }
            }
            $parsed['query'] = http_build_query($query);
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $result = $scheme . '://' . $host;
        if (isset($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }
        $result .= $parsed['path'] ?? '';
        if (isset($parsed['query'])) {
            $result .= '?' . $parsed['query'];
        }

        return $result;
    }
}
