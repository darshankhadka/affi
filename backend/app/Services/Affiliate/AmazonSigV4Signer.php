<?php

namespace App\Services\Affiliate;

class AmazonSigV4Signer
{
    /**
     * Sign an Amazon PA-API 5.0 HTTP Request with AWS Signature Version 4
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $region (e.g. "us-east-1", "eu-west-1")
     * @param string $host (e.g. "webservices.amazon.com")
     * @param string $target (e.g. "com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems")
     * @param string $payload (JSON payload string)
     * @param ?string $timestamp (UTC timestamp string Ymd\THis\Z)
     * @return array<string, string> Signed headers to attach to the HTTP request
     */
    public function sign(
        string $accessKey,
        string $secretKey,
        string $region,
        string $host,
        string $target,
        string $payload,
        ?string $timestamp = null
    ): array {
        $service = 'ProductAdvertisingAPI';
        $timestamp = $timestamp ?? gmdate('Ymd\THis\Z');
        $date = substr($timestamp, 0, 8);

        $headers = [
            'content-encoding' => 'amz-1.0',
            'content-type' => 'application/json; charset=utf-8',
            'host' => $host,
            'x-amz-date' => $timestamp,
            'x-amz-target' => $target,
        ];

        ksort($headers);

        // 1. Canonical Headers & Signed Headers
        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($headers as $k => $v) {
            $kLower = strtolower(trim($k));
            $canonicalHeaders .= "{$kLower}:" . trim($v) . "\n";
            $signedHeadersList[] = $kLower;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        // 2. Canonical Request
        $httpMethod = 'POST';
        $canonicalUri = '/paapi5/getitems';
        if (str_contains($target, 'SearchItems')) {
            $canonicalUri = '/paapi5/searchitems';
        }

        $payloadHash = hash('sha256', $payload);
        $canonicalRequest = "{$httpMethod}\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        // 3. String to Sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = "{$algorithm}\n{$timestamp}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        // 4. Calculate Signature
        $kSecret = 'AWS4' . $secretKey;
        $kDate = hash_hmac('sha256', $date, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // 5. Authorization Header
        $authHeader = "{$algorithm} Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        return array_merge($headers, [
            'Authorization' => $authHeader,
        ]);
    }
}
