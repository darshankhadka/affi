<?php

namespace App\Support;

/**
 * Generic secret redactor. Centralizes the patterns that must never appear in
 * logs, exception messages, job error_log, retailer.last_error, or admin UI.
 *
 * Covers: API tokens, bearer tokens, access keys, secrets, passwords,
 *   Awin publisher/advertiser IDs in URLs, CJ tokens, Amazon SigV4 headers.
 */
class SecretRedactor
{
    protected const PATTERNS = [
        // Generic key/token/password patterns
        '/apikey\/[^\/\s&]+/i'                      => 'apikey/*****',
        '/api[_-]?key[=:]\s*[^\s&]+/i'             => 'api_key=*****',
        '/access[_-]?key[=:]\s*[^\s&]+/i'          => 'access_key=*****',
        '/secret[=:]\s*[^\s&]+/i'                   => 'secret=*****',
        '/token[=:]\s*[^\s&]+/i'                    => 'token=*****',
        '/password[=:]\s*[^\s&]+/i'                 => 'password=*****',

        // HTTP Authorization headers (Bearer, Basic, AWS4-HMAC-SHA256)
        '/authorization:\s*bearer\s+[^\s]+/i'       => 'Authorization: Bearer *****',
        '/authorization:\s*basic\s+[^\s]+/i'        => 'Authorization: Basic *****',
        '/authorization:\s*aws4-hmac-sha256[^\r\n]+/i' => 'Authorization: AWS4-HMAC-SHA256 *****',
        '/bearer\s+[^\s]+/i'                        => 'Bearer *****',

        // Awin specific
        '/awinaffid[=:]\s*[^\s&]+/i'               => 'awinaffid=*****',
        '/awinmid[=:]\s*[^\s&]+/i'                 => 'awinmid=*****',
        '/awin[_-]?api[_-]?key[=:]\s*[^\s&]+/i'   => 'awin_api_key=*****',
        '/awin[_-]?datafeed[_-]?key[=:]\s*[^\s&]+/i' => 'awin_datafeed_key=*****',

        // CJ specific
        '/cj[_-]?token[=:]\s*[^\s&]+/i'           => 'cj_token=*****',
        '/cj[_-]?api[_-]?token[=:]\s*[^\s&]+/i'   => 'cj_api_token=*****',
        '/company[_-]?id[=:]\s*\d{5,}/i'           => 'company_id=*****',

        // Amazon specific
        '/x-amz-security-token[=:]\s*[^\s&\r\n]+/i' => 'x-amz-security-token=*****',
        '/awsaccesskeyid[=:]\s*[^\s&]+/i'          => 'AWSAccessKeyId=*****',
        '/paapi[_-]?key[=:]\s*[^\s&]+/i'           => 'paapi_key=*****',
        '/paapi[_-]?secret[=:]\s*[^\s&]+/i'        => 'paapi_secret=*****',
        '/associate[_-]?tag[=:]\s*[^\s&]+/i'       => 'associate_tag=*****',

        // Database credentials in connection strings
        '/mysql:\/\/[^@]+@/i'                       => 'mysql://*****@',
        '/postgres:\/\/[^@]+@/i'                    => 'postgres://*****@',
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
                if (preg_match('/key|token|secret|affid|mid|password|api|credential|auth|sig|hash|sign/i', (string) $key)) {
                    $query[$key] = '*****';
                }
            }
            $parsed['query'] = http_build_query($query);
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host'] ?? '';
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

    /**
     * Redact sensitive keys from an array (for structured logging).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitizeArray(array $data): array
    {
        $sensitiveKeys = [
            'api_token', 'api_key', 'access_key', 'secret_key', 'secret', 'token',
            'password', 'authorization', 'bearer', 'credentials', 'awin_api_token',
            'datafeed_api_key', 'datafeed_url', 'cj_token', 'paapi_key', 'paapi_secret',
            'aws_access_key_id', 'aws_secret_access_key',
        ];

        $result = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[$key] = '*****';
            } elseif (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$key] = self::sanitize($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
