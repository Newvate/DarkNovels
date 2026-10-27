<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/mercadopago.php';

$status = (string) ($_GET['status'] ?? 'unknown');
$paymentId = trim((string) ($_GET['payment_id'] ?? ''));
$reference = trim((string) ($_GET['external_reference'] ?? ''));
$payment = null;

if (mercado_pago_is_configured()) {
    $status = 'unknown';
    if ($paymentId !== '') {
        $response = mercado_pago_api_request('GET', '/v1/payments/' . rawurlencode($paymentId));
        if ($response['status'] >= 200 && $response['status'] < 300) {
            $payment = $response['data'];
            $status = (string) ($payment['status'] ?? 'unknown');
            $reference = (string) ($payment['external_reference'] ?? '');
        }
    }
}

$isDonation = $paymentId !== '' && ($reference === '' || str_starts_with($reference, 'DN-DONATION-'));
$statusCopy = [
    'approved' => ['Aporte aprobado', 'Gracias por apoyar la lectura libre.', 'success'],
    'pending' => ['Aporte pendiente', 'Mercado Pago aun esta confirmando el pago.', 'warning'],
    'rejected' => ['Aporte rechazado', 'El pago no fue aprobado. Puedes intentarlo nuevamente.', 'danger'],
    'cancelled' => ['Aporte cancelado', 'El proceso fue cancelado.', 'secondary'],
];
$copy = $statusCopy[$status] ?? ['Estado del pago', 'Mercado Pago devolvio el estado: ' . $status, 'secondary'];

$pageTitle = 'Resultado de donacion';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact">
    <div class="container">
        <p class="eyebrow">Mercado Pago</p>
        <h1><?= e($copy[0]) ?></h1>
    </div>
</section>

<section class="section-pad pt-4">
    <div class="container">
        <div class="donation-result alert alert-<?= e($copy[2]) ?>">
            <?php if ($isDonation): ?>
                <h2><?= e($copy[1]) ?></h2>
                <?php if ($paymentId): ?><p class="mb-1">Pago: <strong><?= e($paymentId) ?></strong></p><?php endif; ?>
                <?php if ($reference): ?><p class="mb-0">Referencia: <strong><?= e($reference) ?></strong></p><?php endif; ?>
            <?php else: ?>
                <h2>Referencia no valida.</h2>
                <p class="mb-0">No se pudo verificar que este pago pertenezca a DarkNovels.</p>
            <?php endif; ?>
        </div>
        <a class="btn btn-danger" href="<?= url('pages/library.php') ?>"><i class="bi bi-book"></i> Volver a la biblioteca</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
