<?php

function mercado_pago_env(string $key, string $fallback = ''): string
{
    $value = getenv($key);
    return $value === false ? $fallback : trim((string) $value);
}

// For shared hosting, paste the token only in this local variable or configure it as an environment variable.
$manualAccessToken = '';
define('MERCADOPAGO_ACCESS_TOKEN', mercado_pago_env('MERCADOPAGO_ACCESS_TOKEN', $manualAccessToken));
define('MERCADOPAGO_TEST_MODE', mercado_pago_env('MERCADOPAGO_TEST_MODE', 'true') !== 'false');
define('MERCADOPAGO_SIMULATION_MODE', true);
define('MERCADOPAGO_SITE_URL', rtrim(mercado_pago_env('MERCADOPAGO_SITE_URL', 'https://darknovels144.free.je'), '/'));

function mercado_pago_is_configured(): bool
{
    return function_exists('curl_init')
        && MERCADOPAGO_ACCESS_TOKEN !== ''
        && MERCADOPAGO_SITE_URL !== ''
        && dn_starts_with(MERCADOPAGO_SITE_URL, 'https://');
}

function mercado_pago_api_request(string $method, string $path, ?array $payload = null): array
{
    $curl = curl_init('https://api.mercadopago.com' . $path);
    $headers = [
        'Authorization: Bearer ' . MERCADOPAGO_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    $data = json_decode((string) $body, true);
    return [
        'status' => $status,
        'data' => is_array($data) ? $data : [],
        'error' => $error,
    ];
}
