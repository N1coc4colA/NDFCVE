<?php
// Simple proxy to fetch CIRCL CVE API server-side to avoid CORS issues.
// Usage: api/circl_proxy.php?cve=CVE-YYYY-NNNN

header('Content-Type: application/json; charset=utf-8');
// It's same-origin when served from the same host, but leave CORS header off by default.
// If you need to allow other origins, add a controlled Access-Control-Allow-Origin header.

$pattern = '/^CVE-\d{4}-\d{4,}$/i';

if (!isset($_GET['cve'])) {
    http_response_code(400);
    echo json_encode(["error" => "missing_cve_parameter"]);
    exit;
}

$cve = strtoupper(trim($_GET['cve']));
if (!preg_match($pattern, $cve)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_cve_format"]);
    exit;
}

$remote = 'https://cve.circl.lu/api/cve/' . urlencode($cve);

$ch = curl_init($remote);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// set a user agent so remote API can log
curl_setopt($ch, CURLOPT_USERAGENT, 'NDFCVE-proxy/1.0 (+https://example.com)');

$body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($body === false || $body === null) {
    http_response_code(502);
    echo json_encode(["error" => "upstream_failure", "detail" => $curl_err]);
    exit;
}

// Forward the upstream status code (404 for not found will be preserved)
http_response_code($http_code);

// Try to pass the body through. CIRCL returns JSON; echo as-is.
// In case the body is not valid JSON, return a JSON error.
$json = json_decode($body, true);
if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
    // Not valid JSON, return as text inside JSON for debugging
    echo json_encode(["error" => "invalid_upstream_response", "body" => $body]);
    exit;
}

// echo the original JSON object exactly
echo json_encode($json);
