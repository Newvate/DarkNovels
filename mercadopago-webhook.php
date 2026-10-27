<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/mercadopago.php';

http_response_code(200);

$paymentId = trim((string) ($_GET['data_id'] ?? $_GET['id'] ?? ''));
$type = (string) ($_GET['type'] ?? $_GET['topic'] ?? '');

if ($type === 'payment' && $paymentId !== '' && mercado_pago_is_configured()) {
    // The result page performs the authenticated status check before showing approval.
    mercado_pago_api_request('GET', '/v1/payments/' . rawurlencode($paymentId));
}

echo 'OK';
